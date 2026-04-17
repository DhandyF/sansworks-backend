<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Fabric;
use App\Models\CuttingResult;
use App\Models\CuttingDistribution;
use App\Models\DepositCuttingResult;
use App\Models\QCResult;
use App\Models\RepairDistribution;
use App\Models\Tailor;
use App\Models\Brand;
use App\Models\Article;
use App\Models\Size;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $date = $request->get('date', now()->toDateString());
        $monthStart = now()->startOfMonth()->toDateString();
        $monthEnd = now()->endOfMonth()->toDateString();

        $dashboard = [
            'today' => $this->getTodaySummary($date),
            'month' => $this->getMonthlySummary($monthStart, $monthEnd),
            'production' => $this->getProductionStatus(),
            'quality_control' => $this->getQualityControlStatus(),
            'inventory' => $this->getInventoryStatus(),
            'pending_tasks' => $this->getPendingTasks(),
            'top_performers' => $this->getTopPerformers($monthStart, $monthEnd),
            'counts' => $this->getEntityCounts(),
        ];

        return response()->json([
            'success' => true,
            'data' => $dashboard
        ]);
    }

    protected function getEntityCounts(): array
    {
        return [
            'tailors' => Tailor::count(),
            'active_tailors' => Tailor::where('is_active', true)->count(),
            'brands' => Brand::count(),
            'active_brands' => Brand::where('is_active', true)->count(),
            'articles' => Article::count(),
            'active_articles' => Article::where('is_active', true)->count(),
            'sizes' => Size::count(),
            'fabrics' => Fabric::count(),
        ];
    }

    protected function getTodaySummary(string $date): array
    {
        return [
            'cutting_results' => CuttingResult::whereDate('cutting_date', $date)->count(),
            'cutting_quantity' => CuttingResult::whereDate('cutting_date', $date)->sum('total_cutting'),
            'distributions' => CuttingDistribution::whereDate('taken_date', $date)->count(),
            'distribution_quantity' => CuttingDistribution::whereDate('taken_date', $date)->sum('total_cutting'),
            'deposits' => DepositCuttingResult::whereDate('deposit_date', $date)->count(),
            'sewing_quantity' => DepositCuttingResult::whereDate('deposit_date', $date)->sum('total_sewing_result'),
            'qc_results' => QCResult::whereDate('qc_date', $date)->count(),
            'qc_checked' => QCResult::whereDate('qc_date', $date)->sum('total_products'),
            'qc_passed' => QCResult::whereDate('qc_date', $date)->get()->sum(fn($q) => $q->total_products - $q->total_to_repair),
            'repair_distributions' => RepairDistribution::whereDate('taken_date', $date)->count(),
            'repair_quantity' => RepairDistribution::whereDate('taken_date', $date)->sum('total_to_repair'),
        ];
    }

    protected function dateRangeQuery($query, string $column, string $startDate, string $endDate)
    {
        return $query->whereDate($column, '>=', $startDate)->whereDate($column, '<=', $endDate);
    }

    protected function getMonthlySummary(string $startDate, string $endDate): array
    {
        $cuttingResults = CuttingResult::whereDate('cutting_date', '>=', $startDate)->whereDate('cutting_date', '<=', $endDate);
        $distributions = CuttingDistribution::whereDate('taken_date', '>=', $startDate)->whereDate('taken_date', '<=', $endDate);
        $deposits = DepositCuttingResult::whereDate('deposit_date', '>=', $startDate)->whereDate('deposit_date', '<=', $endDate);
        $qcResults = QCResult::whereDate('qc_date', '>=', $startDate)->whereDate('qc_date', '<=', $endDate);

        $totalProducts = (clone $qcResults)->sum('total_products');
        $totalDefects = (clone $qcResults)->sum('total_to_repair');

        return [
            'total_cutting' => $cuttingResults->sum('total_cutting'),
            'total_distributed' => $distributions->sum('total_cutting'),
            'total_deposited' => $deposits->sum('total_sewing_result'),
            'total_sewing_price' => $deposits->sum('sewing_price'),
            'total_qc_checked' => $totalProducts,
            'total_passed' => $totalProducts - $totalDefects,
            'total_defects' => $totalDefects,
            'defect_rate' => $totalProducts > 0
                ? round(($totalDefects / $totalProducts) * 100, 2)
                : 0,
            'avg_daily_cutting' => $cuttingResults->sum('total_cutting') > 0
                ? round($cuttingResults->sum('total_cutting') / max(1, now()->diffInDays(now()->startOfMonth()) + 1), 1)
                : 0,
        ];
    }

    protected function getProductionStatus(): array
    {
        $pendingDistributions = CuttingDistribution::whereDoesntHave('depositCuttingResults')->count();
        $totalDistributions = CuttingDistribution::count();
        $overdueDistributions = CuttingDistribution::where('deadline_date', '<', now())
            ->whereDoesntHave('depositCuttingResults')
            ->count();

        $completedDeposits = DepositCuttingResult::where('status', 'done')->count();
        $inProgressDeposits = DepositCuttingResult::where('status', '!=', 'done')->count();
        $totalDeposits = DepositCuttingResult::count();

        return [
            'pending_distributions' => $pendingDistributions,
            'total_distributions' => $totalDistributions,
            'distribution_completion' => $totalDistributions > 0
                ? round((($totalDistributions - $pendingDistributions) / $totalDistributions) * 100, 1)
                : 0,
            'overdue_distributions' => $overdueDistributions,
            'in_progress_deposits' => $inProgressDeposits,
            'completed_deposits' => $completedDeposits,
            'total_deposits' => $totalDeposits,
            'deposit_completion' => $totalDeposits > 0
                ? round(($completedDeposits / $totalDeposits) * 100, 1)
                : 0,
            'completed_today' => DepositCuttingResult::whereDate('deposit_date', now())
                ->where('status', 'done')
                ->count(),
        ];
    }

    protected function getQualityControlStatus(): array
    {
        $pendingQc = DepositCuttingResult::whereDoesntHave('qcResults')->count();
        $totalDeposits = DepositCuttingResult::count();

        $pendingRepairs = RepairDistribution::whereDoesntHave('depositRepairResults')->count();
        $totalRepairs = RepairDistribution::count();
        $overdueRepairs = RepairDistribution::where('deadline_repair_date', '<', now())
            ->whereDoesntHave('depositRepairResults')
            ->count();

        $totalQcResults = QCResult::count();
        $totalDefects = QCResult::sum('total_to_repair');
        $totalChecked = QCResult::sum('total_products');

        return [
            'pending_qc' => $pendingQc,
            'total_qc' => $totalQcResults,
            'qc_coverage' => $totalDeposits > 0
                ? round((($totalDeposits - $pendingQc) / $totalDeposits) * 100, 1)
                : 0,
            'overall_defect_rate' => $totalChecked > 0
                ? round(($totalDefects / $totalChecked) * 100, 2)
                : 0,
            'pending_repairs' => $pendingRepairs,
            'total_repairs' => $totalRepairs,
            'repair_completion' => $totalRepairs > 0
                ? round((($totalRepairs - $pendingRepairs) / $totalRepairs) * 100, 1)
                : 0,
            'overdue_repairs' => $overdueRepairs,
            'completed_repairs_today' => DB::table('deposit_repair_results')
                ->whereDate('deposit_date', now())
                ->count(),
        ];
    }

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

    protected function getPendingTasks(): array
    {
        $distributionsAwaitingDeposit = CuttingDistribution::with(['tailor', 'brand', 'article', 'size'])
            ->whereDoesntHave('depositCuttingResults')
            ->orderBy('deadline_date')
            ->limit(5)
            ->get()
            ->map(fn($item) => [
                'id' => $item->id,
                'type' => 'awaiting_deposit',
                'title' => $item->distribution_number,
                'subtitle' => "{$item->brand?->name} - {$item->article?->name} ({$item->size?->name})",
                'assignee' => $item->tailor?->name,
                'date' => $item->deadline_date?->toDateString(),
                'quantity' => $item->total_cutting,
                'overdue' => $item->deadline_date?->isPast() ?? false,
            ]);

        $depositsAwaitingQc = DepositCuttingResult::with(['tailor', 'brand', 'article', 'size'])
            ->whereDoesntHave('qcResults')
            ->orderBy('deposit_date')
            ->limit(5)
            ->get()
            ->map(fn($item) => [
                'id' => $item->id,
                'type' => 'awaiting_qc',
                'title' => "Deposit #{$item->id}",
                'subtitle' => "{$item->brand?->name} - {$item->article?->name} ({$item->size?->name})",
                'assignee' => $item->tailor?->name,
                'date' => $item->deposit_date?->toDateString(),
                'quantity' => $item->total_sewing_result,
                'overdue' => false,
            ]);

        $qcAwaitingRepair = QCResult::with(['tailor', 'brand', 'article', 'size'])
            ->where('total_to_repair', '>', 0)
            ->whereDoesntHave('repairDistributions')
            ->orderBy('qc_date')
            ->limit(5)
            ->get()
            ->map(fn($item) => [
                'id' => $item->id,
                'type' => 'awaiting_repair',
                'title' => "QC #{$item->id}",
                'subtitle' => "{$item->brand?->name} - {$item->article?->name} ({$item->size?->name})",
                'assignee' => $item->tailor?->name,
                'date' => $item->qc_date?->toDateString(),
                'quantity' => $item->total_to_repair,
                'overdue' => false,
            ]);

        $repairsAwaitingCompletion = RepairDistribution::with(['tailor', 'brand', 'article', 'size'])
            ->whereDoesntHave('depositRepairResults')
            ->orderBy('deadline_repair_date')
            ->limit(5)
            ->get()
            ->map(fn($item) => [
                'id' => $item->id,
                'type' => 'awaiting_repair_completion',
                'title' => $item->repair_distribution_number ?? "Repair #{$item->id}",
                'subtitle' => "{$item->brand?->name} - {$item->article?->name} ({$item->size?->name})",
                'assignee' => $item->tailor?->name,
                'date' => $item->deadline_repair_date?->toDateString(),
                'quantity' => $item->total_to_repair,
                'overdue' => $item->deadline_repair_date?->isPast() ?? false,
            ]);

        return [
            'distributions_awaiting_deposit' => $distributionsAwaitingDeposit,
            'deposits_awaiting_qc' => $depositsAwaitingQc,
            'qc_awaiting_repair' => $qcAwaitingRepair,
            'repairs_awaiting_completion' => $repairsAwaitingCompletion,
        ];
    }

    protected function getTopPerformers(string $startDate, string $endDate): array
    {
        return [
            'top_tailors_by_production' => DepositCuttingResult::selectRaw('tailor_id, SUM(total_sewing_result) as total, COUNT(*) as count')
                ->with('tailor')
                ->whereDate('deposit_date', '>=', $startDate)->whereDate('deposit_date', '<=', $endDate)
                ->groupBy('tailor_id')
                ->orderByDesc('total')
                ->limit(5)
                ->get(),

            'top_brands_by_volume' => CuttingResult::selectRaw('brand_id, SUM(total_cutting) as total, COUNT(*) as count')
                ->with('brand')
                ->whereDate('cutting_date', '>=', $startDate)->whereDate('cutting_date', '<=', $endDate)
                ->groupBy('brand_id')
                ->orderByDesc('total')
                ->limit(5)
                ->get(),

            'top_articles' => CuttingResult::selectRaw('article_id, SUM(total_cutting) as total, COUNT(*) as count')
                ->with('article')
                ->whereDate('cutting_date', '>=', $startDate)->whereDate('cutting_date', '<=', $endDate)
                ->groupBy('article_id')
                ->orderByDesc('total')
                ->limit(5)
                ->get(),

            'top_tailors_by_quality' => QCResult::selectRaw('tailor_id, SUM(total_products) as total_checked, SUM(total_to_repair) as total_defects, COUNT(*) as inspections')
                ->with('tailor')
                ->whereDate('qc_date', '>=', $startDate)->whereDate('qc_date', '<=', $endDate)
                ->groupBy('tailor_id')
                ->orderByRaw('CASE WHEN SUM(total_products) > 0 THEN (SUM(total_products) - SUM(total_to_repair)) / SUM(total_products) ELSE 0 END DESC')
                ->limit(5)
                ->get(),
        ];
    }

    public function trends(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'period' => 'nullable|in:7d,30d,90d,custom',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $period = $validated['period'] ?? '30d';

        $startDate = $validated['start_date'] ?? match($period) {
            '7d' => now()->subDays(7)->toDateString(),
            '30d' => now()->subDays(30)->toDateString(),
            '90d' => now()->subDays(90)->toDateString(),
            default => now()->subDays(30)->toDateString(),
        };
        $endDate = $validated['end_date'] ?? now()->toDateString();

        $cuttingTrend = CuttingResult::selectRaw('DATE(cutting_date) as date, SUM(total_cutting) as total, COUNT(*) as batches')
            ->whereDate('cutting_date', '>=', $startDate)->whereDate('cutting_date', '<=', $endDate)
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $distributionTrend = CuttingDistribution::selectRaw('DATE(taken_date) as date, SUM(total_cutting) as total, COUNT(*) as count')
            ->whereDate('taken_date', '>=', $startDate)->whereDate('taken_date', '<=', $endDate)
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $depositTrend = DepositCuttingResult::selectRaw('DATE(deposit_date) as date, SUM(total_sewing_result) as total, SUM(sewing_price) as value, COUNT(*) as count')
            ->whereDate('deposit_date', '>=', $startDate)->whereDate('deposit_date', '<=', $endDate)
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $qcTrend = QCResult::selectRaw('DATE(qc_date) as date, SUM(total_products) as total, SUM(total_to_repair) as defects, COUNT(*) as inspections')
            ->whereDate('qc_date', '>=', $startDate)->whereDate('qc_date', '<=', $endDate)
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $repairTrend = RepairDistribution::selectRaw('DATE(taken_date) as date, SUM(total_to_repair) as total, COUNT(*) as count')
            ->whereDate('taken_date', '>=', $startDate)->whereDate('taken_date', '<=', $endDate)
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $allDates = collect([$cuttingTrend, $distributionTrend, $depositTrend, $qcTrend, $repairTrend])
            ->flatten(1)
            ->pluck('date')
            ->unique()
            ->sort()
            ->values();

        $trends = [
            'period' => [
                'start' => $startDate,
                'end' => $endDate,
            ],
            'dates' => $allDates,
            'cutting_trend' => $cuttingTrend,
            'distribution_trend' => $distributionTrend,
            'deposit_trend' => $depositTrend,
            'qc_trend' => $qcTrend,
            'repair_trend' => $repairTrend,

            'summary' => [
                'total_cutting' => $cuttingTrend->sum('total'),
                'total_distributed' => $distributionTrend->sum('total'),
                'total_sewn' => $depositTrend->sum('total'),
                'total_sewing_value' => $depositTrend->sum('value'),
                'total_qc_checked' => $qcTrend->sum('total'),
                'total_defects' => $qcTrend->sum('defects'),
                'total_repairs' => $repairTrend->sum('total'),
                'defect_rate' => $qcTrend->sum('total') > 0
                    ? round(($qcTrend->sum('defects') / $qcTrend->sum('total')) * 100, 2)
                    : 0,
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $trends
        ]);
    }
}