<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ArticleController extends Controller
{
    /**
     * Display a listing of articles.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Article::query();

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Search by name or code
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $articles = $query->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data' => $articles
        ]);
    }

    /**
     * Store a newly created article.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sewing_price' => 'required|numeric|min:0',
            'code' => 'required|string|max:50|unique:articles',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['created_by'] = auth()->id();
        $validated['updated_by'] = auth()->id();

        $article = Article::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Article created successfully',
            'data' => $article->load('createdBy', 'updatedBy')
        ], 201);
    }

    /**
     * Display the specified article.
     */
    public function show(Article $article): JsonResponse
    {
        $article->load(['createdBy', 'updatedBy']);

        return response()->json([
            'success' => true,
            'data' => $article
        ]);
    }

    /**
     * Update the specified article.
     */
    public function update(Request $request, Article $article): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'sewing_price' => 'sometimes|required|numeric|min:0',
            'code' => 'sometimes|required|string|max:50|unique:articles,code,' . $article->id,
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['updated_by'] = auth()->id();

        $article->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Article updated successfully',
            'data' => $article->fresh()->load('createdBy', 'updatedBy')
        ]);
    }

    /**
     * Remove the specified article.
     */
    public function destroy(Article $article): JsonResponse
    {
        $article->delete();

        return response()->json([
            'success' => true,
            'message' => 'Article deleted successfully'
        ]);
    }
}
