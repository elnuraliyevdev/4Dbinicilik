<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePackageRequest;
use App\Http\Requests\Admin\UpdatePackageRequest;
use App\Models\Package;
use Illuminate\Http\JsonResponse;

class PackageController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['packages' => Package::query()->orderBy('sort_order')->get()]);
    }

    public function store(StorePackageRequest $request): JsonResponse
    {
        $package = Package::create($request->validated());

        return response()->json(['package' => $package], 201);
    }

    public function update(UpdatePackageRequest $request, Package $package): JsonResponse
    {
        $package->update($request->validated());

        return response()->json(['package' => $package->fresh()]);
    }
}
