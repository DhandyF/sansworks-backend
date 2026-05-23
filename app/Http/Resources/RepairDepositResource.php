<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RepairDepositResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $repair = $this->whenLoaded('repair');
        $daysDelay = 0;
        
        if ($repair && $this->deposit_date && $repair->deadline_date) {
            $depositDate = \Carbon\Carbon::parse($this->deposit_date);
            $deadlineDate = \Carbon\Carbon::parse($repair->deadline_date);
            if ($depositDate->gt($deadlineDate)) {
                $daysDelay = $depositDate->diffInDays($deadlineDate, true);
            }
        }

        return [
            'id' => $this->id,
            'repair_id' => $this->repair_id,
            'tailor_id' => $this->tailor_id,
            'total_deposit' => $this->total_deposit,
            'deposit_date' => $this->deposit_date?->toIso8601String(),
            'charge_amount' => (float) $this->charge_amount,
            'charge_percent' => $this->charge_percent,
            'days_delay' => $daysDelay,
            'repair' => $repair ? [
                'id' => $repair->id,
                'name' => $repair->name,
                'total_repair' => $repair->total_repair,
                'sewing_price' => (float) $repair->sewing_price,
                'remaining' => $repair->remaining,
                'brand' => new BrandResource($repair->brand),
                'article' => new ArticleResource($repair->article),
            ] : null,
            'tailor' => new TailorResource($this->whenLoaded('tailor')),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}