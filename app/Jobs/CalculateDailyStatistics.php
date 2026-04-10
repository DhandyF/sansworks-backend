<?php

namespace App\Jobs;

use App\Models\DailyStatistic;
use App\Models\Fabric;
use App\Models\CuttingResult;
use App\Models\CuttingDistribution;
use App\Models\DepositCuttingResult;
use App\Models\QCResult;
use App\Models\RepairDistribution;
use App\Models\DepositRepairResult;
use App\Models\Tailor;
use App\Models\Brand;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CalculateDailyStatistics implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $date;

    /**
     * Create a new job instance.
     */
    public function __construct(?string $date = null)
    {
        $this->date = $date ?? now()->toDateString();
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            DB::beginTransaction();

            $statistic = DailyStatistic::updateOrCreate(
                ['statistic_date' => $this->date],
                [
                    'total_fabric_input' => $this->calculateTotalFabricInput(),
                    'total_fabric_cost' => $this->calculateTotalFabricCost(),
                    'total_cutting_result' => $this->calculateTotalCuttingResult(),
                    'total_cutting_distribution' => $this->calculateTotalCuttingDistribution(),
                    'total_deposit_cutting' => $this->calculateTotalDepositCutting(),
                    'total_sewing_price' => $this->calculateTotalSewingPrice(),
                    'total_qc_result' => $this->calculateTotalQCResult(),
                    'total_qc_to_repair' => $this->calculateTotalQCToRepair(),
                    'total_repair_distribution' => $this->calculateTotalRepairDistribution(),
                    'total_deposit_repair' => $this->calculateTotalDepositRepair(),
                    'active_tailors' => $this->calculateActiveTailors(),
                    'active_brands' => $this->calculateActiveBrands(),
                    'completed_orders' => $this->calculateCompletedOrders(),
                    'overdue_orders' => $this->calculateOverdueOrders(),
                    'completion_rate' => $this->calculateCompletionRate(),
                    'defect_rate' => $this->calculateDefectRate(),
                    'updated_by' => auth()->id(),
                ]
            );

            DB::commit();

            Log::info("Daily statistics calculated for {$this->date}", [
                'statistic_id' => $statistic->id,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to calculate daily statistics for {$this->date}", [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Calculate total fabric input for the day.
     */
    protected function calculateTotalFabricInput(): float
    {
        return Fabric::whereDate('created_at', $this->date)
            ->sum('total_quantity');
    }

    /**
     * Calculate total fabric cost for the day.
     */
    protected function calculateTotalFabricCost(): float
    {
        return Fabric::whereDate('created_at', $this->date)
            ->selectRaw('SUM(total_quantity * price_per_unit) as total_cost')
            ->value('total_cost') ?? 0;
    }

    /**
     * Calculate total cutting result for the day.
     */
    protected function calculateTotalCuttingResult(): int
    {
        return CuttingResult::whereDate('cutting_date', $this->date)
            ->sum('total_cutting');
    }

    /**
     * Calculate total cutting distribution for the day.
     */
    protected function calculateTotalCuttingDistribution(): int
    {
        return CuttingDistribution::whereDate('taken_date', $this->date)
            ->sum('total_cutting');
    }

    /**
     * Calculate total deposit cutting for the day.
     */
    protected function calculateTotalDepositCutting(): int
    {
        return DepositCuttingResult::whereDate('deposit_date', $this->date)
            ->sum('total_sewing_result');
    }

    /**
     * Calculate total sewing price for the day.
     */
    protected function calculateTotalSewingPrice(): float
    {
        return DepositCuttingResult::whereDate('deposit_date', $this->date)
            ->sum('sewing_price');
    }

    /**
     * Calculate total QC result for the day.
     */
    protected function calculateTotalQCResult(): int
    {
        return QCResult::whereDate('qc_date', $this->date)
            ->sum('total_products');
    }

    /**
     * Calculate total QC to repair for the day.
     */
    protected function calculateTotalQCToRepair(): int
    {
        return QCResult::whereDate('qc_date', $this->date)
            ->sum('total_to_repair');
    }

    /**
     * Calculate total repair distribution for the day.
     */
    protected function calculateTotalRepairDistribution(): int
    {
        return RepairDistribution::whereDate('taken_date', $this->date)
            ->sum('total_to_repair');
    }

    /**
     * Calculate total deposit repair for the day.
     */
    protected function calculateTotalDepositRepair(): int
    {
        return DepositRepairResult::whereDate('deposit_date', $this->date)
            ->sum('total_repaired');
    }

    /**
     * Calculate active tailors (with any activity in the last 30 days).
     */
    protected function calculateActiveTailors(): int
    {
        return Tailor::where('is_active', true)
            ->whereHas('cuttingDistributions', function ($query) {
                $query->where('taken_date', '>=', now()->subDays(30));
            })
            ->orWhereHas('depositCuttingResults', function ($query) {
                $query->where('deposit_date', '>=', now()->subDays(30));
            })
            ->count();
    }

    /**
     * Calculate active brands (with any activity in the last 30 days).
     */
    protected function calculateActiveBrands(): int
    {
        return Brand::where('is_active', true)
            ->whereHas('cuttingResults', function ($query) {
                $query->where('cutting_date', '>=', now()->subDays(30));
            })
            ->orWhereHas('depositCuttingResults', function ($query) {
                $query->where('deposit_date', '>=', now()->subDays(30));
            })
            ->count();
    }

    /**
     * Calculate completed orders (deposit cutting with status done).
     */
    protected function calculateCompletedOrders(): int
    {
        return DepositCuttingResult::whereDate('deposit_date', $this->date)
            ->where('status', 'done')
            ->count();
    }

    /**
     * Calculate overdue orders (cutting distributions past deadline).
     */
    protected function calculateOverdueOrders(): int
    {
        return CuttingDistribution::where('deadline_date', '<', $this->date)
            ->whereDoesntHave('depositCuttingResults')
            ->count();
    }

    /**
     * Calculate completion rate (completed / total distributed).
     */
    protected function calculateCompletionRate(): float
    {
        $distributed = CuttingDistribution::whereDate('taken_date', '<=', $this->date)
            ->sum('total_cutting');

        $deposited = DepositCuttingResult::whereDate('deposit_date', '<=', $this->date)
            ->sum('total_sewing_result');

        if ($distributed == 0) {
            return 0;
        }

        return round(($deposited / $distributed) * 100, 2);
    }

    /**
     * Calculate defect rate (to repair / total QC).
     */
    protected function calculateDefectRate(): float
    {
        $totalQC = QCResult::whereDate('qc_date', '<=', $this->date)
            ->sum('total_products');

        $toRepair = QCResult::whereDate('qc_date', '<=', $this->date)
            ->sum('total_to_repair');

        if ($totalQC == 0) {
            return 0;
        }

        return round(($toRepair / $totalQC) * 100, 2);
    }
}
