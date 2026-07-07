<?php

namespace App\Services;

use App\Models\CuttingResult;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class CuttingResultService extends BaseService
{
    public function __construct(CuttingResult $model)
    {
        $this->model = $model;
    }

    public function paginate(int $perPage = 15, string $search = null, string $brandId = null): LengthAwarePaginator
    {
        $query = $this->model->with(['preOrder', 'brand', 'article', 'size']);

        if ($search) {
            $query->where('name', 'LIKE', "%{$search}%")->orWhere(DB::raw("LOWER(name)"), 'LIKE', DB::raw("LOWER('%{$search}%')"));
        }

        if ($brandId) {
            $query->where('brand_id', $brandId);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function find(string $id)
    {
        return $this->model->with(['preOrder', 'brand', 'article', 'size'])->findOrFail($id);
    }

    public function create(array $data): CuttingResult
    {
        // Always calculate excess cutting automatically - ignore any provided value
        $preOrder = \App\Models\PreOrder::findOrFail($data['pre_order_id']);
        $totalPcs = $preOrder->total_pcs;
        $cutQty = $this->model->where('pre_order_id', $data['pre_order_id'])->sum('total_cutting');
        $available = $totalPcs - (int) $cutQty;

        // Calculate excess if cutting exceeds available
        $data['excess_cutting'] = max(0, $data['total_cutting'] - $available);
        $data['remaining'] = max(0, $data['total_cutting'] - $data['excess_cutting']);

        $preOrder = \App\Models\PreOrder::with(['brand', 'article', 'size'])->findOrFail($data['pre_order_id']);
        $article = \App\Models\Article::findOrFail($data['article_id']);
        $size = \App\Models\Size::findOrFail($data['size_id']);

        $data['name'] = $preOrder->name . '-' . strtoupper($article->name) . '-' . strtoupper($size->abbreviation);
        $data['brand_id'] = $preOrder->brand_id;

        $record = $this->model->create($data);

        $this->logCreate($record->toArray());

        return $record;
    }

    public function update(string $id, array $data): CuttingResult
    {
        $cuttingResult = $this->model->findOrFail($id);
        $oldData = $cuttingResult->toArray();

        if (isset($data['total_cutting'])) {
            $distributed = $cuttingResult->distributions()->sum('total_cutting');

            // Calculate excess if not provided
            if (!isset($data['excess_cutting'])) {
                $preOrder = \App\Models\PreOrder::findOrFail($cuttingResult->pre_order_id);
                $totalPcs = $preOrder->total_pcs;
                $cutQty = $this->model->where('pre_order_id', $cuttingResult->pre_order_id)->sum('total_cutting');
                $available = $totalPcs - (int) $cutQty;

                // Calculate new excess based on the updated total_cutting
                $newCutQty = $cutQty - $cuttingResult->total_cutting + $data['total_cutting'];
                $data['excess_cutting'] = max(0, $newCutQty - $available);
            }

            $data['remaining'] = max(0, $data['total_cutting'] - ($data['excess_cutting'] ?? 0) - $distributed);
        }

        $cuttingResult->update($data);
        $updatedRecord = $cuttingResult->fresh();

        $this->logUpdate($oldData, $updatedRecord->toArray());

        return $updatedRecord;
    }

    public function getRemaining(string $preOrderId): array
    {
        $preOrder = \App\Models\PreOrder::findOrFail($preOrderId);
        $totalPcs = $preOrder->total_pcs;
        $cutQty = $this->model->where('pre_order_id', $preOrderId)->sum('total_cutting');

        return [
            'total_pcs' => $totalPcs,
            'cut_qty' => (int) $cutQty,
            'available' => $totalPcs - (int) $cutQty,
        ];
    }

    public function delete(string $id): void
    {
        $cuttingResult = $this->model->findOrFail($id);
        $preOrderId = $cuttingResult->pre_order_id;
        $articleId = $cuttingResult->article_id;
        $sizeId = $cuttingResult->size_id;
        $recordData = $cuttingResult->toArray();

        $cuttingResult->delete();

        $this->recalculateExcessCutting($preOrderId, $articleId, $sizeId);

        $this->logDelete($recordData);
    }

    private function recalculateExcessCutting(string $preOrderId, string $articleId, string $sizeId): void
    {
        $preOrder = \App\Models\PreOrder::findOrFail($preOrderId);
        $totalPcs = $preOrder->total_pcs;

        $results = $this->model
            ->where('pre_order_id', $preOrderId)
            ->where('article_id', $articleId)
            ->where('size_id', $sizeId)
            ->orderBy('created_at')
            ->get();

        $cumulative = 0;
        foreach ($results as $result) {
            $available = max(0, $totalPcs - $cumulative);
            $newExcess = max(0, $result->total_cutting - $available);
            $distributed = $result->distributions()->sum('total_cutting');
            $newRemaining = max(0, $result->total_cutting - $newExcess - $distributed);

            $result->update([
                'excess_cutting' => $newExcess,
                'remaining' => $newRemaining,
            ]);

            $cumulative += ($result->total_cutting - $newExcess);
        }
    }
}