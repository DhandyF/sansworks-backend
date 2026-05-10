<?php

namespace App\Http\Requests\DepositCuttingResult;

use Illuminate\Foundation\Http\FormRequest;

class StoreDepositCuttingResultBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'distribution_ids' => ['required', 'array', 'min:1'],
            'distribution_ids.*' => ['required', 'exists:cutting_distributions,id'],
            'total_sewing_result' => ['required', 'integer', 'min:1'],
            'cutting_price_per_pcs' => ['required', 'numeric', 'min:0'],
            'deposit_date' => ['required', 'date'],
            'quality_notes' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $ids = $this->input('distribution_ids', []);
            $totalSewing = (int) $this->input('total_sewing_result');

            if (!empty($ids) && $totalSewing > 0) {
                $totalAvailable = \App\Models\CuttingDistribution::whereIn('id', $ids)
                    ->with('deposits')
                    ->get()
                    ->sum(function ($dist) {
                        return $dist->total_cutting - $dist->deposits->sum('total_sewing_result');
                    });

                if ($totalSewing > $totalAvailable) {
                    $validator->errors()->add('total_sewing_result', "Total sewing result ({$totalSewing}) exceeds total available ({$totalAvailable}) across selected distributions.");
                }
            }
        });
    }
}