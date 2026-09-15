<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuthEvent;
use App\Models\PackagePurchaseRequest;
use App\Services\CreditLedgerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseRequestController extends Controller
{
    public function __construct(private readonly CreditLedgerService $credits) {}

    public function index(Request $request): JsonResponse
    {
        $requests = PackagePurchaseRequest::query()
            ->with(['user', 'package'])
            ->when($request->query('status', 'pending'), fn ($q, $status) => $status === 'all' ? $q : $q->where('status', $status))
            ->latest()
            ->get();

        return response()->json(['requests' => $requests]);
    }

    public function approve(Request $request, PackagePurchaseRequest $purchaseRequest): JsonResponse
    {
        if ($purchaseRequest->status !== 'pending') {
            return response()->json(['message' => 'Bu talep zaten işleme alınmış.'], 422);
        }

        $package = $purchaseRequest->package;
        abort_unless($package, 422, 'Paket bulunamadı.');

        try {
            DB::transaction(function () use ($purchaseRequest, $package, $request) {
                $purchaseRequest->lockAndRequireStatus('pending', 'Bu talep zaten işleme alınmış.');

                $this->credits->adjustIndividual(
                    $purchaseRequest->user,
                    $package->lesson_count,
                    'package_purchase',
                    $request->user()->id,
                    "Onaylanan paket talebi #{$purchaseRequest->id}"
                );

                $purchaseRequest->user->update(['active_package_id' => $package->id]);

                $purchaseRequest->update([
                    'status' => 'approved',
                    'reviewed_by' => $request->user()->id,
                    'reviewed_at' => now(),
                ]);
            });
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        AuthEvent::log('PACKAGE_REQUEST_APPROVED', 'success', "Admin {$request->user()->name} — paket talebi onayladı: {$purchaseRequest->user->name} (+{$package->lesson_count} ders)", [
            'user_id' => $request->user()->id,
            'role' => 'admin',
        ]);

        return response()->json(['request' => $purchaseRequest->fresh(['user', 'package'])]);
    }

    public function reject(Request $request, PackagePurchaseRequest $purchaseRequest): JsonResponse
    {
        try {
            DB::transaction(function () use ($purchaseRequest, $request) {
                $purchaseRequest->lockAndRequireStatus('pending', 'Bu talep zaten işleme alınmış.');

                $purchaseRequest->update([
                    'status' => 'rejected',
                    'reviewed_by' => $request->user()->id,
                    'reviewed_at' => now(),
                ]);
            });
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        AuthEvent::log('PACKAGE_REQUEST_REJECTED', 'info', "Admin {$request->user()->name} — paket talebi reddetti: {$purchaseRequest->user->name}", [
            'user_id' => $request->user()->id,
            'role' => 'admin',
        ]);

        return response()->json(['request' => $purchaseRequest->fresh()]);
    }
}
