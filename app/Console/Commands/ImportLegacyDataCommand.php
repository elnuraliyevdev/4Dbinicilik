<?php

namespace App\Console\Commands;

use App\Models\AuthEvent;
use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\Horse;
use App\Models\Package;
use App\Models\Reservation;
use App\Models\Trainer;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * One-time import of the real member/trainer/family/reservation/package data that used
 * to sit hardcoded in the old static prototype's public app.js. Run this once against
 * the out-of-band legacy-data.json (never committed — see storage/.gitignore), then
 * delete that file. See Phase 1 plan §5 for the full rationale.
 */
class ImportLegacyDataCommand extends Command
{
    protected $signature = 'import:legacy-data {path=storage/legacy-data.json}';

    protected $description = 'One-time import of real club data from the legacy static-site JSON export';

    /** @var array<string,int> legacy member id (e.g. "MEM-003") => new users.id */
    private array $memberIdMap = [];

    /** @var array<string,int> trainer name => new trainers.id */
    private array $trainerNameMap = [];

    /** @var array<string,int> horse name (without breed suffix) => new horses.id */
    private array $horseNameMap = [];

    public function handle(): int
    {
        $path = base_path($this->argument('path'));

        if (! file_exists($path)) {
            $this->error("File not found: {$path}");

            return self::FAILURE;
        }

        $data = json_decode(file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);

        DB::transaction(function () use ($data) {
            $trainerCount = $this->importTrainers($data['trainers'] ?? []);
            $memberCount = $this->importMembers($data['members'] ?? []);
            $familyCount = $this->importFamilies($data['families'] ?? []);
            $packageCount = $this->importPackages($data['packagePricing'] ?? []);
            $reservationCount = $this->importReservations($data['reservations'] ?? []);

            AuthEvent::log('LEGACY_DATA_IMPORTED', 'info', sprintf(
                'Legacy import: %d trainers, %d members, %d families, %d packages, %d reservations',
                $trainerCount, $memberCount, $familyCount, $packageCount, $reservationCount
            ));

            $this->info("Imported: {$trainerCount} trainers, {$memberCount} members, {$familyCount} families, {$packageCount} packages, {$reservationCount} reservations.");
        });

        if (! empty($data['_secondaryLeak_lowCreditContacts']['contacts'])) {
            $this->warn('NOT auto-imported (unreconciled — no matching member record found in the original data):');
            foreach ($data['_secondaryLeak_lowCreditContacts']['contacts'] as $c) {
                $this->line("  - {$c['name']} <{$c['email']}>");
            }
            $this->warn('Have an admin manually confirm and add these as real members if appropriate.');
        }

        $this->newLine();
        $this->info('Every imported member/trainer has password=null and pin_hash=null (or a freshly generated PIN for trainers, printed above) — no legacy credentials were reused.');
        $this->info("Once verified, delete the source file: {$path}");

        return self::SUCCESS;
    }

    private function importTrainers(array $trainers): int
    {
        foreach ($trainers as $t) {
            $pin = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);

            $user = User::query()->updateOrCreate(
                ['name' => $t['name'], 'role' => 'trainer'],
                ['pin_hash' => Hash::make($pin)]
            );
            $user->assignRole('trainer');

            $trainer = Trainer::query()->updateOrCreate(
                ['user_id' => $user->id],
                ['title' => $t['role'] ?? null, 'avatar_letter' => $t['avatar'] ?? null]
            );

            $this->trainerNameMap[$t['name']] = $trainer->id;
            $this->line("  trainer '{$t['name']}' PIN: {$pin}");
        }

        return count($trainers);
    }

    private function importMembers(array $members): int
    {
        foreach ($members as $m) {
            $role = $m['role'] ?? 'member';

            $user = User::query()->updateOrCreate(
                ['ref_code' => $m['refCode']],
                [
                    'name' => $m['name'],
                    'phone' => $m['phone'] ?? null,
                    'email' => $m['email'] ?? null,
                    'role' => $role,
                    'total_lessons' => $m['totalLessons'] ?? 0,
                    'used_lessons' => $m['usedLessons'] ?? 0,
                    'remaining_lessons' => $m['remainingLessons'] ?? 0,
                    'password' => null,
                    'pin_hash' => null,
                ]
            );
            $user->assignRole($role);

            $this->memberIdMap[$m['id']] = $user->id;
        }

        return count($members);
    }

    private function importFamilies(array $families): int
    {
        foreach ($families as $f) {
            $family = Family::query()->updateOrCreate(
                ['name' => $f['name']],
                [
                    'package_total' => $f['packageTotal'] ?? 0,
                    'remaining_lessons' => $f['remainingLessons'] ?? 0,
                    'used_lessons' => $f['used'] ?? 0,
                    'reserved_lessons' => $f['reserved'] ?? 0,
                ]
            );

            foreach ($f['members'] ?? [] as $entry) {
                $isPrimary = str_contains($entry, '(Ana Üye)');
                $name = trim(str_replace('(Ana Üye)', '', $entry));

                // Best-effort match against an already-imported member by name;
                // dependents with no account of their own stay display-name-only.
                $matchedUser = User::query()->where('name', $name)->first();

                FamilyMember::query()->updateOrCreate(
                    ['family_id' => $family->id, 'display_name' => $name],
                    ['user_id' => $matchedUser?->id, 'is_primary' => $isPrimary]
                );
            }

            if ($f['primaryMember'] ?? null) {
                $primary = User::query()->where('name', $f['primaryMember'])->first();
                if ($primary) {
                    $family->update(['primary_user_id' => $primary->id]);
                }
            }
        }

        return count($families);
    }

    private function importPackages(array $packages): int
    {
        foreach ($packages as $i => $p) {
            $price = (float) str_replace(['.', ' ₺'], ['', ''], $p['price']);
            $isFeatured = str_contains($p['status'], 'Popüler');
            $isActive = str_starts_with($p['status'], 'Aktif');

            Package::query()->updateOrCreate(
                ['lesson_count' => $p['lessons']],
                [
                    'price_try' => $price,
                    'is_active' => $isActive,
                    'is_featured' => $isFeatured,
                    'badge_label' => $isFeatured ? '⭐ En Popüler' : null,
                    'sort_order' => $i,
                ]
            );
        }

        return count($packages);
    }

    private function importReservations(array $reservations): int
    {
        foreach ($reservations as $r) {
            $userId = $this->memberIdMap[$r['memberId']] ?? null;
            if (! $userId) {
                $this->warn("  skipping reservation {$r['id']} — unknown memberId {$r['memberId']}");

                continue;
            }

            $trainerId = $this->resolveTrainerId($r['trainer'] ?? null);
            $horseId = $this->resolveHorseId($r['horse'] ?? null);

            $status = match ($r['status'] ?? null) {
                'Tamamlandı' => 'completed',
                'Geç İptal' => 'late_cancelled',
                'Gelmedi (Düştü)' => 'no_show',
                default => 'confirmed',
            };

            Reservation::query()->updateOrCreate(
                ['reservation_code' => $r['id']],
                [
                    'user_id' => $userId,
                    'trainer_id' => $trainerId,
                    'horse_id' => $horseId,
                    'type' => 'lesson',
                    'activity_label' => $r['title'] ?? 'Ders',
                    'date' => $r['date'],
                    'time' => $r['time'],
                    'status' => $status,
                    'source' => 'admin',
                ]
            );
        }

        return count($reservations);
    }

    private function resolveTrainerId(?string $trainerLabel): ?int
    {
        if (! $trainerLabel) {
            return null;
        }
        // Labels like "Emre Özmen (Baş Antrenör)" — strip the parenthetical.
        $name = trim(preg_replace('/\s*\(.*\)\s*$/', '', $trainerLabel));

        return $this->trainerNameMap[$name] ?? null;
    }

    private function resolveHorseId(?string $horseLabel): ?int
    {
        if (! $horseLabel) {
            return null;
        }

        if (preg_match('/^(.*?)\s*\((.*)\)$/', $horseLabel, $matches)) {
            $name = trim($matches[1]);
            $breed = trim($matches[2]);
        } else {
            $name = trim($horseLabel);
            $breed = null;
        }

        if (isset($this->horseNameMap[$name])) {
            return $this->horseNameMap[$name];
        }

        $horse = Horse::query()->firstOrCreate(['name' => $name], ['breed' => $breed]);
        $this->horseNameMap[$name] = $horse->id;

        return $horse->id;
    }
}
