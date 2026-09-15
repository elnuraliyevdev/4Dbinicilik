<?php

namespace App\Http\Requests\Trainer;

use Illuminate\Foundation\Http\FormRequest;

class FeedbackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'student_user_id' => ['required', 'integer', 'exists:users,id'],
            'reservation_id' => ['required', 'integer', 'exists:reservations,id'],
            'discipline_level' => ['nullable', 'string', 'max:190'],
            'note' => ['required', 'string', 'max:2000'],
        ];
    }
}
