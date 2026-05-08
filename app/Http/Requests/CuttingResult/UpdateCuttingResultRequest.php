<?php

namespace App\Http\Requests\CuttingResult;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCuttingResultRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'total_cutting' => ['sometimes', 'integer', 'min:1'],
            'cutting_date' => ['sometimes', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }
}