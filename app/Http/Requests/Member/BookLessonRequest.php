<?php

namespace App\Http\Requests\Member;

use Illuminate\Foundation\Http\FormRequest;

class BookLessonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'trainer_id' => ['required', 'integer', 'exists:trainers,id'],
            'horse_id' => ['nullable', 'integer', 'exists:horses,id'],
            'date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'time' => ['required', 'date_format:H:i'],
            'activity_label' => ['nullable', 'string', 'max:190'],
        ];
    }
}
