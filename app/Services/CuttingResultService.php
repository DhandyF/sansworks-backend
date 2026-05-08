<?php

namespace App\Services;

use App\Models\CuttingResult;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

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
            $query->where('name', 'LIKE', "%{$search}%");
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
        $data['remaining'] = $data['total_cutting'];

        $preOrder = \App\Models\PreOrder::with(['brand', 'article', 'size'])->findOrFail($data['pre_order_id']);
        $article = \App\Models\Article::findOrFail($data['article_id']);
        $size = \App\Models\Size::findOrFail($data['size_id']);

        $data['name'] = $preOrder->name . '-' . strtoupper($article->name) . '-' . strtoupper($size->abbreviation);
        $data['brand_id'] = $preOrder->brand_id;

        return $this->model->create($data);
    }

    public function update(string $id, array $data): CuttingResult
    {
        $cuttingResult = $this->model->findOrFail($id);

        if (isset($data['total_cutting'])) {
            $distributed = $cuttingResult->distributions()->sum('total_cutting');
            $data['remaining'] = $data['total_cutting'] - $distributed;
        }

        $cuttingResult->update($data);
        return $cuttingResult->fresh();
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
        $cuttingResult->delete();
    }
}