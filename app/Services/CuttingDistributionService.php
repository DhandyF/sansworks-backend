<?php

namespace App\Services;

use App\Models\CuttingDistribution;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class CuttingDistributionService extends BaseService
{
    public function __construct(CuttingDistribution $model)
    {
        $this->model = $model;
    }

    public function paginate(int $perPage = 15, string $search = null, string $brandId = null): LengthAwarePaginator
    {
        $query = $this->model->with(['cuttingResult.preOrder', 'tailor', 'brand', 'article', 'size', 'deposits']);

        if ($search) {
            $lowerSearch = strtolower($search);
            $query->where(function($q) use ($lowerSearch) {
                $q->whereRaw('LOWER(name) LIKE ?', ["%{$lowerSearch}%"])
                  ->orWhereHas('tailor', function($tq) use ($lowerSearch) {
                      $tq->whereRaw('LOWER(name) LIKE ?', ["%{$lowerSearch}%"]);
                  })
                  ->orWhereHas('brand', function($bq) use ($lowerSearch) {
                      $bq->whereRaw('LOWER(name) LIKE ?', ["%{$lowerSearch}%"]);
                  })
                  ->orWhereHas('cuttingResult.preOrder', function($pq) use ($lowerSearch) {
                      $pq->whereRaw('LOWER(name) LIKE ?', ["%{$lowerSearch}%"]);
                  });
            });
        }

        if ($brandId) {
            $query->where('brand_id', $brandId);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function find(string $id)
    {
        return $this->model->with(['cuttingResult.preOrder', 'tailor', 'brand', 'article', 'size', 'deposits'])->findOrFail($id);
    }

    public function create(array $data): CuttingDistribution
    {
        $cuttingResult = \App\Models\CuttingResult::with(['brand', 'article', 'size'])->findOrFail($data['cutting_result_id']);
        $tailor = \App\Models\Tailor::findOrFail($data['tailor_id']);

        $data['name'] = $cuttingResult->name . '-' . strtoupper($tailor->name);
        $data['brand_id'] = $cuttingResult->brand_id;
        $data['article_id'] = $cuttingResult->article_id;
        $data['size_id'] = $cuttingResult->size_id;

        $distribution = $this->model->create($data);

        $cuttingResult->decrement('remaining', $data['total_cutting']);

        $this->logCreate($distribution->toArray());

        return $distribution;
    }

    public function update(string $id, array $data): CuttingDistribution
    {
        $distribution = $this->model->findOrFail($id);
        $oldData = $distribution->toArray();

        if (isset($data['total_cutting'])) {
            $cuttingResult = $distribution->cuttingResult;
            $oldQty = $distribution->total_cutting;
            $newQty = $data['total_cutting'];
            $diff = $newQty - $oldQty;

            $cuttingResult->increment('remaining', $oldQty);
            $cuttingResult->decrement('remaining', $newQty);
        }

        $distribution->update($data);
        $updatedRecord = $distribution->fresh();

        $this->logUpdate($oldData, $updatedRecord->toArray());

        return $updatedRecord;
    }

    public function delete(string $id): void
    {
        $distribution = $this->model->findOrFail($id);
        $cuttingResult = $distribution->cuttingResult;
        $recordData = $distribution->toArray();

        $cuttingResult->increment('remaining', $distribution->total_cutting);

        $distribution->delete();

        $this->logDelete($recordData);
    }

    public function getDepositRemaining(string $distributionId): array
    {
        $distribution = $this->model->findOrFail($distributionId);
        $totalCutting = $distribution->total_cutting;
        $deposited = $distribution->deposits()->sum('total_sewing_result');

        return [
            'total_cutting' => $totalCutting,
            'deposited' => (int) $deposited,
            'available' => $totalCutting - (int) $deposited,
        ];
    }

    public function createBatch(array $data): array
    {
        $cuttingResults = \App\Models\CuttingResult::where('name', $data['cutting_result_name'])
            ->orderBy('cutting_date')
            ->get();

        $tailor = \App\Models\Tailor::findOrFail($data['tailor_id']);

        $remaining = $data['total_cutting'];
        $distributions = [];
        $createdData = [];

        foreach ($cuttingResults as $cr) {
            if ($remaining <= 0) break;
            if ($cr->remaining <= 0) continue;

            $qty = min($remaining, $cr->remaining);

            $distribution = $this->model->create([
                'name' => $cr->name . '-' . strtoupper($tailor->name),
                'cutting_result_id' => $cr->id,
                'tailor_id' => $data['tailor_id'],
                'brand_id' => $cr->brand_id,
                'article_id' => $cr->article_id,
                'size_id' => $cr->size_id,
                'total_cutting' => $qty,
                'taken_date' => $data['taken_date'],
                'deadline_date' => $data['deadline_date'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            $cr->decrement('remaining', $qty);
            $distributions[] = $distribution;
            $createdData[] = $distribution->toArray();
            $remaining -= $qty;
        }

        $this->getActivityLogService()->log('distribution.batch_created', 'distribution', $createdData[0]['id'] ?? uniqid(), [
            'count' => count($distributions),
            'tailor' => $tailor->name,
        ]);

        return $distributions;
    }
}