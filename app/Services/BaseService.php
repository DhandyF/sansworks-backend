<?php

namespace App\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

abstract class BaseService
{
    protected Model $model;

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->paginate($perPage);
    }

    public function find(string $id)
    {
        return $this->model->findOrFail($id);
    }

    public function create(array $data)
    {
        return DB::transaction(fn () => $this->model->create($data));
    }

    public function update(string $id, array $data)
    {
        $record = $this->find($id);

        return DB::transaction(fn () => tap($record)->update($data));
    }

    public function delete(string $id): void
    {
        $record = $this->find($id);

        DB::transaction(fn () => $record->delete());
    }
}
