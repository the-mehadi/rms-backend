<?php

namespace App\Http\Requests\MenuItem;

use Illuminate\Foundation\Http\FormRequest;

class StoreMenuItemImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'image' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp,heic', 'max:10240'],
            'alt_text' => ['sometimes', 'nullable', 'string', 'max:255'],
            'order' => ['sometimes', 'integer'],
        ];
    }

    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        throw new \Illuminate\Http\Exceptions\HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors(),
            ], 422)
        );
    }
}
