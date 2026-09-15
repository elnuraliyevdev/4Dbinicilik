<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AddFamilyMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'display_name' => ['required_without:user_id', 'nullable', 'string', 'max:190'],
            'is_primary' => ['nullable', 'boolean'],
        ];
    }
}
