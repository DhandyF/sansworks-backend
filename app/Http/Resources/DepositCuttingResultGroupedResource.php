<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DepositCuttingResultGroupedResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource['id'],
            'tailor_id' => $this->resource['tailor_id'],
            'brand_id' => $this->resource['brand_id'],
            'article_id' => $this->resource['article_id'],
            'size_id' => $this->resource['size_id'],
            'tailor' => new TailorResource($this->resource['tailor']),
            'brand' => new BrandResource($this->resource['brand']),
            'article' => new ArticleResource($this->resource['article']),
            'size' => new SizeResource($this->resource['size']),
            'total_sewing_result' => $this->resource['total_sewing_result'],
            'total_price' => $this->resource['total_price'],
            'has_overdue' => $this->resource['has_overdue'],
            'total_distributed' => $this->resource['total_distributed'],
            'total_deposit_remaining' => $this->resource['total_deposit_remaining'],
            'deposit_dates' => $this->resource['deposit_dates'],
            'entries' => DepositCuttingResultResource::collection($this->resource['entries']),
        ];
    }
}
