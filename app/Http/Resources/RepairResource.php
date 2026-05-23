<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RepairResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tailor_id' => $this->tailor_id,
            'brand_id' => $this->brand_id,
            'article_id' => $this->article_id,
            'name' => $this->name,
            'total_repair' => $this->total_repair,
            'sewing_price' => (float) $this->sewing_price,
            'taken_date' => $this->taken_date?->toIso8601String(),
            'deadline_date' => $this->deadline_date?->toIso8601String(),
            'status' => $this->status,
            'total_deposited' => $this->total_deposited,
            'remaining' => $this->remaining,
            'tailor' => new TailorResource($this->whenLoaded('tailor')),
            'brand' => new BrandResource($this->whenLoaded('brand')),
            'article' => new ArticleResource($this->whenLoaded('article')),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}