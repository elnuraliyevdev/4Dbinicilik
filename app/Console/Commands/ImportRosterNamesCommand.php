<?php

namespace App\Console\Commands;

use App\Models\AuthEvent;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Partial-data fallback: the live site's members list view only shows "Name" +
 * "X ders" (remaining lesson count) per row — no phone/email/ref_code, those
 * only exist on each member's individual profile page. This imports just that,
 * updating remaining_lessons for members already known and creating
 * contact-less placeholder accounts (phone=null, email=null — cannot log in
 * until an admin fills in real contact info) for everyone else. This is
 * intentionally a lower-fidelity path than `import:legacy-data` — use that one
 * whenever a properly structured export (ideally a direct DB/CSV export from
 * the club's own backend, not a scraped page) becomes available instead.
 */
class ImportRosterNamesCommand extends Command
{
    protected $signature = 'import:roster-names {path=storage/legacy-data-full.json} {--elements-key=rawPages.members.elements}';

    protected $description = 'Import name + remaining-lesson-count pairs scraped from the live site\'s members list (no contact info)';

    public function handle(): int
    {
        $path = base_path($this->argument('path'));

        if (! file_exists($path)) {
            $this->error("File not found: {$path}");

            return self::FAILURE;
        }

        $data = json_decode(file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
        $elements = data_get($data, $this->option('elements-key'), []);

        $created = 0;
        $updated = 0;
        $skipped = 0;

        DB::transaction(function () use ($elements, &$created, &$updated, &$skipped) {
            foreach ($elements as $raw) {
                if (! preg_match('/^(.+?)\n\n(?:(\d+)\s*ders|Ders yok)$/us', trim($raw), $m)) {
                    $skipped++;

                    continue;
                }

                $name = trim($m[1]);
                $remaining = isset($m[2]) ? (int) $m[2] : 0;

                $existing = User::query()->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->first();

                if ($existing) {
                    $existing->update(['remaining_lessons' => $remaining]);
                    $updated++;

                    continue;
                }

                $user = User::query()->create([
                    'name' => $name,
                    'role' => 'member',
                    'remaining_lessons' => $remaining,
                    'total_lessons' => $remaining,
                    'password' => null,
                ]);
                $user->assignRole('member');
                $created++;
            }
        });

        AuthEvent::log('ROSTER_NAMES_IMPORTED', 'info', "Roster-only import: {$created} created, {$updated} updated, {$skipped} skipped");

        $this->info("Created {$created} contact-less placeholder members, updated {$updated} existing, skipped {$skipped} non-matching rows.");
        $this->warn('Created accounts have no phone/email and cannot log in — an admin must add contact info before they can.');
        $this->warn('Recommend asking the club for a real data export (Supabase CSV/DB dump) instead of relying on further page-scraping for contact info.');

        return self::SUCCESS;
    }
}
