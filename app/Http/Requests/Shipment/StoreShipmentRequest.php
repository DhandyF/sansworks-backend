<?php

namespace App\Http\Requests\Shipment;

use Illuminate\Foundation\Http\FormRequest;

class StoreShipmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'pre_order_id' => ['required', 'exists:pre_orders,id'],
            'shipment_date' => ['required', 'date'],
            'total_shipment' => ['required', 'integer', 'min:1'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $preOrderId = $this->input('pre_order_id');
            $totalShipment = (int) $this->input('total_shipment');

            if ($preOrderId && $totalShipment > 0) {
                $preOrder = \App\Models\PreOrder::find($preOrderId);
                if ($preOrder) {
                    $shipped = \App\Models\Shipment::where('pre_order_id', $preOrderId)->sum('total_shipment');
                    $available = $preOrder->total_pcs - (int) $shipped;

                    if ($totalShipment > $available) {
                        $validator->errors()->add('total_shipment', "Total shipment ({$totalShipment}) exceeds available quantity ({$available}) for this pre-order item.");
                    }
                }
            }
        });
    }
}