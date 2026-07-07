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
            'status' => $this->when($this->relationLoaded('shipments'), function () {
                $totalShipped = $this->shipments->sum('total_shipment');
                if ($this->total_pcs > 0 && $totalShipped >= $this->total_pcs) {
                    return 'done';
                }
                $deadlineDate = $this->deadline_date?->format('Y-m-d');
                $today = now()->format('Y-m-d');
                if ($deadlineDate && $today > $deadlineDate && $totalShipped < $this->total_pcs) {
                    return 'overdue';
                }
                return 'in_progress';
            }),
            'completed_date' => $this->completed_date?->toIso8601String(),
            'cut_qty' => $this->when($this->relationLoaded('cuttingResults'), fn () => (int) min($this->total_pcs, $this->cuttingResults->sum('total_cutting'))),
            'excess_cutting' => $this->when($this->relationLoaded('cuttingResults'), fn () => (float) $this->cuttingResults->sum('excess_cutting')),
            'remaining' => $this->when($this->relationLoaded('cuttingResults'), fn () => max(0, $this->total_pcs - (int) $this->cuttingResults->sum('total_cutting'))),
            'deposited_qty' => $this->when($this->relationLoaded('cuttingResults'), fn () => (int) $this->cuttingResults->flatMap->distributions->flatMap->deposits->sum('total_sewing_result')),
            'cutting_results' => $this->when($this->relationLoaded('cuttingResults'), fn () => $this->cuttingResults->map(fn ($cr) => [
                'id' => $cr->id,
                'total_cutting' => (int) $cr->total_cutting,
                'excess_cutting' => (float) ($cr->excess_cutting ?? 0),
                'remaining' => (int) ($cr->remaining ?? 0),
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
            'deleted_at' => $this->deleted_at?->toIso8601String(),
        ];
    }
}