<?php

namespace App\Http\Requests\Shipment;

use Illuminate\Foundation\Http\FormRequest;

class UpdateShipmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'shipment_date' => ['sometimes', 'date'],
            'total_shipment' => ['sometimes', 'integer', 'min:1'],
        ];
    }
}