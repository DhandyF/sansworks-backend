<?php

namespace App\Http\Requests\PreOrder;

use Illuminate\Foundation\Http\FormRequest;

class StorePreOrderBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'brand_id' => ['required', 'exists:brands,id'],
            'article_id' => ['required', 'exists:articles,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.size_id' => ['required', 'exists:sizes,id'],
            'items.*.total_pcs' => ['required', 'integer', 'min:1'],
        ];
    }
}