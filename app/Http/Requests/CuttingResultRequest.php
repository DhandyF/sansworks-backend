<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class CuttingResultRequest extends FormRequest
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
        return [
            'fabric_id' => 'required|exists:fabrics,id',
            'brand_id' => 'required|exists:brands,id',
            'article_id' => 'required|exists:articles,id',
            'size_id' => 'required|exists:sizes,id',
            'total_cutting' => 'required|integer|min:1',
            'cutting_date' => 'required|date',
            'batch_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'fabric_id.required' => 'Fabric is required.',
            'fabric_id.exists' => 'Selected fabric does not exist.',
            'brand_id.required' => 'Brand is required.',
            'brand_id.exists' => 'Selected brand does not exist.',
            'article_id.required' => 'Article is required.',
            'article_id.exists' => 'Selected article does not exist.',
            'size_id.required' => 'Size is required.',
            'size_id.exists' => 'Selected size does not exist.',
            'total_cutting.required' => 'Total cutting is required.',
            'total_cutting.integer' => 'Total cutting must be a whole number.',
            'total_cutting.min' => 'Total cutting must be at least 1.',
            'cutting_date.required' => 'Cutting date is required.',
            'cutting_date.date' => 'Cutting date must be a valid date.',
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
}
