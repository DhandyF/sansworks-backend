<?php

namespace App\Http\Controllers;

use App\Http\Requests\Repair\StoreRepairRequest;
use App\Http\Requests\Repair\UpdateRepairRequest;
use App\Http\Resources\RepairResource;
use App\Services\RepairService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class RepairController extends Controller
{
    public function __construct(protected RepairService $service) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        return RepairResource::collection($this->service->paginate(
            $request->integer('per_page', 15),
            $request->query('search'),
            $request->query('tailor_filter'),
            $request->query('brand_filter'),
            $request->query('article_filter')
        ));
    }

    public function store(StoreRepairRequest $request)
    {
        $repair = $this->service->create($request->validated());
        return response()->json(new RepairResource($repair->load(['tailor', 'brand', 'article'])), 201);
    }

    public function show(string $id)
    {
        return response()->json(new RepairResource($this->service->find($id)));
    }

    public function update(UpdateRepairRequest $request, string $id)
    {
        $repair = $this->service->update($id, $request->validated());
        return response()->json(new RepairResource($repair->load(['tailor', 'brand', 'article'])));
    }

    public function destroy(string $id)
    {
        $this->service->delete($id);
        return response()->json(null, 204);
    }

    public function generateName(Request $request)
    {
        $validated = $request->validate([
            'tailor_id' => 'required|exists:tailors,id',
            'article_id' => 'required|exists:articles,id',
        ]);

        $tailor = \App\Models\Tailor::findOrFail($validated['tailor_id']);
        $article = \App\Models\Article::findOrFail($validated['article_id']);

        $name = 'QC-' . $tailor->name . '-' . $article->name;

        return response()->json(['name' => $name]);
    }

    public function availableArticles(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'brand_id' => 'nullable|exists:brands,id',
        ]);

        $articlesWithPrice = \App\Models\DepositCuttingResult::query()
            ->join('cutting_distributions', 'deposit_cutting_results.cutting_distribution_id', '=', 'cutting_distributions.id')
            ->join('cutting_results', 'cutting_distributions.cutting_result_id', '=', 'cutting_results.id')
            ->join('pre_orders', 'cutting_results.pre_order_id', '=', 'pre_orders.id')
            ->join('articles', 'pre_orders.article_id', '=', 'articles.id')
            ->when($validated['brand_id'] ?? null, fn($q, $brandId) => $q->where('articles.brand_id', $brandId))
            ->select('articles.id', 'articles.name', 'deposit_cutting_results.cutting_price_per_pcs')
            ->distinct()
            ->get()
            ->groupBy('id')
            ->map(fn($group) => [
                'id' => $group->first()['id'],
                'name' => $group->first()['name'],
                'cutting_price_per_pcs' => $group->first()['cutting_price_per_pcs'],
            ])
            ->values();

        return response()->json(['data' => $articlesWithPrice]);
    }
}