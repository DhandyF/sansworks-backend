<?php

namespace App\Http\Requests\CuttingDistribution;

use Illuminate\Foundation\Http\FormRequest;

class StoreCuttingDistributionBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cutting_result_name' => ['required', 'string', 'exists:cutting_results,name'],
            'tailor_id' => ['required', 'exists:tailors,id'],
            'total_cutting' => ['required', 'integer', 'min:1'],
            'taken_date' => ['required', 'date'],
            'deadline_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $name = $this->input('cutting_result_name');
            $totalCutting = (int) $this->input('total_cutting');

            if ($name && $totalCutting > 0) {
                $totalRemaining = \App\Models\CuttingResult::where('name', $name)->sum('remaining');

                if ($totalCutting > $totalRemaining) {
                    $validator->errors()->add('total_cutting', "Total distribution ({$totalCutting}) exceeds total remaining ({$totalRemaining}) for this cutting result group.");
                }
            }
        });
    }
}