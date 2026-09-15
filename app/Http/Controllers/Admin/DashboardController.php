<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PackagePurchaseRequest;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function index(): JsonResponse
    {
        $today = Carbon::today();

        $lessonsToday = Reservation::query()->where('type', 'lesson')->where('date', $today->toDateString());

        return response()->json([
            'lessons_today' => [
                'total' => (clone $lessonsToday)->count(),
                'completed' => (clone $lessonsToday)->where('status', 'completed')->count(),
                'pending' => (clone $lessonsToday)->where('status', 'confirmed')->count(),
            ],
            'new_registrations' => [
                'today' => User::query()->where('role', 'member')->whereDate('created_at', $today)->count(),
                'week' => User::query()->where('role', 'member')->where('created_at', '>=', $today->copy()->startOfWeek())->count(),
                'month' => User::query()->where('role', 'member')->where('created_at', '>=', $today->copy()->startOfMonth())->count(),
                'year' => User::query()->where('role', 'member')->where('created_at', '>=', $today->copy()->startOfYear())->count(),
            ],
            'revenue' => [
                'today' => $this->approvedRevenueSince($today),
                'week' => $this->approvedRevenueSince($today->copy()->startOfWeek()),
                'month' => $this->approvedRevenueSince($today->copy()->startOfMonth()),
                'all_time' => $this->approvedRevenueSince(null),
            ],
            'total_members' => User::query()->where('role', 'member')->count(),
            'members_with_contact_info' => User::query()->where('role', 'member')->whereNotNull('phone')->count(),
            'low_credit_members' => $this->lowCreditMembers(),
        ]);
    }

    private function approvedRevenueSince(?Carbon $since): float
    {
        return (float) PackagePurchaseRequest::query()
            ->where('status', 'approved')
            ->when($since, fn ($q) => $q->where('reviewed_at', '>=', $since))
            ->join('packages', 'packages.id', '=', 'package_purchase_requests.package_id')
            ->sum('packages.price_try');
    }

    /**
     * Real query, unlike the old prototype's hardcoded fake alert list —
     * every row here is an actual member with actual contact info on file.
     */
    private function lowCreditMembers(int $threshold = 2)
    {
        return User::query()
            ->where('role', 'member')
            ->where('remaining_lessons', '<=', $threshold)
            ->where(fn ($q) => $q->whereNotNull('phone')->orWhereNotNull('email'))
            ->orderBy('remaining_lessons')
            ->limit(20)
            ->get(['id', 'name', 'phone', 'email', 'remaining_lessons']);
    }
}
