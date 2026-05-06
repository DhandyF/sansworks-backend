<?php

namespace App\Services;

use App\Models\PreOrder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PreOrderService extends BaseService
{
    private const NUMBER_MAP = [
        1 => 'satu', 2 => 'dua', 3 => 'tiga', 4 => 'empat', 5 => 'lima',
        6 => 'enam', 7 => 'tujuh', 8 => 'delapan', 9 => 'sembilan', 10 => 'sepuluh',
        11 => 'sebelas', 12 => 'dua belas',
    ];

    private const MONTH_MAP = [
        1 => 'januari', 2 => 'februari', 3 => 'maret', 4 => 'april',
        5 => 'mei', 6 => 'juni', 7 => 'juli', 8 => 'agustus',
        9 => 'september', 10 => 'oktober', 11 => 'november', 12 => 'desember',
    ];

    public function __construct(PreOrder $model)
    {
        $this->model = $model;
    }

    public function paginate(int $perPage = 15, string $search = null, string $brandId = null): LengthAwarePaginator
    {
        $query = $this->model->with(['brand', 'article', 'size']);

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
        return $this->model->with(['brand', 'article', 'size'])->findOrFail($id);
    }

    public function createBatch(string $brandId, string $name, string $preOrderDate, string $deadlineDate, array $articles): array
    {
        $records = [];

        foreach ($articles as $article) {
            foreach ($article['sizes'] as $sizeItem) {
                $records[] = $this->model->create([
                    'brand_id' => $brandId,
                    'article_id' => $article['article_id'],
                    'size_id' => $sizeItem['size_id'],
                    'name' => $name,
                    'pre_order_date' => $preOrderDate,
                    'deadline_date' => $deadlineDate,
                    'total_pcs' => $sizeItem['total_pcs'],
                ]);
            }
        }

        return $records;
    }

    public function getNextName(string $brandId): string
    {
        return $this->generateName($brandId);
    }

    public function create(array $data): PreOrder
    {
        if (empty($data['name'])) {
            $data['name'] = $this->generateName($data['brand_id']);
        }

        return $this->model->create($data);
    }

    private function generateName(string $brandId): string
    {
        $now = now();
        $month = $now->month;
        $year = $now->year;

        $count = $this->model
            ->where('brand_id', $brandId)
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->count();

        $number = self::NUMBER_MAP[$count + 1] ?? (string) ($count + 1);
        $monthName = self::MONTH_MAP[$month];

        return "po-{$number}-{$monthName}";
    }
}