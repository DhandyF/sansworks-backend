<?php

namespace App\Http\Controllers;

use App\Http\Requests\PreOrder\StorePreOrderBatchRequest;
use App\Http\Requests\PreOrder\StorePreOrderRequest;
use App\Http\Requests\PreOrder\UpdatePreOrderRequest;
use App\Http\Resources\PreOrderResource;
use App\Services\PreOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PreOrderController extends Controller
{
    public function __construct(protected PreOrderService $service) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        return PreOrderResource::collection($this->service->paginate(
            $request->integer('per_page', 15),
            $request->query('search'),
            $request->query('brand_filter')
        ));
    }

    public function nextName(Request $request): JsonResponse
    {
        $validated = $request->validate(['brand_id' => 'required|exists:brands,id']);
        return response()->json(['name' => $this->service->getNextName($validated['brand_id'])]);
    }

    public function storeBatch(StorePreOrderBatchRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $records = $this->service->createBatch(
            $validated['brand_id'],
            $validated['article_id'],
            $validated['items']
        );

        return response()->json(PreOrderResource::collection(
            collect($records)->map(fn ($r) => $r->load(['brand', 'article', 'size']))
        ), 201);
    }

    public function store(StorePreOrderRequest $request): JsonResponse
    {
        $preOrder = $this->service->create($request->validated());

        return response()->json(new PreOrderResource($preOrder->load(['brand', 'article', 'size'])), 201);
    }

    public function show(string $id): JsonResponse
    {
        return response()->json(new PreOrderResource($this->service->find($id)));
    }

    public function update(UpdatePreOrderRequest $request, string $id): JsonResponse
    {
        $preOrder = $this->service->update($id, $request->validated());

        return response()->json(new PreOrderResource($preOrder->load(['brand', 'article', 'size'])));
    }

    public function destroy(string $id): JsonResponse
    {
        $this->service->delete($id);

        return response()->json(null, 204);
    }
}