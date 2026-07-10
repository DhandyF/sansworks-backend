<?php

namespace App\Http\Controllers;

use App\Models\Tailor;
use App\Models\DepositCuttingResult;
use App\Models\RepairDeposit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Carbon\Carbon;

class PayslipController extends Controller
{
    public function generate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tailor_id' => 'required|exists:tailors,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $tailor = Tailor::findOrFail($validated['tailor_id']);
        $startDate = Carbon::parse($validated['start_date'])->startOfDay();
        $endDate = Carbon::parse($validated['end_date'])->endOfDay();

        // Production deposits
        $deposits = DepositCuttingResult::with(['cuttingDistribution.article', 'cuttingDistribution.size'])
            ->where('tailor_id', $tailor->id)
            ->whereDate('deposit_date', '>=', $startDate->toDateString())
            ->whereDate('deposit_date', '<=', $endDate->toDateString())
            ->get();

        // Repair deposits (earnings) - only include those with charges
        $repairDeposits = RepairDeposit::with(['repair.article'])
            ->where('tailor_id', $tailor->id)
            ->where('charge_amount', '>', 0)
            ->whereDate('deposit_date', '>=', $startDate->toDateString())
            ->whereDate('deposit_date', '<=', $endDate->toDateString())
            ->get();

        $dayCount = $startDate->diffInDays($endDate) + 1;
        $useWeeks = $dayCount > 7;

        if ($useWeeks) {
            $weeks = $this->buildWeeks($startDate, $endDate);
            $grouped = $this->groupByWeeks($deposits, $weeks, $repairDeposits);
        } else {
            $dateRange = [];
            $current = $startDate->copy();
            while ($current->lte($endDate)) {
                $dateRange[] = $current->format('Y-m-d');
                $current->addDay();
            }
            $grouped = $this->groupByDays($deposits, $dateRange, $repairDeposits);
        }

        $grandTotal = collect($grouped)->sum('earnings');
        $grandCuttingQty = collect($grouped)->sum('cutting_qty');
        $grandRepairQty = collect($grouped)->sum('repair_qty');
        $grandQty = collect($grouped)->sum('total_qty');
        $grandRepairCharges = collect($grouped)->sum('repair_charges');
        $grandCuttingDepositCharges = collect($grouped)->sum('cutting_deposit_charges');

        $periodLabel = $startDate->format('d M Y') . ' - ' . $endDate->format('d M Y');
        if ($startDate->equalTo($endDate)) {
            $periodLabel = $startDate->format('d M Y');
        }

        $monthLabel = $startDate->format('F Y');
        if ($startDate->month !== $endDate->month || $startDate->year !== $endDate->year) {
            $monthLabel = $startDate->format('d M Y') . ' - ' . $endDate->format('d M Y');
        }

        return response()->json([
            'tailor' => [
                'id' => $tailor->id,
                'name' => $tailor->name,
                'phone' => $tailor->phone,
                'address' => $tailor->address,
            ],
            'period' => [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'week_label' => $periodLabel,
                'month_label' => $monthLabel,
                'use_weeks' => $useWeeks,
            ],
            'columns' => $useWeeks ? $this->buildWeeks($startDate, $endDate) : $this->buildDays($startDate, $endDate),
            'items' => $grouped,
            'summary' => [
                'total_articles' => count($grouped),
                'cutting_qty' => $grandCuttingQty,
                'repair_qty' => $grandRepairQty,
                'total_qty' => $grandQty,
                'earnings' => round($grandTotal, 2),
                'repair_charges' => round($grandRepairCharges, 2),
                'cutting_deposit_charges' => round($grandCuttingDepositCharges, 2),
                'net_total' => round($grandTotal - $grandRepairCharges - $grandCuttingDepositCharges, 2),
            ],
        ]);
    }

    private function buildWeeks($startDate, $endDate)
    {
        $weeks = [];
        $current = $startDate->copy()->startOfWeek(Carbon::MONDAY);
        $weekNum = 1;
        while ($current->lte($endDate)) {
            $weekStart = $current->copy();
            $weekEnd = $current->copy()->endOfWeek(Carbon::SUNDAY);
            if ($weekEnd->gt($endDate)) {
                $weekEnd = $endDate->copy();
            }
            $weeks[] = [
                'key' => "week_{$weekNum}",
                'label' => "Week {$weekNum}",
                'sub_label' => $weekStart->format('d M') . ' - ' . $weekEnd->format('d M'),
                'start' => $weekStart->format('Y-m-d'),
                'end' => $weekEnd->format('Y-m-d'),
            ];
            $current->addWeek();
            $weekNum++;
        }
        return $weeks;
    }

    private function buildDays($startDate, $endDate)
    {
        $days = [];
        $current = $startDate->copy();
        while ($current->lte($endDate)) {
            $days[] = [
                'key' => $current->format('Y-m-d'),
                'label' => $current->format('d M'),
                'sub_label' => $current->format('D'),
                'date' => $current->format('Y-m-d'),
            ];
            $current->addDay();
        }
        return $days;
    }

    private function groupByWeeks($deposits, $weeks, $repairDeposits)
    {
        $production = $deposits->groupBy(function ($dep) {
            return $dep->cuttingDistribution?->article?->id ?? 'unknown';
        });

        $repairs = $repairDeposits->groupBy(function ($dep) {
            return $dep->repair?->article?->id ?? 'unknown_repair';
        });

        $allArticleIds = array_unique(array_merge(
            $production->keys()->all(),
            $repairs->keys()->all()
        ));

        $grouped = [];
        foreach ($allArticleIds as $articleId) {
            $prodGroup = $production->get($articleId);
            $repairGroup = $repairs->get($articleId);

            $articleName = $prodGroup?->first()?->cuttingDistribution?->article?->name
                ?? $repairGroup?->first()?->repair?->article?->name
                ?? 'Unknown Article';

            $weekly = [];
            foreach ($weeks as $w) {
                $weekly[$w['key']] = 0;
            }

            $prodQty = 0;
            $prodTotalPrice = 0;
            $cuttingPricePerPcs = 0;
            $cuttingDepositCharges = 0;

            if ($prodGroup) {
                foreach ($prodGroup as $dep) {
                    $depDate = Carbon::parse($dep->deposit_date)->format('Y-m-d');
                    foreach ($weeks as $w) {
                        if ($depDate >= $w['start'] && $depDate <= $w['end']) {
                            $weekly[$w['key']] += (int) $dep->total_sewing_result;
                            break;
                        }
                    }
                    $qty = (int) $dep->total_sewing_result;
                    $prodQty += $qty;
                    $prodTotalPrice += (float) $dep->total_price;
                    if ($qty > 0 && !empty($dep->cutting_price_per_pcs)) {
                        $cuttingPricePerPcs = (float) $dep->cutting_price_per_pcs;
                    }
                    $cuttingDepositCharges += (float) ($dep->charge_amount ?? 0);
                }
            }

            $repairQty = 0;
            $repairCharges = 0;
            if ($repairGroup) {
                foreach ($repairGroup as $dep) {
                    // Only include repair deposits with charges
                    if ((float) $dep->charge_amount > 0) {
                        $repairQty += (int) $dep->total_deposit;
                        $repairCharges += (float) $dep->charge_amount;
                    }
                }
            }

            $pricePerPcs = $prodQty > 0 ? round($prodTotalPrice / $prodQty, 2) : 0;

            $grouped[] = [
                'article_name' => $articleName,
                'article_id' => $articleId,
                'columns' => $weekly,
                'cutting_qty' => $prodQty,
                'repair_qty' => $repairQty,
                'total_qty' => $prodQty + $repairQty,
                'price_per_pcs' => $pricePerPcs,
                'cutting_price_per_pcs' => $cuttingPricePerPcs,
                'earnings' => round($prodTotalPrice, 2),
                'repair_charges' => round($repairCharges, 2),
                'cutting_deposit_charges' => round($cuttingDepositCharges, 2),
            ];
        }

        return $grouped;
    }

    private function groupByDays($deposits, $dateRange, $repairDeposits)
    {
        $daily = array_fill_keys($dateRange, 0);

        $production = $deposits->groupBy(function ($dep) {
            return $dep->cuttingDistribution?->article?->id ?? 'unknown';
        });

        $repairs = $repairDeposits->groupBy(function ($dep) {
            return $dep->repair?->article?->id ?? 'unknown_repair';
        });

        $allArticleIds = array_unique(array_merge(
            $production->keys()->all(),
            $repairs->keys()->all()
        ));

        $grouped = [];
        foreach ($allArticleIds as $articleId) {
            $prodGroup = $production->get($articleId);
            $repairGroup = $repairs->get($articleId);

            $articleName = $prodGroup?->first()?->cuttingDistribution?->article?->name
                ?? $repairGroup?->first()?->repair?->article?->name
                ?? 'Unknown Article';

            $d = $daily;

            $prodQty = 0;
            $prodTotalPrice = 0;
            $cuttingPricePerPcs = 0;
            $cuttingDepositCharges = 0;

            if ($prodGroup) {
                foreach ($prodGroup as $dep) {
                    $depDate = Carbon::parse($dep->deposit_date)->format('Y-m-d');
                    if (isset($d[$depDate])) {
                        $d[$depDate] += (int) $dep->total_sewing_result;
                    }
                    $qty = (int) $dep->total_sewing_result;
                    $prodQty += $qty;
                    $prodTotalPrice += (float) $dep->total_price;
                    if ($qty > 0 && !empty($dep->cutting_price_per_pcs)) {
                        $cuttingPricePerPcs = (float) $dep->cutting_price_per_pcs;
                    }
                    $cuttingDepositCharges += (float) ($dep->charge_amount ?? 0);
                }
            }

            $repairQty = 0;
            $repairCharges = 0;
            if ($repairGroup) {
                foreach ($repairGroup as $dep) {
                    // Only include repair deposits with charges
                    if ((float) $dep->charge_amount > 0) {
                        $repairQty += (int) $dep->total_deposit;
                        $repairCharges += (float) $dep->charge_amount;
                    }
                }
            }

            $pricePerPcs = $prodQty > 0 ? round($prodTotalPrice / $prodQty, 2) : 0;

            $grouped[] = [
                'article_name' => $articleName,
                'article_id' => $articleId,
                'columns' => $d,
                'cutting_qty' => $prodQty,
                'repair_qty' => $repairQty,
                'total_qty' => $prodQty + $repairQty,
                'price_per_pcs' => $pricePerPcs,
                'cutting_price_per_pcs' => $cuttingPricePerPcs,
                'earnings' => round($prodTotalPrice, 2),
                'repair_charges' => round($repairCharges, 2),
                'cutting_deposit_charges' => round($cuttingDepositCharges, 2),
            ];
        }

        return $grouped;
    }
}