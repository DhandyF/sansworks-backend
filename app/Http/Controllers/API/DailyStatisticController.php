<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\DailyStatistic;
use App\Jobs\CalculateDailyStatistics;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DailyStatisticController extends Controller
{
    /**
     * Display a listing of daily statistics.
     */
    public function index(Request $request): JsonResponse
    {
        $query = DailyStatistic::with(['createdBy', 'updatedBy']);

        // Filter by date range
        if ($request->has('from_date') && $request->has('to_date')) {
            $query->whereBetween('statistic_date', [$request->from_date, $request->to_date]);
        }

        // Order by date descending
        $statistics = $query->orderBy('statistic_date', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $statistics
        ]);
    }

    /**
     * Store a newly created daily statistic.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'statistic_date' => 'required|date|unique:daily_statistics,statistic_date',
        ]);

        // Dispatch the job to calculate statistics
        CalculateDailyStatistics::dispatch($validated['statistic_date']);

        return response()->json([
            'success' => true,
            'message' => 'Daily statistics calculation job dispatched',
        ], 202);
    }

    /**
     * Display the specified daily statistic.
     */
    public function show(DailyStatistic $dailyStatistic): JsonResponse
    {
        $dailyStatistic->load(['createdBy', 'updatedBy']);

        return response()->json([
            'success' => true,
            'data' => $dailyStatistic
        ]);
    }

    /**
     * Update the specified daily statistic.
     */
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

        $validated['updated_by'] = auth()->id();

        $dailyStatistic->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Daily statistic updated successfully',
            'data' => $dailyStatistic->fresh()->load(['createdBy', 'updatedBy'])
        ]);
    }

    /**
     * Remove the specified daily statistic.
     */
    public function destroy(DailyStatistic $dailyStatistic): JsonResponse
    {
        $dailyStatistic->delete();

        return response()->json([
            'success' => true,
            'message' => 'Daily statistic deleted successfully'
        ]);
    }

    /**
     * Recalculate statistics for a specific date.
     */
    public function recalculate(Request $request, DailyStatistic $dailyStatistic): JsonResponse
    {
        // Dispatch the job to recalculate statistics
        CalculateDailyStatistics::dispatch($dailyStatistic->statistic_date);

        return response()->json([
            'success' => true,
            'message' => 'Daily statistics recalculation job dispatched',
        ], 202);
    }

    /**
     * Get summary statistics for a date range.
     */
    public function summary(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from_date' => 'required|date',
            'to_date' => 'required|date|after_or_equal:from_date',
        ]);

        $statistics = DailyStatistic::whereBetween('statistic_date', [$validated['from_date'], $validated['to_date']])
            ->get();

        $summary = [
            'period' => [
                'from' => $validated['from_date'],
                'to' => $validated['to_date'],
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

        return response()->json([
            'success' => true,
            'data' => $summary
        ]);
    }

    /**
     * Get latest statistics (today or most recent).
     */
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
