<?php

namespace App\Http\Requests\DepositCuttingResult;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreDepositCuttingResultRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cutting_distribution_id' => ['required', 'exists:cutting_distributions,id'],
            'total_sewing_result' => ['required', 'integer', 'min:1'],
            'deposit_date' => ['required', 'date'],
            'quality_notes' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $distributionId = $this->input('cutting_distribution_id');
            $totalSewing = (int) $this->input('total_sewing_result');

            if (!$distributionId || !$totalSewing) {
                return;
            }

            $distribution = \App\Models\CuttingDistribution::with('deposits')->find($distributionId);

            if (!$distribution) {
                return;
            }

            $deposited = (int) $distribution->deposits->sum('total_sewing_result');
            $available = $distribution->total_cutting - $deposited;

            if ($totalSewing > $available) {
                $validator->errors()->add(
                    'total_sewing_result',
                    "Total sewing result ({$totalSewing}) exceeds available distribution quantity ({$available})."
                );
            }
        });
    }
}