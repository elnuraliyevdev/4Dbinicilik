<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Http\Requests\Member\BuyPackageRequest;
use App\Models\AuthEvent;
use App\Models\Package;
use App\Models\PackagePurchaseRequest;
use Illuminate\Http\JsonResponse;

class PackageController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'packages' => Package::query()->where('is_active', true)->orderBy('sort_order')->get(),
        ]);
    }

    /**
     * Members don't pay online yet (the club is cash/WhatsApp-at-the-facility) —
     * this creates a pending request an admin approves, which is when credits
     * actually get added (see Phase 4 admin approval endpoint).
     */
    public function requestPurchase(BuyPackageRequest $request): JsonResponse
    {
        $user = $request->user();

        $purchaseRequest = PackagePurchaseRequest::create([
            'user_id' => $user->id,
            'package_id' => $request->validated('package_id'),
            'status' => 'pending',
            'notes' => $request->validated('notes'),
        ]);

        AuthEvent::log('PACKAGE_REQUEST_CREATED', 'info', "{$user->name} paket talebinde bulundu (#{$purchaseRequest->package_id})", [
            'user_id' => $user->id,
            'role' => $user->role,
        ]);

        return response()->json(['request' => $purchaseRequest], 201);
    }
}
