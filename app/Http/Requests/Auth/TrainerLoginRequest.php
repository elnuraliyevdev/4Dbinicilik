<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class TrainerLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'trainer_id' => ['required', 'integer', 'exists:trainers,id'],
            'pin' => ['required', 'string', 'max:20'],
        ];
    }
}
