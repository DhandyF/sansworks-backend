<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CuttingResultResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'pre_order_id' => $this->pre_order_id,
            'brand_id' => $this->brand_id,
            'article_id' => $this->article_id,
            'size_id' => $this->size_id,
            'total_cutting' => $this->total_cutting,
            'excess_cutting' => $this->excess_cutting ?? 0,
            'remaining' => $this->remaining,
            'cutting_date' => $this->cutting_date?->toIso8601String(),
            'notes' => $this->notes,
            'pre_order' => new PreOrderResource($this->whenLoaded('preOrder')),
            'brand' => new BrandResource($this->whenLoaded('brand')),
            'article' => new ArticleResource($this->whenLoaded('article')),
            'size' => new SizeResource($this->whenLoaded('size')),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}