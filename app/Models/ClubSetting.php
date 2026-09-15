<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['key', 'value', 'updated_by'])]
class ClubSetting extends Model
{
    public static function get(string $key, mixed $default = null): mixed
    {
        return static::query()->where('key', $key)->value('value') ?? $default;
    }

    public static function set(string $key, mixed $value, ?int $updatedBy = null): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => $value, 'updated_by' => $updatedBy]);
    }
}
