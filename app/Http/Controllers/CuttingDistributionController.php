<?php

namespace App\Http\Controllers;

use App\Services\CuttingDistributionService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use App\Http\Resources\CuttingDistributionResource;
use App\Http\Requests\CuttingDistribution\StoreCuttingDistributionRequest;
use App\Http\Requests\CuttingDistribution\StoreCuttingDistributionBatchRequest;
use App\Http\Requests\CuttingDistribution\UpdateCuttingDistributionRequest;

class CuttingDistributionController extends Controller
{
    public function __construct(protected CuttingDistributionService $service) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        return CuttingDistributionResource::collection($this->service->paginate(
            $request->integer('per_page', 15),
            $request->query('search'),
            $request->query('brand_filter')
        ));
    }

    public function remaining(Request $request)
    {
        $validated = $request->validate([
            'cutting_distribution_id' => 'required|exists:cutting_distributions,id',
        ]);

        $remaining = $this->service->getDepositRemaining($validated['cutting_distribution_id']);

        return response()->json($remaining);
    }

    public function store(StoreCuttingDistributionRequest $request)
    {
        $distribution = $this->service->create($request->validated());
        return response()->json(new CuttingDistributionResource($distribution->load(['cuttingResult.preOrder', 'tailor', 'brand', 'article', 'size'])), 201);
    }

    public function storeBatch(StoreCuttingDistributionBatchRequest $request)
    {
        $distributions = $this->service->createBatch($request->validated());
        return response()->json(CuttingDistributionResource::collection(
            collect($distributions)->map(fn ($d) => $d->load(['cuttingResult.preOrder', 'tailor', 'brand', 'article', 'size']))
        ), 201);
    }

    public function show(string $id)
    {
        return response()->json(new CuttingDistributionResource($this->service->find($id)));
    }

    public function update(UpdateCuttingDistributionRequest $request, string $id)
    {
        $distribution = $this->service->update($id, $request->validated());
        return response()->json(new CuttingDistributionResource($distribution->load(['cuttingResult.preOrder', 'tailor', 'brand', 'article', 'size'])));
    }

    public function destroy(string $id)
    {
        $this->service->delete($id);
        return response()->json(null, 204);
    }

    public function trashed(Request $request): AnonymousResourceCollection
    {
        return CuttingDistributionResource::collection($this->service->getTrashed(
            $request->integer('per_page', 15),
            $request->query('search')
        ));
    }

    public function restore(string $id)
    {
        $distribution = $this->service->restore($id);
        return response()->json(new CuttingDistributionResource($distribution->load(['cuttingResult.preOrder', 'tailor', 'brand', 'article', 'size'])));
    }
}