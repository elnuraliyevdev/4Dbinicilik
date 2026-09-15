<?php

namespace App\Http\Requests\Member;

use Illuminate\Foundation\Http\FormRequest;

class BookSafariRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'safari_tour_id' => ['required', 'integer', 'exists:safari_tours,id'],
            'date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'time' => ['required', 'date_format:H:i'],
            'participants' => ['required', 'integer', 'min:1', 'max:20'],
        ];
    }
}
