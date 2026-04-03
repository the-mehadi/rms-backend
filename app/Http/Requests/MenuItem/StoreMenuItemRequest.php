<?php

namespace App\Http\Requests\MenuItem;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMenuItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('menu_items', 'slug')],
            'price' => ['required', 'numeric', 'min:0'],
            'description' => ['sometimes', 'nullable', 'string'],
            'is_available' => ['sometimes', 'boolean'],
            'order' => ['sometimes', 'integer'],
        ];
    }

    public function messages(): array
    {
        return [];
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
