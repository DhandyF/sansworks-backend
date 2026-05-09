<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BrandDetailController extends Controller
{
    public function productionStats(Request $request, string $id): JsonResponse
    {
        $brand = Brand::findOrFail($id);

        $preOrders = \App\Models\PreOrder::with(['brand', 'article', 'size', 'cuttingResults.distributions.deposits'])
            ->where('brand_id', $id)
            ->orderBy('created_at', 'desc')
            ->get();

        $stats = $preOrders->map(function ($po) {
            $totalPcs = $po->total_pcs;
            $cutQty = $po->cuttingResults->sum('total_cutting');
            $remaining = $totalPcs - $cutQty;

            $totalCuttingResultQty = $po->cuttingResults->sum('total_cutting');
            $totalDistributed = $po->cuttingResults->flatMap->distributions->sum('total_cutting');
            $totalDeposited = $po->cuttingResults->flatMap->distributions->flatMap->deposits->sum('total_sewing_result');

            $deadlineDate = $po->deadline_date?->format('Y-m-d');
            $today = now()->format('Y-m-d');

            $status = 'in_progress';
            if ($totalPcs > 0 && $totalDeposited >= $totalPcs) {
                $status = 'done';
            } elseif ($deadlineDate && $today > $deadlineDate && $totalDeposited < $totalPcs) {
                $status = 'overdue';
            }

            return [
                'id' => $po->id,
                'name' => $po->name,
                'pre_order_date' => $po->pre_order_date?->toIso8601String(),
                'deadline_date' => $po->deadline_date?->toIso8601String(),
                'article' => $po->article ? ['id' => $po->article->id, 'name' => $po->article->name] : null,
                'size' => $po->size ? ['id' => $po->size->id, 'abbreviation' => $po->size->abbreviation] : null,
                'total_pcs' => $totalPcs,
                'cut_qty' => (int) $cutQty,
                'cutting_remaining' => $remaining,
                'distributed_qty' => (int) $totalDistributed,
                'deposited_qty' => (int) $totalDeposited,
                'status' => $status,
            ];
        });

        $totalPreOrders = $preOrders->count();
        $totalPcs = $preOrders->sum('total_pcs');
        $totalCutQty = $preOrders->sum(fn ($po) => $po->cuttingResults->sum('total_cutting'));
        $totalDistributed = $preOrders->sum(fn ($po) => $po->cuttingResults->flatMap->distributions->sum('total_cutting'));
        $totalDeposited = $preOrders->sum(fn ($po) => $po->cuttingResults->flatMap->distributions->flatMap->deposits->sum('total_sewing_result'));

        $doneCount = $stats->where('status', 'done')->count();
        $inProgressCount = $stats->where('status', 'in_progress')->count();
        $overdueCount = $stats->where('status', 'overdue')->count();

        return response()->json([
            'brand' => [
                'id' => $brand->id,
                'name' => $brand->name,
                'phone' => $brand->phone,
                'address' => $brand->address,
                'status' => $brand->status,
            ],
            'summary' => [
                'total_pre_orders' => $totalPreOrders,
                'total_pcs' => $totalPcs,
                'total_cut_qty' => (int) $totalCutQty,
                'cutting_remaining' => $totalPcs - (int) $totalCutQty,
                'total_distributed' => (int) $totalDistributed,
                'total_deposited' => (int) $totalDeposited,
                'done_count' => $doneCount,
                'in_progress_count' => $inProgressCount,
                'overdue_count' => $overdueCount,
            ],
            'pre_orders' => $stats,
        ]);
    }
}