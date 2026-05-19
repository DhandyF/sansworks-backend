<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PreOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'brand_id' => $this->brand_id,
            'article_id' => $this->article_id,
            'size_id' => $this->size_id,
            'pre_order_date' => $this->pre_order_date?->toIso8601String(),
            'deadline_date' => $this->deadline_date?->toIso8601String(),
            'total_pcs' => $this->total_pcs,
            'cut_qty' => $this->when($this->relationLoaded('cuttingResults'), fn () => (int) $this->cuttingResults->sum('total_cutting')),
            'remaining' => $this->when($this->relationLoaded('cuttingResults'), fn () => $this->total_pcs - (int) $this->cuttingResults->sum('total_cutting')),
            'deposited_qty' => $this->when($this->relationLoaded('cuttingResults'), fn () => (int) $this->cuttingResults->flatMap->distributions->flatMap->deposits->sum('total_sewing_result')),
            'cutting_results' => $this->when($this->relationLoaded('cuttingResults'), fn () => $this->cuttingResults->map(fn ($cr) => [
                'id' => $cr->id,
                'total_cutting' => (int) $cr->total_cutting,
                'cutting_date' => $cr->cutting_date?->toIso8601String(),
            ])->values()->all()),
            'shipments' => $this->when($this->relationLoaded('shipments'), fn () => $this->shipments->map(fn ($s) => [
                'id' => $s->id,
                'total_shipment' => (int) $s->total_shipment,
                'shipment_date' => $s->shipment_date?->toIso8601String(),
            ])->values()->all()),
            'brand' => new BrandResource($this->whenLoaded('brand')),
            'article' => new ArticleResource($this->whenLoaded('article')),
            'size' => new SizeResource($this->whenLoaded('size')),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}