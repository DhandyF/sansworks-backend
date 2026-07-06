<?php

namespace App\Http\Controllers;

use App\Http\Requests\Tailor\StoreTailorRequest;
use App\Http\Requests\Tailor\UpdateTailorRequest;
use App\Http\Resources\TailorResource;
use App\Services\TailorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TailorController extends Controller
{
    public function __construct(protected TailorService $service) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        return TailorResource::collection($this->service->paginate($request->integer('per_page', 15), $request->query('search')));
    }

    public function store(StoreTailorRequest $request): JsonResponse
    {
        $tailor = $this->service->create($request->validated());

        return response()->json(new TailorResource($tailor), 201);
    }

    public function show(string $id): JsonResponse
    {
        return response()->json(new TailorResource($this->service->find($id)));
    }

    public function update(UpdateTailorRequest $request, string $id): JsonResponse
    {
        $tailor = $this->service->update($id, $request->validated());

        return response()->json(new TailorResource($tailor));
    }

    public function destroy(string $id): JsonResponse
    {
        $this->service->delete($id);

        return response()->json(null, 204);
    }

    public function trashed(Request $request): AnonymousResourceCollection
    {
        return TailorResource::collection($this->service->getTrashed($request->integer('per_page', 15), $request->query('search')));
    }

    public function restore(string $id): JsonResponse
    {
        return response()->json(new TailorResource($this->service->restore($id)));
    }
}
