<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('member')?->id;

        return [
            'name' => ['sometimes', 'string', 'max:190'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:30', Rule::unique('users', 'phone')->ignore($userId)],
            'email' => ['sometimes', 'nullable', 'email', 'max:190', Rule::unique('users', 'email')->ignore($userId)],
            'ref_code' => ['sometimes', 'nullable', 'string', 'max:60', Rule::unique('users', 'ref_code')->ignore($userId)],
        ];
    }
}
