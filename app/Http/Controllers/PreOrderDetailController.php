<?php

namespace App\Http\Controllers;

use App\Models\PreOrder;
use Illuminate\Http\JsonResponse;

class PreOrderDetailController extends Controller
{
    public function detailStats(string $id): JsonResponse
    {
        $preOrder = PreOrder::findOrFail($id);

        $group = PreOrder::with(['brand', 'article', 'size', 'cuttingResults.distributions.deposits', 'shipments'])
            ->where('name', $preOrder->name)
            ->orderBy('created_at', 'desc')
            ->get();

        $totalPcs = $group->sum('total_pcs');
        $cutQty = $group->sum(fn ($po) => $po->cuttingResults->sum('total_cutting'));
        $totalDistributed = $group->sum(fn ($po) => $po->cuttingResults->flatMap->distributions->sum('total_cutting'));
        $totalDeposited = $group->sum(fn ($po) => $po->cuttingResults->flatMap->distributions->flatMap->deposits->sum('total_sewing_result'));
        $totalShipped = $group->sum(fn ($po) => $po->shipments->sum('total_shipment'));

        $deadlineDate = $preOrder->deadline_date?->format('Y-m-d');
        $today = now()->format('Y-m-d');

        $status = 'in_progress';
        if ($totalPcs > 0 && $totalShipped >= $totalPcs) {
            $status = 'done';
        } elseif ($deadlineDate && $today > $deadlineDate && $totalShipped < $totalPcs) {
            $status = 'overdue';
        }

        $entries = $group->map(function ($po) {
            $poCutQty = $po->cuttingResults->sum('total_cutting');
            $poDistributed = $po->cuttingResults->flatMap->distributions->sum('total_cutting');
            $poDeposited = $po->cuttingResults->flatMap->distributions->flatMap->deposits->sum('total_sewing_result');
            $poShipped = $po->shipments->sum('total_shipment');

            $poDeadline = $po->deadline_date?->format('Y-m-d');
            $today = now()->format('Y-m-d');

            $poStatus = 'in_progress';
            if ($po->total_pcs > 0 && $poShipped >= $po->total_pcs) {
                $poStatus = 'done';
            } elseif ($poDeadline && $today > $poDeadline && $poShipped < $po->total_pcs) {
                $poStatus = 'overdue';
            }

            $distributions = $po->cuttingResults->flatMap->distributions->map(function ($dist) {
                $deposited = $dist->deposits->sum('total_sewing_result');
                $depositRemaining = $dist->total_cutting - $deposited;

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
                    'tailor' => $dist->tailor ? ['id' => $dist->tailor->id, 'name' => $dist->tailor->name] : null,
                    'total_cutting' => (int) $dist->total_cutting,
                    'deposit_remaining' => (int) $depositRemaining,
                    'status' => $distStatus,
                    'deposits' => $dist->deposits->map(function ($dep) {
                        return [
                            'id' => $dep->id,
                            'name' => $dep->name,
                            'total_sewing_result' => (int) $dep->total_sewing_result,
                            'deposit_date' => $dep->deposit_date?->toIso8601String(),
                            'status' => $dep->status,
                        ];
                    })->values()->all(),
                ];
            })->values()->all();

            return [
                'id' => $po->id,
                'name' => $po->name,
                'article' => $po->article ? ['id' => $po->article->id, 'name' => $po->article->name] : null,
                'size' => $po->size ? ['id' => $po->size->id, 'abbreviation' => $po->size->abbreviation] : null,
                'total_pcs' => $po->total_pcs,
                'cut_qty' => (int) $poCutQty,
                'cutting_remaining' => (int) ($po->total_pcs - $poCutQty),
                'distributed_qty' => (int) $poDistributed,
                'deposited_qty' => (int) $poDeposited,
                'shipped_qty' => (int) $po->shipments->sum('total_shipment'),
                'deadline_date' => $po->deadline_date?->toIso8601String(),
                'status' => $poStatus,
                'distributions' => $distributions,
            ];
        });

        $doneCount = $entries->where('status', 'done')->count();
        $inProgressCount = $entries->where('status', 'in_progress')->count();
        $overdueCount = $entries->where('status', 'overdue')->count();

        return response()->json([
            'pre_order' => [
                'id' => $preOrder->id,
                'name' => $preOrder->name,
                'brand' => $preOrder->brand ? ['id' => $preOrder->brand->id, 'name' => $preOrder->brand->name] : null,
                'pre_order_date' => $preOrder->pre_order_date?->toIso8601String(),
                'deadline_date' => $preOrder->deadline_date?->toIso8601String(),
            ],
            'summary' => [
                'total_pcs' => (int) $totalPcs,
                'cut_qty' => (int) $cutQty,
                'cutting_remaining' => (int) ($totalPcs - $cutQty),
                'distributed_qty' => (int) $totalDistributed,
                'deposited_qty' => (int) $totalDeposited,
                'shipped_qty' => (int) $totalShipped,
                'status' => $status,
                'done_count' => $doneCount,
                'in_progress_count' => $inProgressCount,
                'overdue_count' => $overdueCount,
            ],
            'entries' => $entries,
        ]);
    }
}