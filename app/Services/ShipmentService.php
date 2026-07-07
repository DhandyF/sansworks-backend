<?php

namespace App\Services;

use App\Models\Shipment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

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
            $query->whereHas('preOrder', fn($q) => $q->where('name', 'LIKE', "%{$search}%")->orWhere(DB::raw("LOWER(name)"), 'LIKE', DB::raw("LOWER('%{$search}%')")));
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
        $shipment = $this->model->create($data);
        $this->updatePreOrderCompletion($data['pre_order_id']);
        return $shipment;
    }

    public function update(string $id, array $data): Shipment
    {
        $shipment = $this->model->findOrFail($id);
        $oldData = $shipment->toArray();
        $preOrderId = $shipment->pre_order_id;

        $shipment->update($data);
        $updatedRecord = $shipment->fresh();

        $this->logUpdate($oldData, $updatedRecord->toArray());
        $this->updatePreOrderCompletion($preOrderId);

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
        $preOrderId = $shipment->pre_order_id;

        $shipment->delete();

        $this->logDelete($recordData);
        $this->updatePreOrderCompletion($preOrderId);
    }

    public function restore(string $id): \Illuminate\Database\Eloquent\Model
    {
        $shipment = $this->model->withTrashed()->findOrFail($id);
        $shipment->restore();

        $this->getActivityLogService()->log(
            $this->getSubjectType() . '.restored',
            $this->getSubjectType(),
            $shipment->id,
            $shipment->toArray()
        );

        $this->updatePreOrderCompletion($shipment->pre_order_id);

        return $shipment;
    }

    private function updatePreOrderCompletion(string $preOrderId): void
    {
        $preOrder = \App\Models\PreOrder::withoutTrashed()->with('shipments')->findOrFail($preOrderId);
        $totalShipped = $preOrder->shipments->sum('total_shipment');
        $isDone = $preOrder->total_pcs > 0 && $totalShipped >= $preOrder->total_pcs;

        $preOrder->update([
            'completed_date' => $isDone ? ($preOrder->completed_date ?? now()->toDateString()) : null,
        ]);
    }
}