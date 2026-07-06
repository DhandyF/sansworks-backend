<?php

namespace App\Http\Controllers;

use App\Http\Requests\CuttingResult\StoreCuttingResultRequest;
use App\Http\Requests\CuttingResult\UpdateCuttingResultRequest;
use App\Http\Resources\CuttingResultResource;
use App\Services\CuttingResultService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CuttingResultController extends Controller
{
    public function __construct(protected CuttingResultService $service) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        return CuttingResultResource::collection($this->service->paginate(
            $request->integer('per_page', 15),
            $request->query('search'),
            $request->query('brand_filter')
        ));
    }

    public function remaining(Request $request)
    {
        $validated = $request->validate([
            'pre_order_id' => 'required|exists:pre_orders,id',
        ]);

        $remaining = $this->service->getRemaining($validated['pre_order_id']);

        return response()->json([
            'remaining' => $remaining,
            'total_pcs' => $remaining['total_pcs'],
            'cut_qty' => $remaining['cut_qty'],
            'available' => $remaining['available'],
        ]);
    }

    public function store(StoreCuttingResultRequest $request)
    {
        $cuttingResult = $this->service->create($request->validated());
        return response()->json(new CuttingResultResource($cuttingResult->load(['preOrder', 'brand', 'article', 'size'])), 201);
    }

    public function show(string $id)
    {
        return response()->json(new CuttingResultResource($this->service->find($id)));
    }

    public function update(UpdateCuttingResultRequest $request, string $id)
    {
        $cuttingResult = $this->service->update($id, $request->validated());
        return response()->json(new CuttingResultResource($cuttingResult->load(['preOrder', 'brand', 'article', 'size'])));
    }

    public function destroy(string $id)
    {
        $this->service->delete($id);
        return response()->json(null, 204);
    }

    public function trashed(Request $request): AnonymousResourceCollection
    {
        return CuttingResultResource::collection($this->service->getTrashed(
            $request->integer('per_page', 15),
            $request->query('search')
        ));
    }

    public function restore(string $id)
    {
        $cuttingResult = $this->service->restore($id);
        return response()->json(new CuttingResultResource($cuttingResult->load(['preOrder', 'brand', 'article', 'size'])));
    }
}