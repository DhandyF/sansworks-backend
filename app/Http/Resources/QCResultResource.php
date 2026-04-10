<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QCResultResource extends JsonResource
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
            'deposit_cutting_result_id' => $this->deposit_cutting_result_id,
            'tailor_id' => $this->tailor_id,
            'brand_id' => $this->brand_id,
            'article_id' => $this->article_id,
            'size_id' => $this->size_id,
            'total_products' => $this->total_products,
            'total_to_repair' => $this->total_to_repair,
            'qc_date' => $this->qc_date->toISOString(),
            'qc_by' => $this->qc_by,
            'defect_details' => $this->defect_details,
            'notes' => $this->notes,
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'deleted_at' => $this->deleted_at?->toISOString(),

            // Computed fields
            'pass_rate' => $this->total_products > 0
                ? round((($this->total_products - $this->total_to_repair) / $this->total_products) * 100, 2)
                : 100,

            // Include relationships when loaded
            'deposit_cutting_result' => $this->whenLoaded('depositCuttingResult'),
            'tailor' => $this->whenLoaded('tailor'),
            'brand' => $this->whenLoaded('brand'),
            'article' => $this->whenLoaded('article'),
            'size' => $this->whenLoaded('size'),
            'qc_by_user' => $this->whenLoaded('qcBy'),
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
