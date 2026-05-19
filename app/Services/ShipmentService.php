<?php

namespace App\Services;

use App\Models\Shipment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ShipmentService extends BaseService
{
    public function __construct(Shipment $model)
    {
        $this->model = $model;
    }

    public function paginate(int $perPage = 15, string $search = null, string $brandId = null): LengthAwarePaginator
    {
        $query = $this->model->with(['preOrder.brand', 'preOrder.article', 'preOrder.size']);

        if ($search) {
            $query->whereHas('preOrder', fn($q) => $q->where('name', 'LIKE', "%{$search}%"));
        }

        if ($brandId) {
            $query->whereHas('preOrder', fn($q) => $q->where('brand_id', $brandId));
        }

        return $query->orderBy('shipment_date', 'desc')->paginate($perPage);
    }

    public function find(string $id)
    {
        return $this->model->with(['preOrder.brand', 'preOrder.article', 'preOrder.size'])->findOrFail($id);
    }

    public function create(array $data): Shipment
    {
        return $this->model->create($data);
    }

    public function update(string $id, array $data): Shipment
    {
        $shipment = $this->model->findOrFail($id);
        $oldData = $shipment->toArray();

        $shipment->update($data);
        $updatedRecord = $shipment->fresh();
        
        $this->logUpdate($oldData, $updatedRecord->toArray());
        
        return $updatedRecord;
    }

    public function getTotalShipped(string $preOrderId): int
    {
        return $this->model->where('pre_order_id', $preOrderId)->sum('total_shipment');
    }

    public function delete(string $id): void
    {
        $shipment = $this->model->findOrFail($id);
        $recordData = $shipment->toArray();
        
        $shipment->delete();
        
        $this->logDelete($recordData);
    }
}