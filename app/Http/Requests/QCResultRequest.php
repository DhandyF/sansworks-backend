<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class QCResultRequest extends FormRequest
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
            'deposit_cutting_result_id' => 'required|exists:deposit_cutting_results,id',
            'tailor_id' => 'required|exists:tailors,id',
            'brand_id' => 'required|exists:brands,id',
            'article_id' => 'required|exists:articles,id',
            'size_id' => 'required|exists:sizes,id',
            'total_products' => 'required|integer|min:1',
            'total_to_repair' => 'required|integer|min:0',
            'qc_date' => 'required|date',
            'qc_by' => 'nullable|exists:users,id',
            'defect_details' => 'nullable|array',
            'notes' => 'nullable|string',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'deposit_cutting_result_id.required' => 'Deposit cutting result is required.',
            'deposit_cutting_result_id.exists' => 'Selected deposit does not exist.',
            'tailor_id.required' => 'Tailor is required.',
            'tailor_id.exists' => 'Selected tailor does not exist.',
            'brand_id.required' => 'Brand is required.',
            'brand_id.exists' => 'Selected brand does not exist.',
            'article_id.required' => 'Article is required.',
            'article_id.exists' => 'Selected article does not exist.',
            'size_id.required' => 'Size is required.',
            'size_id.exists' => 'Selected size does not exist.',
            'total_products.required' => 'Total products is required.',
            'total_products.min' => 'Total products must be at least 1.',
            'total_to_repair.required' => 'Total to repair is required.',
            'total_to_repair.min' => 'Total to repair cannot be negative.',
            'qc_date.required' => 'QC date is required.',
            'qc_date.date' => 'QC date must be a valid date.',
            'qc_by.exists' => 'Selected QC user does not exist.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $totalProducts = (int) $this->input('total_products');
            $totalToRepair = (int) $this->input('total_to_repair');

            if ($totalToRepair > $totalProducts) {
                $validator->errors()->add('total_to_repair', 'Total to repair cannot exceed total products.');
            }
        });
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
