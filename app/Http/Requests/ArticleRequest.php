<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ArticleRequest extends FormRequest
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
     */
    public function rules(): array
    {
        $articleId = $this->route('article')?->id;

        return [
            'name' => 'required|string|max:255',
            'sewing_price' => 'required|numeric|min:0',
            'code' => 'required|string|max:50|unique:articles,code,' . $articleId,
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Article name is required.',
            'sewing_price.required' => 'Sewing price is required.',
            'sewing_price.numeric' => 'Sewing price must be a number.',
            'sewing_price.min' => 'Sewing price cannot be negative.',
            'code.required' => 'Article code is required.',
            'code.unique' => 'This code is already in use.',
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422)
        );
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->has('is_active') ? $this->boolean('is_active') : true,
        ]);
    }
}
