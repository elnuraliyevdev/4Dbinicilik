<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSettingRequest;
use App\Models\ClubSetting;
use Illuminate\Http\JsonResponse;

class SettingController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['settings' => ClubSetting::query()->orderBy('key')->get()]);
    }

    public function update(UpdateSettingRequest $request, string $key): JsonResponse
    {
        ClubSetting::set($key, $request->validated('value'), $request->user()->id);

        return response()->json(['setting' => ClubSetting::query()->where('key', $key)->first()]);
    }
}
