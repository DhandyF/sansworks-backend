<?php

namespace App\Http\Controllers;

use App\Models\Tailor;
use Illuminate\Http\JsonResponse;

class TailorDetailController extends Controller
{
    public function detailStats(string $id): JsonResponse
    {
        $tailor = Tailor::findOrFail($id);

        $distributions = $tailor->cuttingDistributions()
            ->with(['cuttingResult.preOrder', 'brand', 'article', 'size', 'deposits'])
            ->orderBy('created_at', 'desc')
            ->get();

        $totalDistributed = $distributions->sum('total_cutting');
        $totalDeposited = $distributions->flatMap->deposits->sum('total_sewing_result');
        $totalPrice = $distributions->flatMap->deposits->sum('total_price');
        $totalRemaining = $totalDistributed - $totalDeposited;

        $distributionData = $distributions->map(function ($dist) {
            $deposited = $dist->deposits->sum('total_sewing_result');
            $depositRemaining = $dist->total_cutting - $deposited;
            $distPrice = $dist->deposits->sum('total_price');

            $distDeadline = $dist->deadline_date?->format('Y-m-d');
            $today = now()->format('Y-m-d');

            $distStatus = 'in_progress';
            if ($dist->total_cutting > 0 && $deposited >= $dist->total_cutting) {
                $distStatus = 'done';
            } elseif ($distDeadline && $today > $distDeadline && $deposited < $dist->total_cutting) {
                $distStatus = 'overdue';
            }

            return [
                'id' => $dist->id,
                'name' => $dist->name,
                'brand' => $dist->brand ? ['id' => $dist->brand->id, 'name' => $dist->brand->name] : null,
                'article' => $dist->article ? ['id' => $dist->article->id, 'name' => $dist->article->name] : null,
                'size' => $dist->size ? ['id' => $dist->size->id, 'abbreviation' => $dist->size->abbreviation] : null,
                'pre_order' => $dist->cuttingResult && $dist->cuttingResult->preOrder ? ['id' => $dist->cuttingResult->preOrder->id, 'name' => $dist->cuttingResult->preOrder->name] : null,
                'total_cutting' => (int) $dist->total_cutting,
                'deposit_remaining' => (int) $depositRemaining,
                'total_price' => (float) $distPrice,
                'taken_date' => $dist->taken_date?->toIso8601String(),
                'deadline_date' => $dist->deadline_date?->toIso8601String(),
                'status' => $distStatus,
                'deposits' => $dist->deposits->map(function ($dep) {
                    return [
                        'id' => $dep->id,
                        'name' => $dep->name,
                        'total_sewing_result' => (int) $dep->total_sewing_result,
                        'cutting_price_per_pcs' => (float) $dep->cutting_price_per_pcs,
                        'total_price' => (float) $dep->total_price,
                        'deposit_date' => $dep->deposit_date?->toIso8601String(),
                        'status' => $dep->status,
                    ];
                })->values()->all(),
            ];
        });

        return response()->json([
            'tailor' => [
                'id' => $tailor->id,
                'name' => $tailor->name,
                'phone' => $tailor->phone,
                'address' => $tailor->address,
                'status' => $tailor->status,
            ],
            'summary' => [
                'total_distributed' => (int) $totalDistributed,
                'total_deposited' => (int) $totalDeposited,
                'total_remaining' => (int) $totalRemaining,
                'total_price' => (float) $totalPrice,
                'done_count' => $distributionData->where('status', 'done')->count(),
                'in_progress_count' => $distributionData->where('status', 'in_progress')->count(),
                'overdue_count' => $distributionData->where('status', 'overdue')->count(),
            ],
            'distributions' => $distributionData,
        ]);
    }
}