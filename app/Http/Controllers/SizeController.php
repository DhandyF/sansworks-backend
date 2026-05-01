<?php

namespace App\Http\Controllers;

use App\Http\Requests\Size\StoreSizeRequest;
use App\Http\Requests\Size\UpdateSizeRequest;
use App\Http\Resources\SizeResource;
use App\Services\SizeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SizeController extends Controller
{
    public function __construct(protected SizeService $service) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        return SizeResource::collection($this->service->paginate($request->integer('per_page', 15)));
    }

    public function store(StoreSizeRequest $request): JsonResponse
    {
        $size = $this->service->create($request->validated());

        return response()->json(new SizeResource($size), 201);
    }

    public function show(string $id): JsonResponse
    {
        return response()->json(new SizeResource($this->service->find($id)));
    }

    public function update(UpdateSizeRequest $request, string $id): JsonResponse
    {
        $size = $this->service->update($id, $request->validated());

        return response()->json(new SizeResource($size));
    }

    public function destroy(string $id): JsonResponse
    {
        $this->service->delete($id);

        return response()->json(null, 204);
    }
}
