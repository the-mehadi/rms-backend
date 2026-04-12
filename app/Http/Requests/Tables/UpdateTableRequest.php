<?php

namespace App\Http\Requests\Tables;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTableRequest extends FormRequest
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
            'table_number' => [
                'sometimes',
                'integer',
                'min:1',
                Rule::unique('tables')->ignore($this->route('table')?->id ?? $this->route('table')),
            ],
            'capacity' => 'sometimes|integer|min:1',
            'status' => 'sometimes|in:available,occupied,reserved',
        ];
    }
}
