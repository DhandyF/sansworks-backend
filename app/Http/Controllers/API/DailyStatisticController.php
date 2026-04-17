<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Concerns\HasPagination;
use App\Models\DailyStatistic;
use App\Models\CuttingResult;
use App\Models\CuttingDistribution;
use App\Models\DepositCuttingResult;
use App\Models\QCResult;
use App\Models\RepairDistribution;
use App\Models\DepositRepairResult;
use App\Jobs\CalculateDailyStatistics;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DailyStatisticController extends Controller
{
    use HasPagination;

    public function index(Request $request): JsonResponse
    {
        $perPage = $this->getPerPage($request);

        $query = DailyStatistic::with(['createdBy', 'updatedBy']);

        if ($request->has('from_date') && $request->has('to_date')) {
            $query->whereBetween('statistic_date', [$request->from_date, $request->to_date]);
        }

        $query->orderBy('statistic_date', 'desc');

        if ($perPage === 'all') {
            $items = $query->get();
            return response()->json([
                'success' => true,
                'data' => $items,
            ]);
        }

        $result = $query->paginate($perPage);
        return $this->paginatedResponse($result);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'statistic_date' => 'required|date|unique:daily_statistics,statistic_date',
        ]);

        CalculateDailyStatistics::dispatch($validated['statistic_date']);

        return response()->json([
            'success' => true,
            'message' => 'Daily statistics calculation job dispatched',
        ], 202);
    }

    public function show(DailyStatistic $dailyStatistic): JsonResponse
    {
        $dailyStatistic->load(['createdBy', 'updatedBy']);

        return response()->json([
            'success' => true,
            'data' => $dailyStatistic
        ]);
    }

    public function update(Request $request, DailyStatistic $dailyStatistic): JsonResponse
    {
        $validated = $request->validate([
            'total_fabric_input' => 'nullable|numeric|min:0',
            'total_fabric_cost' => 'nullable|numeric|min:0',
            'total_cutting_result' => 'nullable|integer|min:0',
            'total_cutting_distribution' => 'nullable|integer|min:0',
            'total_deposit_cutting' => 'nullable|integer|min:0',
            'total_sewing_price' => 'nullable|numeric|min:0',
            'total_qc_result' => 'nullable|integer|min:0',
            'total_qc_to_repair' => 'nullable|integer|min:0',
            'total_repair_distribution' => 'nullable|integer|min:0',
            'total_deposit_repair' => 'nullable|integer|min:0',
            'active_tailors' => 'nullable|integer|min:0',
            'active_brands' => 'nullable|integer|min:0',
            'completed_orders' => 'nullable|integer|min:0',
            'overdue_orders' => 'nullable|integer|min:0',
            'completion_rate' => 'nullable|numeric|min:0|max:100',
            'defect_rate' => 'nullable|numeric|min:0|max:100',
        ]);

        $validated['updated_by'] = auth()->id() ?? 1;

        $dailyStatistic->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Daily statistic updated successfully',
            'data' => $dailyStatistic->fresh()->load(['createdBy', 'updatedBy'])
        ]);
    }

    public function destroy(DailyStatistic $dailyStatistic): JsonResponse
    {
        $dailyStatistic->delete();

        return response()->json([
            'success' => true,
            'message' => 'Daily statistic deleted successfully'
        ]);
    }

    public function recalculate(Request $request, DailyStatistic $dailyStatistic): JsonResponse
    {
        CalculateDailyStatistics::dispatch($dailyStatistic->statistic_date);

        return response()->json([
            'success' => true,
            'message' => 'Daily statistics recalculation job dispatched',
        ], 202);
    }

    public function summary(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from_date' => 'required|date',
            'to_date' => 'required|date|after_or_equal:from_date',
        ]);

        $fromDate = $validated['from_date'];
        $toDate = $validated['to_date'];

        // Try DailyStatistic table first
        $statistics = DailyStatistic::whereBetween('statistic_date', [$fromDate, $toDate])->get();

        // If we have daily statistics, use them
        if ($statistics->count() > 0) {
            $summary = [
                'period' => [
                    'from' => $fromDate,
                    'to' => $toDate,
                    'days' => $statistics->count(),
                ],
                'production' => [
                    'total_fabric_input' => $statistics->sum('total_fabric_input'),
                    'total_fabric_cost' => $statistics->sum('total_fabric_cost'),
                    'total_cutting_result' => $statistics->sum('total_cutting_result'),
                    'total_cutting_distribution' => $statistics->sum('total_cutting_distribution'),
                    'total_deposit_cutting' => $statistics->sum('total_deposit_cutting'),
                    'total_sewing_price' => $statistics->sum('total_sewing_price'),
                ],
                'quality_control' => [
                    'total_qc_result' => $statistics->sum('total_qc_result'),
                    'total_qc_to_repair' => $statistics->sum('total_qc_to_repair'),
                    'total_repair_distribution' => $statistics->sum('total_repair_distribution'),
                    'total_deposit_repair' => $statistics->sum('total_deposit_repair'),
                ],
                'averages' => [
                    'completion_rate' => $statistics->avg('completion_rate'),
                    'defect_rate' => $statistics->avg('defect_rate'),
                    'active_tailors' => $statistics->avg('active_tailors'),
                    'active_brands' => $statistics->avg('active_brands'),
                ],
            ];
        } else {
            // Fall back to live data from actual tables
            $cuttingResults = CuttingResult::whereDate('cutting_date', '>=', $fromDate)->whereDate('cutting_date', '<=', $toDate);
            $distributions = CuttingDistribution::whereDate('taken_date', '>=', $fromDate)->whereDate('taken_date', '<=', $toDate);
            $deposits = DepositCuttingResult::whereDate('deposit_date', '>=', $fromDate)->whereDate('deposit_date', '<=', $toDate);
            $qcResults = QCResult::whereDate('qc_date', '>=', $fromDate)->whereDate('qc_date', '<=', $toDate);
            $repairDistributions = RepairDistribution::whereDate('taken_date', '>=', $fromDate)->whereDate('taken_date', '<=', $toDate);
            $repairDeposits = DepositRepairResult::whereDate('deposit_date', '>=', $fromDate)->whereDate('deposit_date', '<=', $toDate);

            $totalCutting = $cuttingResults->sum('total_cutting');
            $totalDistributed = $distributions->sum('total_cutting');
            $totalSewingResult = $deposits->sum('total_sewing_result');
            $totalSewingPrice = $deposits->sum('sewing_price');
            $totalQcProducts = $qcResults->sum('total_products');
            $totalDefects = $qcResults->sum('total_to_repair');

            $summary = [
                'period' => [
                    'from' => $fromDate,
                    'to' => $toDate,
                    'days' => (int) ceil((strtotime($toDate) - strtotime($fromDate)) / 86400) + 1,
                ],
                'production' => [
                    'total_fabric_input' => 0,
                    'total_fabric_cost' => 0,
                    'total_cutting_result' => $totalCutting,
                    'total_cutting_distribution' => $totalDistributed,
                    'total_deposit_cutting' => $totalSewingResult,
                    'total_sewing_price' => $totalSewingPrice,
                ],
                'quality_control' => [
                    'total_qc_result' => $totalQcProducts,
                    'total_qc_to_repair' => $totalDefects,
                    'total_repair_distribution' => $repairDistributions->sum('total_to_repair'),
                    'total_deposit_repair' => $repairDeposits->sum('total_repaired'),
                ],
                'averages' => [
                    'completion_rate' => $totalDistributed > 0
                        ? round(($deposits->count() / CuttingDistribution::whereBetween('taken_date', [$fromDate, $toDate])->count()) * 100, 2)
                        : 0,
                    'defect_rate' => $totalQcProducts > 0
                        ? round(($totalDefects / $totalQcProducts) * 100, 2)
                        : 0,
                    'active_tailors' => 0,
                    'active_brands' => 0,
                ],
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $summary
        ]);
    }

    public function latest(): JsonResponse
    {
        $statistic = DailyStatistic::orderBy('statistic_date', 'desc')->first();

        if (!$statistic) {
            return response()->json([
                'success' => false,
                'message' => 'No statistics found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $statistic->load(['createdBy', 'updatedBy'])
        ]);
    }
}