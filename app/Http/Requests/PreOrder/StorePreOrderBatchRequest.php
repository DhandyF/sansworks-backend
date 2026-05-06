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
            'name' => ['required', 'string'],
            'pre_order_date' => ['required', 'date'],
            'deadline_date' => ['required', 'date'],
            'articles' => ['required', 'array', 'min:1'],
            'articles.*.article_id' => ['required', 'exists:articles,id'],
            'articles.*.sizes' => ['required', 'array', 'min:1'],
            'articles.*.sizes.*.size_id' => ['required', 'exists:sizes,id'],
            'articles.*.sizes.*.total_pcs' => ['required', 'integer', 'min:1'],
        ];
    }
}