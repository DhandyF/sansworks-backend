<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Concerns\HasPagination;
use App\Models\Tailor;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TailorController extends Controller
{
    use HasPagination;

    public function index(Request $request): JsonResponse
    {
        $perPage = $this->getPerPage($request);

        $query = Tailor::query();

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $query->orderBy('name');

        if ($perPage === 'all') {
            $items = $query->get();
            return response()->json([
                'success' => true,
                'data' => $items,
            ]);
        }

        $result = $query->paginate($perPage);
        return $this->paginatedResponse($result);
    }

    /**
     * Store a newly created tailor.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'code' => 'required|string|max:50|unique:tailors',
            'is_active' => 'nullable|boolean',
            'notes' => 'nullable|string',
        ]);

        $validated['created_by'] = auth()->id();
        $validated['updated_by'] = auth()->id();

        $tailor = Tailor::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Tailor created successfully',
            'data' => $tailor->load('createdBy', 'updatedBy')
        ], 201);
    }

    /**
     * Display the specified tailor with relationships.
     */
    public function show(Tailor $tailor): JsonResponse
    {
        $tailor->load(['createdBy', 'updatedBy', 'cuttingDistributions', 'depositCuttingResults']);

        return response()->json([
            'success' => true,
            'data' => $tailor
        ]);
    }

    /**
     * Update the specified tailor.
     */
    public function update(Request $request, Tailor $tailor): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'code' => 'sometimes|required|string|max:50|unique:tailors,code,' . $tailor->id,
            'is_active' => 'nullable|boolean',
            'notes' => 'nullable|string',
        ]);

        $validated['updated_by'] = auth()->id();

        $tailor->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Tailor updated successfully',
            'data' => $tailor->fresh()->load('createdBy', 'updatedBy')
        ]);
    }

    /**
     * Remove the specified tailor.
     */
    public function destroy(Tailor $tailor): JsonResponse
    {
        $tailor->delete();

        return response()->json([
            'success' => true,
            'message' => 'Tailor deleted successfully'
        ]);
    }

    /**
     * Get tailor statistics.
     */
    public function statistics(Tailor $tailor): JsonResponse
    {
        $stats = [
            'total_distributions' => $tailor->cuttingDistributions()->count(),
            'total_deposits' => $tailor->depositCuttingResults()->count(),
            'pending_items' => $tailor->cuttingDistributions()
                ->whereDoesntHave('depositCuttingResults')
                ->count(),
            'total_qc_passed' => $tailor->qcResults()
                ->where('total_to_repair', 0)
                ->sum('total_products'),
            'total_qc_failed' => $tailor->qcResults()
                ->where('total_to_repair', '>', 0)
                ->sum('total_to_repair'),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
}
