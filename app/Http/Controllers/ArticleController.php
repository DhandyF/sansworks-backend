<?php

namespace App\Http\Controllers;

use App\Http\Requests\Article\StoreArticleRequest;
use App\Http\Requests\Article\UpdateArticleRequest;
use App\Http\Resources\ArticleResource;
use App\Services\ArticleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ArticleController extends Controller
{
    public function __construct(protected ArticleService $service) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        if ($request->has('brand_id')) {
            return ArticleResource::collection(
                $this->service->getByBrand($request->string('brand_id'), $request->integer('per_page', 1000))
            );
        }

        return ArticleResource::collection($this->service->paginate($request->integer('per_page', 15)));
    }

    public function store(StoreArticleRequest $request): JsonResponse
    {
        $article = $this->service->create($request->validated());

        return response()->json(new ArticleResource($article->load('brand')), 201);
    }

    public function show(string $id): JsonResponse
    {
        return response()->json(new ArticleResource($this->service->find($id)));
    }

    public function update(UpdateArticleRequest $request, string $id): JsonResponse
    {
        $article = $this->service->update($id, $request->validated());

        return response()->json(new ArticleResource($article->load('brand')));
    }

    public function destroy(string $id): JsonResponse
    {
        $this->service->delete($id);

        return response()->json(null, 204);
    }
}
