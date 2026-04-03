<?php

namespace App\Http\Requests\MenuItem;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMenuItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $menuItemId = $this->route('menu_item')?->id ?? $this->route('id');

        return [
            'category_id' => ['sometimes', 'integer', 'exists:categories,id'],
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => ['sometimes', 'nullable', 'string', 'max:255', Rule::unique('menu_items', 'slug')->ignore($menuItemId)],
            'price' => ['sometimes', 'numeric', 'min:0'],
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
