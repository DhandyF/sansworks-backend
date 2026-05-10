<?php

namespace App\Http\Controllers;

use App\Models\Tailor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TailorDetailController extends Controller
{
    public function detailStats(Request $request, string $id): JsonResponse
    {
        $tailor = Tailor::findOrFail($id);

        $perPage = $request->integer('per_page', 15);
        $brandFilter = $request->query('brand_filter');
        $search = $request->query('search');
        $statusFilter = $request->query('status_filter');

        $allDistributions = $tailor->cuttingDistributions()
            ->with(['cuttingResult.preOrder', 'brand', 'article', 'size', 'deposits'])
            ->orderBy('created_at', 'desc')
            ->get();

        $totalDistributed = $allDistributions->sum('total_cutting');
        $totalDeposited = $allDistributions->flatMap->deposits->sum('total_sewing_result');
        $totalPrice = $allDistributions->flatMap->deposits->sum('total_price');
        $totalRemaining = $totalDistributed - $totalDeposited;

        $allDistributionData = $allDistributions->map(function ($dist) {
            return $this->mapDistribution($dist);
        });

        $doneCount = $allDistributionData->where('status', 'done')->count();
        $inProgressCount = $allDistributionData->where('status', 'in_progress')->count();
        $overdueCount = $allDistributionData->where('status', 'overdue')->count();

        $brands = $allDistributionData
            ->pluck('brand')
            ->filter()
            ->unique('id')
            ->values();

        $filtered = $allDistributionData;

        if ($brandFilter) {
            $filtered = $filtered->filter(fn($d) => ($d['brand']['id'] ?? null) === $brandFilter);
        }

        if ($search) {
            $q = strtolower($search);
            $filtered = $filtered->filter(function ($d) use ($q) {
                return str_contains(strtolower($d['pre_order']['name'] ?? ''), $q)
                    || str_contains(strtolower($d['name']), $q);
            });
        }

        if ($statusFilter) {
            $filtered = $filtered->filter(fn($d) => $d['status'] === $statusFilter);
        }

        $page = $request->integer('page', 1);
        $total = $filtered->count();
        $paged = $filtered->forPage($page, $perPage);

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
                'done_count' => $doneCount,
                'in_progress_count' => $inProgressCount,
                'overdue_count' => $overdueCount,
            ],
            'brands' => $brands,
            'distributions' => [
                'data' => $paged->values(),
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => max(1, (int) ceil($total / $perPage)),
            ],
        ]);
    }

    private function mapDistribution($dist): array
    {
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
    }
}