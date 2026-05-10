<?php

namespace App\Http\Controllers;

use App\Models\Tailor;
use App\Models\DepositCuttingResult;
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

        $deposits = DepositCuttingResult::with(['cuttingDistribution.article', 'cuttingDistribution.size'])
            ->where('tailor_id', $tailor->id)
            ->whereDate('deposit_date', '>=', $startDate->toDateString())
            ->whereDate('deposit_date', '<=', $endDate->toDateString())
            ->get();

        $dayCount = $startDate->diffInDays($endDate) + 1;
        $useWeeks = $dayCount > 7;

        if ($useWeeks) {
            $weeks = $this->buildWeeks($startDate, $endDate);
            $grouped = $this->groupByWeeks($deposits, $weeks);
        } else {
            $dateRange = [];
            $current = $startDate->copy();
            while ($current->lte($endDate)) {
                $dateRange[] = $current->format('Y-m-d');
                $current->addDay();
            }
            $grouped = $this->groupByDays($deposits, $dateRange);
        }

        $grandTotal = collect($grouped)->sum('total_price');
        $grandQty = collect($grouped)->sum('total_qty');

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
                'total_qty' => $grandQty,
                'total_price' => round($grandTotal, 2),
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

    private function groupByWeeks($deposits, $weeks)
    {
        return $deposits->groupBy(function ($dep) {
            return $dep->cuttingDistribution?->article?->id ?? 'unknown';
        })->map(function ($group, $articleId) use ($weeks) {
            $articleName = $group->first()->cuttingDistribution?->article?->name ?? 'Unknown Article';
            $weekly = [];
            foreach ($weeks as $w) {
                $weekly[$w['key']] = 0;
            }
            $totalQty = 0;
            $totalPrice = 0;
            $cuttingPricePerPcs = 0;
            foreach ($group as $dep) {
                $depDate = Carbon::parse($dep->deposit_date)->format('Y-m-d');
                foreach ($weeks as $w) {
                    if ($depDate >= $w['start'] && $depDate <= $w['end']) {
                        $weekly[$w['key']] += (int) $dep->total_sewing_result;
                        break;
                    }
                }
                $qty = (int) $dep->total_sewing_result;
                $totalQty += $qty;
                $totalPrice += (float) $dep->total_price;
                if ($qty > 0 && !empty($dep->cutting_price_per_pcs)) {
                    $cuttingPricePerPcs = (float) $dep->cutting_price_per_pcs;
                }
            }
            $pricePerPcs = $totalQty > 0 ? round($totalPrice / $totalQty, 2) : 0;
            return [
                'article_name' => $articleName,
                'article_id' => $articleId,
                'columns' => $weekly,
                'total_qty' => $totalQty,
                'price_per_pcs' => $pricePerPcs,
                'cutting_price_per_pcs' => $cuttingPricePerPcs,
                'total_price' => round($totalPrice, 2),
                'deposit_count' => $group->count(),
            ];
        })->values()->all();
    }

    private function groupByDays($deposits, $dateRange)
    {
        $daily = array_fill_keys($dateRange, 0);
        return $deposits->groupBy(function ($dep) {
            return $dep->cuttingDistribution?->article?->id ?? 'unknown';
        })->map(function ($group, $articleId) use ($dateRange, $daily) {
            $articleName = $group->first()->cuttingDistribution?->article?->name ?? 'Unknown Article';
            $d = $daily;
            $totalQty = 0;
            $totalPrice = 0;
            $cuttingPricePerPcs = 0;
            foreach ($group as $dep) {
                $depDate = Carbon::parse($dep->deposit_date)->format('Y-m-d');
                if (isset($d[$depDate])) {
                    $d[$depDate] += (int) $dep->total_sewing_result;
                }
                $qty = (int) $dep->total_sewing_result;
                $totalQty += $qty;
                $totalPrice += (float) $dep->total_price;
                if ($qty > 0 && !empty($dep->cutting_price_per_pcs)) {
                    $cuttingPricePerPcs = (float) $dep->cutting_price_per_pcs;
                }
            }
            $pricePerPcs = $totalQty > 0 ? round($totalPrice / $totalQty, 2) : 0;
            return [
                'article_name' => $articleName,
                'article_id' => $articleId,
                'columns' => $d,
                'total_qty' => $totalQty,
                'price_per_pcs' => $pricePerPcs,
                'cutting_price_per_pcs' => $cuttingPricePerPcs,
                'total_price' => round($totalPrice, 2),
                'deposit_count' => $group->count(),
            ];
        })->values()->all();
    }
}