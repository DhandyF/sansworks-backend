<?php

namespace App\Http\Requests\Repair;

use Illuminate\Foundation\Http\FormRequest;

class StoreRepairRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tailor_id' => ['required', 'exists:tailors,id'],
            'brand_id' => ['required', 'exists:brands,id'],
            'article_id' => ['required', 'exists:articles,id'],
            'name' => ['required', 'string'],
            'total_repair' => ['required', 'integer', 'min:1'],
            'sewing_price' => ['required', 'numeric', 'min:0'],
            'taken_date' => ['required', 'date'],
            'deadline_date' => ['required', 'date', 'after_or_equal:taken_date'],
        ];
    }
}