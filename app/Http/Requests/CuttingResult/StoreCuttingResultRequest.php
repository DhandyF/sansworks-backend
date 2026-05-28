<?php

namespace App\Http\Requests\CuttingResult;

use Illuminate\Foundation\Http\FormRequest;

class StoreCuttingResultRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'pre_order_id' => ['required', 'exists:pre_orders,id'],
            'article_id' => ['required', 'exists:articles,id'],
            'size_id' => ['required', 'exists:sizes,id'],
            'total_cutting' => ['required', 'integer', 'min:1'],
            'excess_cutting' => ['nullable', 'numeric', 'min:0'],
            'cutting_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // No validation preventing excess cutting - allow over-cutting
            // Excess will be calculated automatically in the service
        });
    }
}