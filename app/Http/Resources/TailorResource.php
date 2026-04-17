<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TailorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Get cutting distributions with their deposits and QC
        $distributions = $this->whenLoaded('cuttingDistributions', function () {
            return $this->cuttingDistributions->map(function ($dist) {
                $deposit = $dist->depositCuttingResults->first();
                
                // Get QC result for this distribution (via deposit or direct)
                $qcResult = $dist->depositCuttingResults
                    ->flatMap->qcResults
                    ->first();
                
                $totalDistributed = $dist->total_cutting;
                $totalDeposited = $deposit ? $deposit->total_sewing_result : 0;
                $sewingLeft = $totalDistributed - $totalDeposited;
                
                // Calculate price: total_cutting * article.sewing_price
                $sewingPrice = $dist->article ? ($dist->article->sewing_price ?? 0) : 0;
                $totalPrice = $totalDistributed * floatval($sewingPrice);
                
                // QC info
                $totalProducts = $qcResult ? $qcResult->total_products : 0;
                $totalToRepair = $qcResult ? $qcResult->total_to_repair : 0;
                $defectRate = $qcResult ? $qcResult->defect_rate : 0;
                $qcPassed = $totalProducts > 0 ? ($totalProducts - $totalToRepair) : 0;
                
                return [
                    'id' => $dist->id,
                    'distribution_number' => $dist->distribution_number,
                    'total_cutting' => $totalDistributed,
                    'total_deposited' => $totalDeposited,
                    'sewing_left' => $sewingLeft,
                    'sewing_price' => floatval($sewingPrice),
                    'total_price' => $totalPrice,
                    'taken_date' => $dist->taken_date?->toISOString(),
                    'deadline_date' => $dist->deadline_date?->toISOString(),
                    'status' => $dist->status,
                    // Article info
                    'article' => $dist->article ? [
                        'id' => $dist->article->id,
                        'name' => $dist->article->name,
                        'sewing_price' => floatval($dist->article->sewing_price),
                    ] : null,
                    // Brand info
                    'brand' => $dist->brand ? [
                        'id' => $dist->brand->id,
                        'name' => $dist->brand->name,
                    ] : null,
                    // Size info
                    'size' => $dist->size ? [
                        'id' => $dist->size->id,
                        'name' => $dist->size->name,
                    ] : null,
                    // Deposit info
                    'deposit' => $deposit ? [
                        'id' => $deposit->id,
                        'total_sewing_result' => $deposit->total_sewing_result,
                        'deposit_date' => $deposit->deposit_date?->toISOString(),
                        'status' => $deposit->status,
                        'quality_notes' => $deposit->quality_notes,
                    ] : null,
                    // QC info
                    'qc' => $qcResult ? [
                        'id' => $qcResult->id,
                        'qc_date' => $qcResult->qc_date?->toISOString(),
                        'total_products' => $totalProducts,
                        'total_to_repair' => $totalToRepair,
                        'defect_rate' => $defectRate,
                        'qc_passed' => $qcPassed,
                        'qc_by' => $qcResult->qcBy ? $qcResult->qcBy->name : null,
                    ] : null,
                ];
            });
        }, function () {
            return [];
        });

        // Calculate totals from distributions
        $totalDistributed = $distributions ? $distributions->sum('total_cutting') : 0;
        $totalDeposited = $distributions ? $distributions->sum('total_deposited') : 0;
        $totalSewingLeft = $distributions ? $distributions->sum('sewing_left') : 0;
        $totalPrice = $distributions ? $distributions->sum('total_price') : 0;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'phone' => $this->phone,
            'address' => $this->address,
            'code' => $this->code,
            'is_active' => $this->is_active,
            'notes' => $this->notes,
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'deleted_at' => $this->deleted_at?->toISOString(),

            // Summary totals
            'summary' => [
                'total_distributions' => $distributions ? $distributions->count() : 0,
                'total_cutting' => $totalDistributed,
                'total_deposited' => $totalDeposited,
                'total_sewing_left' => $totalSewingLeft,
                'total_price' => $totalPrice,
            ],

            // Detailed distributions with deposit and QC info
            'distributions' => $distributions,
        ];
    }

    public function with(Request $request): array
    {
        return [
            'success' => true,
        ];
    }
}