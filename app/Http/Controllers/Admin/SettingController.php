<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSettingRequest;
use App\Models\ClubSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class SettingController extends Controller
{
    /**
     * value is stored as a generic string on ClubSetting (key/value store), so
     * the FormRequest alone can't range-check it per key — a raw negative or
     * absurd cancellation_window_hours would otherwise silently break the
     * cancellation-window rule club-wide, and an unknown/typo'd key would
     * silently create an orphaned row nothing ever reads.
     */
    private const KNOWN_KEYS = ['monday_closed', 'cancellation_window_hours', 'club_whatsapp_number'];

    public function index(): JsonResponse
    {
        return response()->json(['settings' => ClubSetting::query()->orderBy('key')->get()]);
    }

    public function update(UpdateSettingRequest $request, string $key): JsonResponse
    {
        if (! in_array($key, self::KNOWN_KEYS, true)) {
            throw ValidationException::withMessages(['key' => 'Bilinmeyen ayar anahtarı.']);
        }

        $value = $request->validated('value');

        if ($key === 'cancellation_window_hours' && (! ctype_digit($value) || (int) $value < 0 || (int) $value > 168)) {
            throw ValidationException::withMessages(['value' => 'İptal süresi 0 ile 168 saat arasında bir tam sayı olmalıdır.']);
        }

        if ($key === 'monday_closed' && ! in_array($value, ['0', '1'], true)) {
            throw ValidationException::withMessages(['value' => 'Bu ayar yalnızca 0 veya 1 olabilir.']);
        }

        ClubSetting::set($key, $value, $request->user()->id);

        return response()->json(['setting' => ClubSetting::query()->where('key', $key)->first()]);
    }
}
