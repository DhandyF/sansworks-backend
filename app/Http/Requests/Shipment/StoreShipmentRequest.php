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

}