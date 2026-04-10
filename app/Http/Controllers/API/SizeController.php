<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\SizeRequest;
use App\Http\Resources\SizeResource;
use App\Models\Size;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SizeController extends Controller
{
    /**
     * Display a listing of sizes.
     */
    public function index(): AnonymousResourceCollection
    {
        $sizes = Size::orderBy('sort_order')->orderBy('name')->get();
        return SizeResource::collection($sizes);
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
    public function destroy(Size $size): JsonResponse
    {
        $size->delete();

        return response()->json([
            'success' => true,
            'message' => 'Size deleted successfully'
        ]);
    }
}
