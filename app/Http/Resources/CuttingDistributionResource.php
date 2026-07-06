<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CuttingDistributionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'cutting_result_id' => $this->cutting_result_id,
            'tailor_id' => $this->tailor_id,
            'brand_id' => $this->brand_id,
            'article_id' => $this->article_id,
            'size_id' => $this->size_id,
            'total_cutting' => $this->total_cutting,
            'deposit_remaining' => $this->total_cutting - (int) ($this->relationLoaded('deposits') ? $this->deposits->sum('total_sewing_result') : $this->deposits()->sum('total_sewing_result')),
            'taken_date' => $this->taken_date?->toIso8601String(),
            'deadline_date' => $this->deadline_date?->toIso8601String(),
            'notes' => $this->notes,
            'cutting_result' => new CuttingResultResource($this->whenLoaded('cuttingResult')),
            'tailor' => new TailorResource($this->whenLoaded('tailor')),
            'brand' => new BrandResource($this->whenLoaded('brand')),
            'article' => new ArticleResource($this->whenLoaded('article')),
            'size' => new SizeResource($this->whenLoaded('size')),
            'deleted_at' => $this->deleted_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}