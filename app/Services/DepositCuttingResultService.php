<?php

namespace App\Services;

use App\Models\DepositCuttingResult;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class DepositCuttingResultService extends BaseService
{
    public function __construct(DepositCuttingResult $model)
    {
        $this->model = $model;
    }

    public function paginate(int $perPage = 15, string $search = null, string $brandId = null): LengthAwarePaginator
    {
        return $this->paginateGrouped($perPage, $search, $brandId);
    }

    public function paginateGrouped(int $perPage = 15, string $search = null, string $brandId = null): LengthAwarePaginator
    {
        $query = $this->model->with(['cuttingDistribution.cuttingResult.preOrder', 'tailor', 'brand', 'article', 'size'])
            ->whereHas('cuttingDistribution.cuttingResult.preOrder', fn ($q) => $q->whereNull('completed_date'));

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ILIKE', "%{$search}%")
                    ->orWhereHas('tailor', function ($sq) use ($search) {
                        $sq->where('name', 'ILIKE', "%{$search}%");
                    })
                    ->orWhereHas('brand', function ($sq) use ($search) {
                        $sq->where('name', 'ILIKE', "%{$search}%");
                    })
                    ->orWhereHas('article', function ($sq) use ($search) {
                        $sq->where('name', 'ILIKE', "%{$search}%");
                    });
            });
        }

        if ($brandId) {
            $query->where('brand_id', $brandId);
        }

        $deposits = $query->orderBy('created_at', 'desc')->get();

        // Group by tailor_id, brand_id, article_id, and size_id
        $groups = $deposits->groupBy(function ($item) {
            return "{$item->tailor_id}_{$item->brand_id}_{$item->article_id}_{$item->size_id}";
        })->map(function ($group) {
            $first = $group->first();

            // Calculate totals from deposits
            $totalSewingResult = $group->sum('total_sewing_result');
            $totalPrice = $group->sum('total_price');
            $hasOverdue = $group->contains('status', 'overdue');

            // Collect unique deposit dates
            $depositDates = $group->pluck('deposit_date')
                ->filter()
                ->map(fn($date) => $date->toIso8601String())
                ->unique()
                ->sortBy(fn($date) => $date)
                ->values()
                ->toArray();

            // Find ALL cutting_distributions that match the tailor/brand/article/size keys
            $distributions = \App\Models\CuttingDistribution::with('deposits')
                ->where('tailor_id', $first->tailor_id)
                ->where('brand_id', $first->brand_id)
                ->where('article_id', $first->article_id)
                ->where('size_id', $first->size_id)
                ->get();

            // Calculate total distributed and remaining from ALL matching distributions
            $totalDistributed = 0;
            $totalDepositRemaining = 0;

            foreach ($distributions as $dist) {
                $totalDistributed += $dist->total_cutting;
                $depositedSoFar = $dist->deposits->sum('total_sewing_result');
                $totalDepositRemaining += max(0, $dist->total_cutting - $depositedSoFar);
            }

            return [
                'id' => "{$first->tailor_id}_{$first->brand_id}_{$first->article_id}_{$first->size_id}",
                'tailor_id' => $first->tailor_id,
                'brand_id' => $first->brand_id,
                'article_id' => $first->article_id,
                'size_id' => $first->size_id,
                'tailor' => $first->tailor,
                'brand' => $first->brand,
                'article' => $first->article,
                'size' => $first->size,
                'total_sewing_result' => $totalSewingResult,
                'total_price' => $totalPrice,
                'has_overdue' => $hasOverdue,
                'total_distributed' => $totalDistributed,
                'total_deposit_remaining' => $totalDepositRemaining,
                'deposit_dates' => $depositDates,
                'entries' => $group,
            ];
        })->values();

        // Manual pagination
        $page = request()->input('page', 1);
        $total = $groups->count();
        $items = $groups->forPage($page, $perPage);

        return new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );
    }

    public function find(string $id)
    {
        return $this->model->with(['cuttingDistribution.cuttingResult.preOrder', 'tailor', 'brand', 'article', 'size'])->findOrFail($id);
    }

    public function create(array $data): DepositCuttingResult
    {
        $distribution = \App\Models\CuttingDistribution::with(['cuttingResult', 'tailor', 'deposits'])->findOrFail($data['cutting_distribution_id']);

        $existingDepositsCount = $distribution->deposits->count();

        $data['name'] = $distribution->name . '-DEP' . ($existingDepositsCount + 1);
        $data['brand_id'] = $distribution->brand_id;
        $data['article_id'] = $distribution->article_id;
        $data['size_id'] = $distribution->size_id;
        $data['tailor_id'] = $distribution->tailor_id;

        $data['total_price'] = (float) ($data['cutting_price_per_pcs'] ?? 0) * (int) $data['total_sewing_result'];

        if (!isset($data['status'])) {
            $totalAllDeposits = (int) $distribution->deposits->sum('total_sewing_result') + (int) $data['total_sewing_result'];
            if ($totalAllDeposits >= $distribution->total_cutting) {
                $data['status'] = 'done';
            } elseif (($data['deposit_date'] ?? null) && $distribution->deadline_date && $data['deposit_date'] > $distribution->deadline_date->format('Y-m-d')) {
                $data['status'] = 'overdue';
            } else {
                $data['status'] = 'in_progress';
            }
        }

        $deposit = $this->model->create($data);

        if ($totalAllDeposits >= $distribution->total_cutting) {
            $distribution->deposits()->where('id', '!=', $deposit->id)->update(['status' => 'done']);
        }

        $this->logCreate($deposit->toArray());

        return $deposit;
    }

    public function update(string $id, array $data): DepositCuttingResult
    {
        $deposit = $this->model->findOrFail($id);
        $oldData = $deposit->toArray();

        if (isset($data['total_sewing_result']) || isset($data['cutting_price_per_pcs'])) {
            $pricePerPcs = (float) ($data['cutting_price_per_pcs'] ?? $deposit->cutting_price_per_pcs);
            $sewingResult = (int) ($data['total_sewing_result'] ?? $deposit->total_sewing_result);
            $data['total_price'] = $pricePerPcs * $sewingResult;
        }

        if (isset($data['total_sewing_result']) || isset($data['deposit_date'])) {
            $distribution = $deposit->cuttingDistribution;
            $totalSewing = $data['total_sewing_result'] ?? $deposit->total_sewing_result;
            $depositDate = $data['deposit_date'] ?? $deposit->deposit_date?->format('Y-m-d');

            $totalAllDeposits = $distribution->deposits()
                ->where('id', '!=', $deposit->id)
                ->sum('total_sewing_result');
            $totalAllDeposits += $totalSewing;

            if ($totalAllDeposits >= $distribution->total_cutting) {
                $data['status'] = 'done';
            } elseif ($depositDate && $distribution->deadline_date && $depositDate > $distribution->deadline_date->format('Y-m-d')) {
                $data['status'] = 'overdue';
            } else {
                $data['status'] = 'in_progress';
            }
        }

        $deposit->update($data);
        $updatedRecord = $deposit->fresh();

        $this->logUpdate($oldData, $updatedRecord->toArray());

        return $updatedRecord;
    }

    public function delete(string $id): void
    {
        $deposit = $this->model->findOrFail($id);
        $recordData = $deposit->toArray();

        $deposit->delete();

        $this->logDelete($recordData);
    }

    public function createBatch(array $data): array
    {
        $ids = $data['distribution_ids'];
        $remaining = (int) $data['total_sewing_result'];
        $deposits = [];
        $createdData = [];

        $distributions = \App\Models\CuttingDistribution::with(['cuttingResult', 'tailor', 'deposits'])
            ->whereIn('id', $ids)
            ->orderBy('created_at')
            ->get();

        foreach ($distributions as $dist) {
            if ($remaining <= 0)
                break;
            $depositAvailable = $dist->total_cutting - $dist->deposits->sum('total_sewing_result');
            if ($depositAvailable <= 0)
                continue;

            $qty = min($remaining, $depositAvailable);
            $existingDepositsCount = $dist->deposits->count();

            $totalAllDeposits = $dist->deposits->sum('total_sewing_result') + $qty;

            $status = 'in_progress';
            $depositDate = $data['deposit_date'] ?? null;
            if ($totalAllDeposits >= $dist->total_cutting) {
                $status = 'done';
            } elseif ($depositDate && $dist->deadline_date && $depositDate > $dist->deadline_date->format('Y-m-d')) {
                $status = 'overdue';
            }

            $deposit = $this->model->create([
                'name' => $dist->name . '-DEP' . ($existingDepositsCount + 1),
                'cutting_distribution_id' => $dist->id,
                'tailor_id' => $dist->tailor_id,
                'brand_id' => $dist->brand_id,
                'article_id' => $dist->article_id,
                'size_id' => $dist->size_id,
                'total_sewing_result' => $qty,
                'cutting_price_per_pcs' => $data['cutting_price_per_pcs'] ?? 0,
                'total_price' => (float) ($data['cutting_price_per_pcs'] ?? 0) * $qty,
                'deposit_date' => $data['deposit_date'],
                'status' => $status,
                'quality_notes' => $data['quality_notes'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            if ($totalAllDeposits >= $dist->total_cutting) {
                $dist->deposits()->where('id', '!=', $deposit->id)->update(['status' => 'done']);
            }

            $deposits[] = $deposit;
            $createdData[] = $deposit->toArray();
            $remaining -= $qty;
        }

        $this->getActivityLogService()->log('deposit.batch_created', 'deposit', $createdData[0]['id'] ?? uniqid(), [
            'count' => count($deposits),
            'total_sewing_result' => $data['total_sewing_result'],
        ]);

        return $deposits;
    }
}