<?php

namespace App\Http\Controllers;

use App\Http\Requests\DepositCuttingResult\StoreDepositCuttingResultRequest;
use App\Http\Requests\DepositCuttingResult\UpdateDepositCuttingResultRequest;
use App\Http\Resources\DepositCuttingResultResource;
use App\Services\DepositCuttingResultService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DepositCuttingResultController extends Controller
{
    public function __construct(protected DepositCuttingResultService $service) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        return DepositCuttingResultResource::collection($this->service->paginate(
            $request->integer('per_page', 15),
            $request->query('search'),
            $request->query('brand_filter')
        ));
    }

    public function store(StoreDepositCuttingResultRequest $request)
    {
        $deposit = $this->service->create($request->validated());
        return response()->json(new DepositCuttingResultResource($deposit->load(['cuttingDistribution.cuttingResult.preOrder', 'tailor', 'brand', 'article', 'size'])), 201);
    }

    public function show(string $id)
    {
        return response()->json(new DepositCuttingResultResource($this->service->find($id)));
    }

    public function update(UpdateDepositCuttingResultRequest $request, string $id)
    {
        $deposit = $this->service->update($id, $request->validated());
        return response()->json(new DepositCuttingResultResource($deposit->load(['cuttingDistribution.cuttingResult.preOrder', 'tailor', 'brand', 'article', 'size'])));
    }

    public function destroy(string $id)
    {
        $this->service->delete($id);
        return response()->json(null, 204);
    }
}