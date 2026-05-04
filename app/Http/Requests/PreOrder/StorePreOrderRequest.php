<?php

namespace App\Http\Requests\PreOrder;

use Illuminate\Foundation\Http\FormRequest;

class StorePreOrderRequest extends FormRequest
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
            'size_id' => ['required', 'exists:sizes,id'],
            'total_pcs' => ['required', 'integer', 'min:1'],
        ];
    }
}