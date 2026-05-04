<?php

namespace App\Http\Requests\PreOrder;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'brand_id' => ['sometimes', 'exists:brands,id'],
            'article_id' => ['sometimes', 'exists:articles,id'],
            'size_id' => ['sometimes', 'exists:sizes,id'],
            'total_pcs' => ['sometimes', 'integer', 'min:1'],
        ];
    }
}