<?php

namespace App\Http\Controllers\API;

use App\Http\Concerns\HasPagination;
use App\Http\Controllers\Controller;
use App\Http\Requests\SizeRequest;
use App\Http\Resources\SizeResource;
use App\Models\Size;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SizeController extends Controller
{
    use HasPagination;

    /**
     * Display a listing of sizes.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $this->getPerPage($request);

        $query = Size::query();

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('abbreviation', 'like', "%{$search}%");
            });
        }

        if ($perPage === 'all') {
            $items = $query->orderBy('sort_order')->orderBy('name')->get();
            $items = SizeResource::collection($items)->resolve();

            return response()->json([
                'success' => true,
                'data' => $items,
            ]);
        }

        $sizes = $query->orderBy('sort_order')
            ->orderBy('name')
            ->paginate($perPage);

        return $this->paginatedResponse($sizes, SizeResource::class);
    }

    /**
     * Store a newly created size.
     */
    public function store(SizeRequest $request): SizeResource
    {
        $size = Size::create($request->validated());
        return new SizeResource($size);
    }

    /**
     * Display the specified size.
     */
    public function show(Size $size): SizeResource
    {
        return new SizeResource($size);
    }

    /**
     * Update the specified size.
     */
    public function update(SizeRequest $request, Size $size): SizeResource
    {
        $size->update($request->validated());
        return new SizeResource($size->fresh());
    }

    /**
     * Remove the specified size.
     */
    public function destroy($id): JsonResponse
    {
        try {
            $size = Size::withTrashed()->findOrFail($id);

            if ($size->trashed()) {
                $size->forceDelete();
                return response()->json([
                    'success' => true,
                    'message' => 'Size permanently deleted'
                ]);
            }

            $size->delete();

            return response()->json([
                'success' => true,
                'message' => 'Size deleted successfully'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Size not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete size: ' . $e->getMessage()
            ], 500);
        }
    }
}