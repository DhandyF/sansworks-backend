<?php

namespace App\Http\Requests\Repair;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRepairRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tailor_id' => ['sometimes', 'exists:tailors,id'],
            'brand_id' => ['sometimes', 'exists:brands,id'],
            'article_id' => ['sometimes', 'exists:articles,id'],
            'name' => ['sometimes', 'string'],
            'total_repair' => ['sometimes', 'integer', 'min:1'],
            'sewing_price' => ['sometimes', 'numeric', 'min:0'],
            'taken_date' => ['sometimes', 'date'],
            'deadline_date' => ['sometimes', 'date'],
            'status' => ['sometimes', 'string', 'in:in_progress,done,overdue'],
        ];
    }
}