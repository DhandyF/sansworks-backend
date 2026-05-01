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
        return UserResource::collection($this->service->paginate($request->integer('per_page', 15)));
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = $this->service->create($request->validated());

        return response()->json(new UserResource($user), 201);
    }

    public function show(string $id): JsonResponse
    {
        return response()->json(new UserResource($this->service->find($id)));
    }

    public function update(UpdateUserRequest $request, string $id): JsonResponse
    {
        $user = $this->service->update($id, $request->validated());

        return response()->json(new UserResource($user));
    }

    public function destroy(string $id): JsonResponse
    {
        $this->service->delete($id);

        return response()->json(null, 204);
    }
}
