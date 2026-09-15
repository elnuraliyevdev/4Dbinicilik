<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdjustCreditsRequest;
use App\Http\Requests\Admin\UpdateMemberRequest;
use App\Models\AuthEvent;
use App\Models\User;
use App\Services\CreditLedgerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;

class MemberController extends Controller
{
    public function __construct(private readonly CreditLedgerService $credits) {}

    public function index(Request $request): JsonResponse
    {
        $query = User::query()->where('role', 'member');

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('ref_code', 'like', "%{$search}%");
            });
        }

        // "Aktif" / "Pasif" mirror the old admin UI's status tabs — approximated
        // from remaining_lessons since there's no separate membership-status
        // field; confirm the exact real-site definition with the club later.
        match ($request->query('status')) {
            'active' => $query->where('remaining_lessons', '>', 0),
            'passive' => $query->where('remaining_lessons', '<=', 0),
            'expiring' => $query->whereNotNull('package_expires_at')->where('package_expires_at', '<=', now()->addDays(7)),
            default => null,
        };

        // "Program Kayıtlıları" (actively-enrolled) vs "Kulüp Üyeleri" (whole
        // roster) — approximated as having an active package assigned. Flagged
        // in the Phase 1 plan as needing confirmation against the real site.
        if ($request->query('scope') === 'program') {
            $query->whereNotNull('active_package_id');
        }

        $members = $query->orderBy('name')->paginate(50);

        return response()->json([
            'members' => $members,
            'counts' => [
                'club_members' => User::query()->where('role', 'member')->count(),
                'program_enrolled' => User::query()->where('role', 'member')->whereNotNull('active_package_id')->count(),
                'no_contact_info' => User::query()->where('role', 'member')->whereNull('phone')->whereNull('email')->count(),
            ],
        ]);
    }

    public function show(User $member): JsonResponse
    {
        abort_unless($member->role === 'member', 404);

        return response()->json([
            'member' => $member->load(['familyMemberships.family', 'reservations' => fn ($q) => $q->latest('date')->limit(10)]),
        ]);
    }

    /**
     * Primarily used to complete contact info for the 150+ members imported
     * with name-only data (see import:roster-names) so they can eventually
     * go through the claim-account flow.
     */
    public function update(UpdateMemberRequest $request, User $member): JsonResponse
    {
        abort_unless($member->role === 'member', 404);

        $member->update($request->validated());

        AuthEvent::log('MEMBER_UPDATED', 'info', "Admin {$request->user()->name} — {$member->name} bilgilerini güncelledi", [
            'user_id' => $request->user()->id,
            'role' => 'admin',
        ]);

        return response()->json(['member' => $member->fresh()]);
    }

    public function adjustCredits(AdjustCreditsRequest $request, User $member): JsonResponse
    {
        abort_unless($member->role === 'member', 404);

        $transaction = DB::transaction(fn () => $this->credits->adjustIndividual(
            $member,
            $request->validated('delta'),
            'admin_adjustment',
            $request->user()->id,
            $request->validated('note')
        ));

        AuthEvent::log('CREDIT_ADJUSTED', 'warning', "Admin {$request->user()->name} — {$member->name} bakiyesini {$transaction->delta} ders değiştirdi", [
            'user_id' => $request->user()->id,
            'role' => 'admin',
        ]);

        return response()->json(['member' => $member->fresh(), 'transaction' => $transaction]);
    }

    /**
     * Generates the signed claim-account link an admin sends the member
     * themselves (WhatsApp/SMS/email — no automated delivery yet, this just
     * returns the URL for the admin to copy).
     */
    public function sendClaimLink(Request $request, User $member): JsonResponse
    {
        abort_unless($member->role === 'member', 404);

        if ($member->password !== null) {
            return response()->json(['message' => 'Bu üyenin hesabı zaten kurulmuş.'], 422);
        }

        $url = URL::temporarySignedRoute('claim-account.show', now()->addDays(7), ['user' => $member->id]);

        AuthEvent::log('CLAIM_LINK_GENERATED', 'info', "Admin {$request->user()->name} — {$member->name} için hesap kurulum bağlantısı üretti", [
            'user_id' => $request->user()->id,
            'role' => 'admin',
        ]);

        return response()->json(['claim_url' => $url, 'expires_in_days' => 7]);
    }
}
