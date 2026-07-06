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

    public function trashed(Request $request): AnonymousResourceCollection
    {
        return RepairResource::collection($this->service->getTrashed(
            $request->integer('per_page', 15),
            $request->query('search')
        ));
    }

    public function restore(string $id): JsonResponse
    {
        $repair = $this->service->restore($id);
        return response()->json(new RepairResource($repair->load(['tailor', 'brand', 'article'])));
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

        $articles = \App\Models\Article::query()
            ->when($validated['brand_id'] ?? null, fn($q, $brandId) => $q->where('brand_id', $brandId))
            ->select('id', 'name')
            ->get();

        return response()->json(['data' => $articles]);
    }

    public function getSewingPrice(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tailor_id' => 'required|exists:tailors,id',
            'article_id' => 'required|exists:articles,id',
        ]);

        $price = \App\Models\DepositCuttingResult::query()
            ->join('cutting_distributions', 'deposit_cutting_results.cutting_distribution_id', '=', 'cutting_distributions.id')
            ->join('cutting_results', 'cutting_distributions.cutting_result_id', '=', 'cutting_results.id')
            ->join('pre_orders', 'cutting_results.pre_order_id', '=', 'pre_orders.id')
            ->join('articles', 'pre_orders.article_id', '=', 'articles.id')
            ->where('cutting_distributions.tailor_id', $validated['tailor_id'])
            ->where('articles.id', $validated['article_id'])
            ->value('deposit_cutting_results.cutting_price_per_pcs');

        return response()->json(['price' => $price ?? 0]);
    }
}