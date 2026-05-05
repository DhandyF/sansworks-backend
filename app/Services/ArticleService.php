<?php

namespace App\Services;

use App\Models\Article;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ArticleService extends BaseService
{
    public function __construct(Article $model)
    {
        $this->model = $model;
    }

    public function paginate(int $perPage = 15, string $search = null, string $searchColumn = 'name', string $brandId = null): LengthAwarePaginator
    {
        $query = $this->model->with('brand');

        if ($search) {
            $query->where('name', 'LIKE', "%{$search}%");
        }

        if ($brandId) {
            $query->where('brand_id', $brandId);
        }

        return $query->paginate($perPage);
    }

    public function find(string $id)
    {
        return $this->model->with('brand')->findOrFail($id);
    }

    public function getByBrand(string $brandId, int $perPage = 1000)
    {
        return $this->model->with('brand')->where('brand_id', $brandId)->paginate($perPage);
    }
}
