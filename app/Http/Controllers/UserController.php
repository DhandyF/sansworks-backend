<?php

namespace App\Http\Controllers;

use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UserController extends Controller
{
    public function __construct(protected UserService $service) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = $this->service->paginate($request->integer('per_page', 15), $request->query('search'));
        $query->load('brands');
        return UserResource::collection($query);
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $data = $request->validated();
        $brands = $data['brands'] ?? null;
        unset($data['brands']);

        $user = $this->service->create($data);

        if ($brands !== null) {
            $user->brands()->sync($brands);
        }

        $user->load('brands');

        return response()->json(new UserResource($user), 201);
    }

    public function show(string $id): JsonResponse
    {
        $user = $this->service->find($id);
        $user->load('brands');
        return response()->json(new UserResource($user));
    }

    public function update(UpdateUserRequest $request, string $id): JsonResponse
    {
        $data = $request->validated();
        $brands = $data['brands'] ?? null;
        unset($data['brands']);

        $user = $this->service->update($id, $data);

        if ($brands !== null) {
            $user->brands()->sync($brands);
        }

        $user->load('brands');

        return response()->json(new UserResource($user));
    }

    public function destroy(string $id): JsonResponse
    {
        $this->service->delete($id);

        return response()->json(null, 204);
    }
}
