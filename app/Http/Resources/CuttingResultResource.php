<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CuttingResultResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'fabric_id' => $this->fabric_id,
            'brand_id' => $this->brand_id,
            'article_id' => $this->article_id,
            'size_id' => $this->size_id,
            'total_cutting' => $this->total_cutting,
            'total_distributed' => $this->total_distributed,
            'total_deposited' => $this->total_deposited,
            'remaining' => $this->remaining,
            'cutting_date' => $this->cutting_date->toISOString(),
            'batch_number' => $this->batch_number,
            'notes' => $this->notes,
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'deleted_at' => $this->deleted_at?->toISOString(),

            // Include relationships when loaded
            'fabric' => $this->whenLoaded('fabric'),
            'brand' => $this->whenLoaded('brand'),
            'article' => $this->whenLoaded('article'),
            'size' => $this->whenLoaded('size'),
            'created_by_user' => $this->whenLoaded('createdBy'),
            'updated_by_user' => $this->whenLoaded('updatedBy'),
        ];
    }

    /**
     * Get additional data that should be returned with the resource array.
     */
    public function with(Request $request): array
    {
        return [
            'success' => true,
        ];
    }
}
