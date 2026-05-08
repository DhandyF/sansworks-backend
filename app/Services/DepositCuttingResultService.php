<?php

namespace App\Services;

use App\Models\DepositCuttingResult;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class DepositCuttingResultService extends BaseService
{
    public function __construct(DepositCuttingResult $model)
    {
        $this->model = $model;
    }

    public function paginate(int $perPage = 15, string $search = null, string $brandId = null): LengthAwarePaginator
    {
        $query = $this->model->with(['cuttingDistribution.cuttingResult.preOrder', 'tailor', 'brand', 'article', 'size']);

        if ($search) {
            $query->where('name', 'LIKE', "%{$search}%");
        }

        if ($brandId) {
            $query->where('brand_id', $brandId);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
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

        return $deposit;
    }

    public function update(string $id, array $data): DepositCuttingResult
    {
        $deposit = $this->model->findOrFail($id);

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
        return $deposit->fresh();
    }

    public function delete(string $id): void
    {
        $deposit = $this->model->findOrFail($id);
        $deposit->delete();
    }
}