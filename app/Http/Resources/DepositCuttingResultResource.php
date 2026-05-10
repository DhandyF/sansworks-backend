<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DepositCuttingResultResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'cutting_distribution_id' => $this->cutting_distribution_id,
            'tailor_id' => $this->tailor_id,
            'brand_id' => $this->brand_id,
            'article_id' => $this->article_id,
            'size_id' => $this->size_id,
            'total_sewing_result' => $this->total_sewing_result,
            'cutting_price_per_pcs' => $this->cutting_price_per_pcs,
            'total_price' => $this->total_price,
            'deposit_date' => $this->deposit_date?->toIso8601String(),
            'status' => $this->status,
            'quality_notes' => $this->quality_notes,
            'notes' => $this->notes,
            'cutting_distribution' => new CuttingDistributionResource($this->whenLoaded('cuttingDistribution')),
            'tailor' => new TailorResource($this->whenLoaded('tailor')),
            'brand' => new BrandResource($this->whenLoaded('brand')),
            'article' => new ArticleResource($this->whenLoaded('article')),
            'size' => new SizeResource($this->whenLoaded('size')),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}