<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Fabric;
use App\Models\CuttingResult;
use App\Models\CuttingDistribution;
use App\Models\DepositCuttingResult;
use App\Models\QCResult;
use App\Models\RepairDistribution;
use App\Models\DailyStatistic;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Get comprehensive dashboard data.
     */
    public function index(Request $request): JsonResponse
    {
        $date = $request->get('date', now()->toDateString());
        $monthStart = now()->startOfMonth()->toDateString();
        $monthEnd = now()->endOfMonth()->toDateString();

        $dashboard = [
            // Today's summary
            'today' => $this->getTodaySummary($date),

            // Monthly summary
            'month' => $this->getMonthlySummary($monthStart, $monthEnd),

            // Inventory status
            'inventory' => $this->getInventoryStatus(),

            // Production status
            'production' => $this->getProductionStatus(),

            // Quality control status
            'quality_control' => $this->getQualityControlStatus(),

            // Pending tasks
            'pending_tasks' => $this->getPendingTasks(),

            // Top performers
            'top_performers' => $this->getTopPerformers($monthStart, $monthEnd),
        ];

        return response()->json([
            'success' => true,
            'data' => $dashboard
        ]);
    }

    /**
     * Get today's summary.
     */
    protected function getTodaySummary(string $date): array
    {
        return [
            'cutting_results' => CuttingResult::whereDate('cutting_date', $date)->count(),
            'cutting_quantity' => CuttingResult::whereDate('cutting_date', $date)->sum('total_cutting'),
            'distributions' => CuttingDistribution::whereDate('taken_date', $date)->count(),
            'deposits' => DepositCuttingResult::whereDate('deposit_date', $date)->count(),
            'qc_results' => QCResult::whereDate('qc_date', $date)->count(),
            'repair_distributions' => RepairDistribution::whereDate('taken_date', $date)->count(),
        ];
    }

    /**
     * Get monthly summary.
     */
    protected function getMonthlySummary(string $startDate, string $endDate): array
    {
        $cuttingResults = CuttingResult::whereBetween('cutting_date', [$startDate, $endDate]);
        $deposits = DepositCuttingResult::whereBetween('deposit_date', [$startDate, $endDate]);
        $qcResults = QCResult::whereBetween('qc_date', [$startDate, $endDate]);

        return [
            'total_cutting' => $cuttingResults->sum('total_cutting'),
            'total_distributed' => CuttingDistribution::whereBetween('taken_date', [$startDate, $endDate])->sum('total_cutting'),
            'total_deposited' => $deposits->sum('total_sewing_result'),
            'total_sewing_price' => $deposits->sum('sewing_price'),
            'total_qc_checked' => $qcResults->sum('total_products'),
            'total_defects' => $qcResults->sum('total_to_repair'),
            'defect_rate' => $qcResults->sum('total_products') > 0
                ? round(($qcResults->sum('total_to_repair') / $qcResults->sum('total_products')) * 100, 2)
                : 0,
        ];
    }

    /**
     * Get inventory status.
     */
    protected function getInventoryStatus(): array
    {
        return [
            'total_fabrics' => Fabric::count(),
            'low_stock_items' => Fabric::where('total_quantity', '<', 100)->count(),
            'total_fabric_value' => Fabric::selectRaw('SUM(total_quantity * price_per_unit) as value')->value('value') ?? 0,
            'by_unit' => Fabric::selectRaw('unit, SUM(total_quantity) as quantity')
                ->groupBy('unit')
                ->get()
                ->pluck('quantity', 'unit'),
        ];
    }

    /**
     * Get production status.
     */
    protected function getProductionStatus(): array
    {
        return [
            'pending_distributions' => CuttingDistribution::whereDoesntHave('depositCuttingResults')->count(),
            'overdue_distributions' => CuttingDistribution::where('deadline_date', '<', now())
                ->whereDoesntHave('depositCuttingResults')
                ->count(),
            'in_progress_deposits' => DepositCuttingResult::where('status', 'in_progress')->count(),
            'completed_today' => DepositCuttingResult::whereDate('deposit_date', now())
                ->where('status', 'done')
                ->count(),
        ];
    }

    /**
     * Get quality control status.
     */
    protected function getQualityControlStatus(): array
    {
        return [
            'pending_qc' => DepositCuttingResult::whereDoesntHave('qcResults')->count(),
            'pending_repairs' => RepairDistribution::whereDoesntHave('depositRepairResults')->count(),
            'overdue_repairs' => RepairDistribution::where('deadline_repair_date', '<', now())
                ->whereDoesntHave('depositRepairResults')
                ->count(),
            'completed_repairs_today' => DB::table('deposit_repair_results')
                ->whereDate('deposit_date', now())
                ->count(),
        ];
    }

    /**
     * Get pending tasks.
     */
    protected function getPendingTasks(): array
    {
        return [
            'distributions_awaiting_deposit' => CuttingDistribution::with(['tailor', 'brand', 'article', 'size'])
                ->whereDoesntHave('depositCuttingResults')
                ->orderBy('deadline_date')
                ->limit(5)
                ->get(),

            'deposits_awaiting_qc' => DepositCuttingResult::with(['tailor', 'brand', 'article', 'size'])
                ->whereDoesntHave('qcResults')
                ->orderBy('deposit_date')
                ->limit(5)
                ->get(),

            'qc_awaiting_repair' => QCResult::with(['tailor', 'brand', 'article', 'size'])
                ->where('total_to_repair', '>', 0)
                ->whereDoesntHave('repairDistributions')
                ->orderBy('qc_date')
                ->limit(5)
                ->get(),

            'repairs_awaiting_completion' => RepairDistribution::with(['tailor', 'brand', 'article', 'size'])
                ->whereDoesntHave('depositRepairResults')
                ->orderBy('deadline_repair_date')
                ->limit(5)
                ->get(),
        ];
    }

    /**
     * Get top performers.
     */
    protected function getTopPerformers(string $startDate, string $endDate): array
    {
        return [
            'top_tailors_by_production' => DepositCuttingResult::selectRaw('tailor_id, SUM(total_sewing_result) as total, COUNT(*) as count')
                ->with('tailor')
                ->whereBetween('deposit_date', [$startDate, $endDate])
                ->groupBy('tailor_id')
                ->orderByDesc('total')
                ->limit(5)
                ->get(),

            'top_brands_by_volume' => CuttingResult::selectRaw('brand_id, SUM(total_cutting) as total, COUNT(*) as count')
                ->with('brand')
                ->whereBetween('cutting_date', [$startDate, $endDate])
                ->groupBy('brand_id')
                ->orderByDesc('total')
                ->limit(5)
                ->get(),

            'top_articles' => CuttingResult::selectRaw('article_id, SUM(total_cutting) as total, COUNT(*) as count')
                ->with('article')
                ->whereBetween('cutting_date', [$startDate, $endDate])
                ->groupBy('article_id')
                ->orderByDesc('total')
                ->limit(5)
                ->get(),
        ];
    }

    /**
     * Get production trends for charts.
     */
    public function trends(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'period' => 'required|in:week,month,quarter',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $period = $validated['period'];
        $startDate = $validated['start_date'] ?? match($period) {
            'week' => now()->subWeek()->toDateString(),
            'month' => now()->subMonth()->toDateString(),
            'quarter' => now()->subQuarter()->toDateString(),
        };
        $endDate = $validated['end_date'] ?? now()->toDateString();

        $trends = [
            'cutting_trend' => CuttingResult::selectRaw('DATE(cutting_date) as date, SUM(total_cutting) as total')
                ->whereBetween('cutting_date', [$startDate, $endDate])
                ->groupBy('date')
                ->orderBy('date')
                ->get(),

            'distribution_trend' => CuttingDistribution::selectRaw('DATE(taken_date) as date, SUM(total_cutting) as total')
                ->whereBetween('taken_date', [$startDate, $endDate])
                ->groupBy('date')
                ->orderBy('date')
                ->get(),

            'deposit_trend' => DepositCuttingResult::selectRaw('DATE(deposit_date) as date, SUM(total_sewing_result) as total')
                ->whereBetween('deposit_date', [$startDate, $endDate])
                ->groupBy('date')
                ->orderBy('date')
                ->get(),

            'qc_trend' => QCResult::selectRaw('DATE(qc_date) as date, SUM(total_products) as total, SUM(total_to_repair) as defects')
                ->whereBetween('qc_date', [$startDate, $endDate])
                ->groupBy('date')
                ->orderBy('date')
                ->get(),
        ];

        return response()->json([
            'success' => true,
            'data' => $trends
        ]);
    }
}
