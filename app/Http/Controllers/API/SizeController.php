<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Size;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SizeController extends Controller
{
    /**
     * Display a listing of sizes.
     */
    public function index(): JsonResponse
    {
        $sizes = Size::orderBy('sort_order')->orderBy('name')->get();
        return response()->json([
            'success' => true,
            'data' => $sizes
        ]);
    }

    /**
     * Store a newly created size.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'abbreviation' => 'required|string|max:10|unique:sizes',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $size = Size::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Size created successfully',
            'data' => $size
        ], 201);
    }

    /**
     * Display the specified size.
     */
    public function show(Size $size): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $size
        ]);
    }

    /**
     * Update the specified size.
     */
    public function update(Request $request, Size $size): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'abbreviation' => 'sometimes|required|string|max:10|unique:sizes,abbreviation,' . $size->id,
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $size->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Size updated successfully',
            'data' => $size->fresh()
        ]);
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
