<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class BrandController extends Controller
{
    /**
     * Display a listing of brands.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Brand::query();

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Search by name or code
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $brands = $query->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data' => $brands
        ]);
    }

    /**
     * Store a newly created brand.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'code' => 'required|string|max:50|unique:brands',
            'is_active' => 'nullable|boolean',
            'notes' => 'nullable|string',
        ]);

        $validated['created_by'] = auth()->id();
        $validated['updated_by'] = auth()->id();

        $brand = Brand::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Brand created successfully',
            'data' => $brand->load('createdBy', 'updatedBy')
        ], 201);
    }

    /**
     * Display the specified brand with relationships.
     */
    public function show(Brand $brand): JsonResponse
    {
        $brand->load(['createdBy', 'updatedBy']);

        return response()->json([
            'success' => true,
            'data' => $brand
        ]);
    }

    /**
     * Update the specified brand.
     */
    public function update(Request $request, Brand $brand): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'code' => 'sometimes|required|string|max:50|unique:brands,code,' . $brand->id,
            'is_active' => 'nullable|boolean',
            'notes' => 'nullable|string',
        ]);

        $validated['updated_by'] = auth()->id();

        $brand->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Brand updated successfully',
            'data' => $brand->fresh()->load('createdBy', 'updatedBy')
        ]);
    }

    /**
     * Remove the specified brand.
     */
    public function destroy(Brand $brand): JsonResponse
    {
        $brand->delete();

        return response()->json([
            'success' => true,
            'message' => 'Brand deleted successfully'
        ]);
    }

    /**
     * Get brand statistics.
     */
    public function statistics(Brand $brand): JsonResponse
    {
        $stats = [
            'total_cutting_results' => $brand->cuttingResults()->count(),
            'total_cutting_quantity' => $brand->cuttingResults()->sum('total_cutting'),
            'active_distributions' => $brand->cuttingDistributions()
                ->whereDoesntHave('depositCuttingResults')
                ->count(),
            'completed_orders' => $brand->depositCuttingResults()->where('status', 'done')->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
}
