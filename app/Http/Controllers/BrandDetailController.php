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

        $grouped = $preOrders->groupBy('name')->map(function ($group) {
            $first = $group->first();
            $totalPcs = $group->sum('total_pcs');
            $cutQty = $group->sum(fn ($po) => $po->cuttingResults->sum('total_cutting'));
            $totalDistributed = $group->sum(fn ($po) => $po->cuttingResults->flatMap->distributions->sum('total_cutting'));
            $totalDeposited = $group->sum(fn ($po) => $po->cuttingResults->flatMap->distributions->flatMap->deposits->sum('total_sewing_result'));

            $deadlineDate = $first->deadline_date?->format('Y-m-d');
            $today = now()->format('Y-m-d');

            $status = 'in_progress';
            if ($totalPcs > 0 && $totalDeposited >= $totalPcs) {
                $status = 'done';
            } elseif ($deadlineDate && $today > $deadlineDate && $totalDeposited < $totalPcs) {
                $status = 'overdue';
            }

            $completedDate = $group->max('completed_date');

            return [
                'id' => $first->id,
                'name' => $first->name,
                'pre_order_date' => $first->pre_order_date?->toIso8601String(),
                'deadline_date' => $first->deadline_date?->toIso8601String(),
                'completed_date' => $completedDate instanceof \Carbon\Carbon ? $completedDate->toIso8601String() : $completedDate,
                'total_pcs' => (int) $totalPcs,
                'cut_qty' => (int) $cutQty,
                'cutting_remaining' => (int) ($totalPcs - $cutQty),
                'distributed_qty' => (int) $totalDistributed,
                'deposited_qty' => (int) $totalDeposited,
                'status' => $status,
            ];
        })->values();

        $totalPcs = $preOrders->sum('total_pcs');
        $totalCutQty = $preOrders->sum(fn ($po) => $po->cuttingResults->sum('total_cutting'));
        $totalDistributed = $preOrders->sum(fn ($po) => $po->cuttingResults->flatMap->distributions->sum('total_cutting'));
        $totalDeposited = $preOrders->sum(fn ($po) => $po->cuttingResults->flatMap->distributions->flatMap->deposits->sum('total_sewing_result'));

        $doneCount = $grouped->where('status', 'done')->count();
        $inProgressCount = $grouped->where('status', 'in_progress')->count();
        $overdueCount = $grouped->where('status', 'overdue')->count();

        return response()->json([
            'brand' => [
                'id' => $brand->id,
                'name' => $brand->name,
                'phone' => $brand->phone,
                'address' => $brand->address,
                'status' => $brand->status,
            ],
            'summary' => [
                'total_pre_orders' => $grouped->count(),
                'total_pcs' => (int) $totalPcs,
                'total_cut_qty' => (int) $totalCutQty,
                'cutting_remaining' => (int) ($totalPcs - $totalCutQty),
                'total_distributed' => (int) $totalDistributed,
                'total_deposited' => (int) $totalDeposited,
                'done_count' => $doneCount,
                'in_progress_count' => $inProgressCount,
                'overdue_count' => $overdueCount,
            ],
            'pre_orders' => $grouped,
        ]);
    }
}