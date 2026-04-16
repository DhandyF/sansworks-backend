<?php

namespace App\Http\Controllers\API;

use App\Http\Concerns\HasPagination;
use App\Http\Controllers\Controller;
use App\Http\Requests\FabricRequest;
use App\Http\Resources\FabricResource;
use App\Models\Fabric;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FabricController extends Controller
{
    use HasPagination;

    /**
     * Display a listing of fabrics.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $this->getPerPage($request);

        $query = Fabric::query();

        if ($request->has('unit')) {
            $query->where('unit', $request->unit);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('color', 'like', "%{$search}%");
            });
        }

        if ($request->has('low_stock') && $request->boolean('low_stock')) {
            $query->where('total_quantity', '<', 100);
        }

        $query->orderBy('name')->orderBy('color');

        if ($perPage === 'all') {
            $items = $query->get()->map(fn($item) => (new FabricResource($item))->resolve())->values()->all();

            return response()->json([
                'success' => true,
                'data' => $items,
            ]);
        }

        $fabrics = $query->paginate($perPage);

        return $this->paginatedResponse($fabrics, FabricResource::class);
    }

    /**
     * Store a newly created fabric.
     */
    public function store(FabricRequest $request): FabricResource
    {
        $validated = $request->validated();
        $validated['created_by'] = auth()->id();
        $validated['updated_by'] = auth()->id();

        $fabric = Fabric::create($validated);

        return new FabricResource($fabric);
    }

    /**
     * Display the specified fabric.
     */
    public function show(Fabric $fabric): FabricResource
    {
        return new FabricResource($fabric->load(['createdBy', 'updatedBy', 'cuttingResults']));
    }

    /**
     * Update the specified fabric.
     */
    public function update(FabricRequest $request, Fabric $fabric): FabricResource
    {
        $validated = $request->validated();
        $validated['updated_by'] = auth()->id();

        $fabric->update($validated);

        return new FabricResource($fabric->fresh());
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
            'data' => new FabricResource($fabric->fresh())
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