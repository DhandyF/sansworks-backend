<?php

namespace App\Http\Requests\CuttingResult;

use Illuminate\Foundation\Http\FormRequest;

class StoreCuttingResultRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'pre_order_id' => ['required', 'exists:pre_orders,id'],
            'article_id' => ['required', 'exists:articles,id'],
            'size_id' => ['required', 'exists:sizes,id'],
            'total_cutting' => ['required', 'integer', 'min:1'],
            'cutting_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $preOrderId = $this->input('pre_order_id');
            $totalCutting = (int) $this->input('total_cutting');

            if ($preOrderId && $totalCutting > 0) {
                $preOrder = \App\Models\PreOrder::find($preOrderId);
                if ($preOrder) {
                    $cutQty = \App\Models\CuttingResult::where('pre_order_id', $preOrderId)->sum('total_cutting');
                    $available = $preOrder->total_pcs - (int) $cutQty;

                    if ($totalCutting > $available) {
                        $validator->errors()->add('total_cutting', "Total cutting ({$totalCutting}) exceeds available quantity ({$available}) for this pre-order item.");
                    }
                }
            }
        });
    }
}