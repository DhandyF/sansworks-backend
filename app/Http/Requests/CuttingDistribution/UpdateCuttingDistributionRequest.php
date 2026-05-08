<?php

namespace App\Http\Requests\CuttingDistribution;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCuttingDistributionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'total_cutting' => ['sometimes', 'integer', 'min:1'],
            'taken_date' => ['sometimes', 'date'],
            'deadline_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }
}