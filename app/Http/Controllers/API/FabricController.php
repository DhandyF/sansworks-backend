<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Fabric;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class FabricController extends Controller
{
    /**
     * Display a listing of fabrics.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Fabric::query();

        // Filter by unit
        if ($request->has('unit')) {
            $query->where('unit', $request->unit);
        }

        // Search by name or color
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('color', 'like', "%{$search}%");
            });
        }

        // Filter by low stock (less than 100 units)
        if ($request->has('low_stock') && $request->boolean('low_stock')) {
            $query->where('total_quantity', '<', 100);
        }

        $fabrics = $query->orderBy('name')->orderBy('color')->get();

        return response()->json([
            'success' => true,
            'data' => $fabrics
        ]);
    }

    /**
     * Store a newly created fabric.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'color' => 'nullable|string|max:255',
            'unit' => 'required|in:pcs,meter,yard,roll',
            'total_quantity' => 'required|numeric|min:0',
            'price_per_unit' => 'required|numeric|min:0',
        ]);

        $validated['created_by'] = auth()->id();
        $validated['updated_by'] = auth()->id();

        $fabric = Fabric::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Fabric created successfully',
            'data' => $fabric->load('createdBy', 'updatedBy')
        ], 201);
    }

    /**
     * Display the specified fabric.
     */
    public function show(Fabric $fabric): JsonResponse
    {
        $fabric->load(['createdBy', 'updatedBy', 'cuttingResults']);

        return response()->json([
            'success' => true,
            'data' => $fabric
        ]);
    }

    /**
     * Update the specified fabric.
     */
    public function update(Request $request, Fabric $fabric): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'color' => 'nullable|string|max:255',
            'unit' => 'sometimes|required|in:pcs,meter,yard,roll',
            'total_quantity' => 'sometimes|required|numeric|min:0',
            'price_per_unit' => 'sometimes|required|numeric|min:0',
        ]);

        $validated['updated_by'] = auth()->id();

        $fabric->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Fabric updated successfully',
            'data' => $fabric->fresh()->load('createdBy', 'updatedBy')
        ]);
    }

    /**
     * Remove the specified fabric.
     */
    public function destroy(Fabric $fabric): JsonResponse
    {
        $fabric->delete();

        return response()->json([
            'success' => true,
            'message' => 'Fabric deleted successfully'
        ]);
    }

    /**
     * Adjust fabric quantity.
     */
    public function adjustQuantity(Request $request, Fabric $fabric): JsonResponse
    {
        $validated = $request->validate([
            'quantity' => 'required|numeric',
            'reason' => 'required|string|max:255',
        ]);

        $fabric->total_quantity += $validated['quantity'];

        if ($fabric->total_quantity < 0) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient fabric quantity'
            ], 400);
        }

        $fabric->updated_by = auth()->id();
        $fabric->save();

        return response()->json([
            'success' => true,
            'message' => 'Fabric quantity adjusted successfully',
            'data' => $fabric->fresh()
        ]);
    }

    /**
     * Get fabric inventory summary.
     */
    public function inventorySummary(): JsonResponse
    {
        $summary = [
            'total_fabrics' => Fabric::count(),
            'total_value' => Fabric::selectRaw('SUM(total_quantity * price_per_unit) as value')->value('value') ?? 0,
            'low_stock_items' => Fabric::where('total_quantity', '<', 100)->count(),
            'by_unit' => Fabric::selectRaw('unit, SUM(total_quantity) as quantity, COUNT(*) as count')
                ->groupBy('unit')
                ->get(),
        ];

        return response()->json([
            'success' => true,
            'data' => $summary
        ]);
    }
}
