<?php

namespace App\Http\Requests\DepositCuttingResult;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDepositCuttingResultRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'total_sewing_result' => ['sometimes', 'integer', 'min:1'],
            'cutting_price_per_pcs' => ['sometimes', 'numeric', 'min:0'],
            'deposit_date' => ['sometimes', 'date'],
            'quality_notes' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'charge_amount' => ['nullable', 'numeric', 'min:0'],
            'charge_percent' => ['nullable', 'integer', 'min:0', 'max:100'],
            'default_charge_per_pcs' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}