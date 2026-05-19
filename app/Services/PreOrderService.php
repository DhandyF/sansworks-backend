<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\PreOrder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PreOrderService extends BaseService
{
    private const MONTH_MAP = [
        1 => 'JAN', 2 => 'FEB', 3 => 'MAR', 4 => 'APR',
        5 => 'MEI', 6 => 'JUN', 7 => 'JUL', 8 => 'AGU',
        9 => 'SEP', 10 => 'OKT', 11 => 'NOV', 12 => 'DES',
    ];

    public function __construct(PreOrder $model)
    {
        $this->model = $model;
    }

    public function paginate(int $perPage = 15, string $search = null, string $brandId = null): LengthAwarePaginator
    {
        $query = $this->model->with(['brand', 'article', 'size', 'cuttingResults.distributions.deposits', 'shipments']);

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
        return $this->model->with(['brand', 'article', 'size', 'cuttingResults', 'shipments'])->findOrFail($id);
    }

    public function createBatch(string $brandId, string $name, string $preOrderDate, string $deadlineDate, array $articles): array
    {
        $records = [];
        $createdData = [];

        foreach ($articles as $article) {
            foreach ($article['sizes'] as $sizeItem) {
                $record = $this->model->create([
                    'brand_id' => $brandId,
                    'article_id' => $article['article_id'],
                    'size_id' => $sizeItem['size_id'],
                    'name' => $name,
                    'pre_order_date' => $preOrderDate,
                    'deadline_date' => $deadlineDate,
                    'total_pcs' => $sizeItem['total_pcs'],
                ]);
                $records[] = $record;
                $createdData[] = $record->toArray();
            }
        }

        $this->getActivityLogService()->log('pre_order.batch_created', 'preorder', $createdData[0]['id'] ?? uniqid(), [
            'count' => count($records),
            'name' => $name,
            'brand_id' => $brandId,
        ]);

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
            ->distinct()
            ->count('name');

        $number = str_pad($count + 1, 3, '0', STR_PAD_LEFT);
        $monthAbbr = self::MONTH_MAP[$month];
        $brandName = strtoupper(Brand::findOrFail($brandId)->name);

        return "PO-{$monthAbbr}-{$number}-{$brandName}";
    }
}