<?php

namespace App\Services;

use App\Models\Repair;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class RepairService extends BaseService
{
    public function __construct(Repair $model)
    {
        $this->model = $model;
    }

    public function paginate(int $perPage = 15, string $search = null, string $tailorId = null, string $brandId = null, string $articleId = null): LengthAwarePaginator
    {
        $query = $this->model->with(['tailor', 'brand', 'article']);

        if ($search) {
            $query->where('name', 'LIKE', "%{$search}%")->orWhere(DB::raw("LOWER(name)"), 'LIKE', DB::raw("LOWER('%{$search}%')"));
        }

        if ($tailorId) {
            $query->where('tailor_id', $tailorId);
        }

        if ($brandId) {
            $query->where('brand_id', $brandId);
        }

        if ($articleId) {
            $query->where('article_id', $articleId);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function find(string $id)
    {
        return $this->model->with(['tailor', 'brand', 'article', 'deposits'])->findOrFail($id);
    }

    public function create(array $data): Repair
    {
        return $this->model->create($data);
    }

    public function update(string $id, array $data): Repair
    {
        $repair = $this->model->findOrFail($id);
        $oldData = $repair->toArray();

        $repair->update($data);
        $updatedRecord = $repair->fresh();
        
        $this->logUpdate($oldData, $updatedRecord->toArray());
        
        return $updatedRecord;
    }

    public function updateStatus(string $id, string $status): Repair
    {
        $repair = $this->model->findOrFail($id);
        $oldData = $repair->toArray();

        $repair->update(['status' => $status]);
        $updatedRecord = $repair->fresh();
        
        $this->logUpdate($oldData, $updatedRecord->toArray());
        
        return $updatedRecord;
    }

    public function delete(string $id): void
    {
        $repair = $this->model->findOrFail($id);
        $recordData = $repair->toArray();
        
        $repair->delete();
        
        $this->logDelete($recordData);
    }
}