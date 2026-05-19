<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShipmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'pre_order_id' => $this->pre_order_id,
            'shipment_date' => $this->shipment_date?->toIso8601String(),
            'total_shipment' => $this->total_shipment,
            'pre_order' => new PreOrderResource($this->whenLoaded('preOrder')),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}