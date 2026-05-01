<?php

namespace App\Http\Controllers;

use App\Http\Requests\Brand\StoreBrandRequest;
use App\Http\Requests\Brand\UpdateBrandRequest;
use App\Http\Resources\BrandResource;
use App\Services\BrandService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BrandController extends Controller
{
    public function __construct(protected BrandService $service) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        return BrandResource::collection($this->service->paginate($request->integer('per_page', 15)));
    }

    public function store(StoreBrandRequest $request): JsonResponse
    {
        $brand = $this->service->create($request->validated());

        return response()->json(new BrandResource($brand), 201);
    }

    public function show(string $id): JsonResponse
    {
        return response()->json(new BrandResource($this->service->find($id)));
    }

    public function update(UpdateBrandRequest $request, string $id): JsonResponse
    {
        $brand = $this->service->update($id, $request->validated());

        return response()->json(new BrandResource($brand));
    }

    public function destroy(string $id): JsonResponse
    {
        $this->service->delete($id);

        return response()->json(null, 204);
    }
}
