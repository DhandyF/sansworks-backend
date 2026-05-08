<?php

namespace App\Http\Requests\CuttingDistribution;

use Illuminate\Foundation\Http\FormRequest;

class StoreCuttingDistributionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cutting_result_id' => ['required', 'exists:cutting_results,id'],
            'tailor_id' => ['required', 'exists:tailors,id'],
            'total_cutting' => ['required', 'integer', 'min:1'],
            'taken_date' => ['required', 'date'],
            'deadline_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }
}