<?php

namespace App\Http\Requests\API\v1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateNoteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'full_name' => 'string|max:255|nullable',
            'company' => 'string|max:255|nullable',
            'phone' => 'string|max:20|nullable',
            'email' => 'email|max:255|nullable',
            'birth_date' => 'date|nullable',
            'photo' => 'image|nullable',
        ];
    }
}
