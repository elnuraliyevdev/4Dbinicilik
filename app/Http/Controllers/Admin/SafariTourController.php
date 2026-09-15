<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSafariTourRequest;
use App\Http\Requests\Admin\UpdateSafariTourRequest;
use App\Models\SafariTour;
use Illuminate\Http\JsonResponse;

class SafariTourController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['tours' => SafariTour::query()->orderBy('sort_order')->get()]);
    }

    public function store(StoreSafariTourRequest $request): JsonResponse
    {
        $tour = SafariTour::create($request->validated());

        return response()->json(['tour' => $tour], 201);
    }

    public function update(UpdateSafariTourRequest $request, SafariTour $safariTour): JsonResponse
    {
        $safariTour->update($request->validated());

        return response()->json(['tour' => $safariTour->fresh()]);
    }
}
