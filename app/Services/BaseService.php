<?php

namespace App\Services;

use App\Traits\LogsActivity;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

abstract class BaseService
{
    use LogsActivity;

    protected Model $model;

    public function paginate(int $perPage = 15, string $search = null, string $searchColumn = 'name'): LengthAwarePaginator
    {
        $query = $this->model->newQuery();

        if ($search) {
            $query->where($searchColumn, 'LIKE', "%{$search}%")->orWhere(DB::raw("LOWER({$searchColumn})"), 'LIKE', DB::raw("LOWER('%{$search}%')"));
        }

        return $query->paginate($perPage);
    }

    public function find(string $id)
    {
        return $this->model->findOrFail($id);
    }

    public function create(array $data)
    {
        $record = DB::transaction(fn () => $this->model->create($data));
        
        $this->logCreate($record->toArray());
        
        return $record;
    }

    public function update(string $id, array $data)
    {
        $record = $this->find($id);
        $oldData = $record->toArray();

        $updatedRecord = DB::transaction(fn () => tap($record)->update($data));
        
        $this->logUpdate($oldData, $updatedRecord->toArray());
        
        return $updatedRecord;
    }

    public function delete(string $id): void
    {
        $record = $this->find($id);
        $recordData = $record->toArray();

        DB::transaction(fn () => $record->delete());

        $this->logDelete($recordData);
    }

    public function getTrashed(int $perPage = 15, string $search = null, string $searchColumn = 'name'): LengthAwarePaginator
    {
        $query = $this->model->onlyTrashed();

        if ($search) {
            $query->where($searchColumn, 'LIKE', "%{$search}%");
        }

        return $query->latest('deleted_at')->paginate($perPage);
    }

    public function restore(string $id): Model
    {
        $record = $this->model->withTrashed()->findOrFail($id);
        $record->restore();

        $this->getActivityLogService()->log(
            $this->getSubjectType() . '.restored',
            $this->getSubjectType(),
            $record->id,
            $record->toArray()
        );

        return $record;
    }

    public function forceDelete(string $id): void
    {
        $record = $this->model->withTrashed()->findOrFail($id);
        $recordData = $record->toArray();

        DB::transaction(fn () => $record->forceDelete());

        $this->getActivityLogService()->log(
            $this->getSubjectType() . '.force_deleted',
            $this->getSubjectType(),
            $recordData['id'] ?? uniqid(),
            $recordData
        );
    }
}
