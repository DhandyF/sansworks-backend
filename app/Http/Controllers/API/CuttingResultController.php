<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Concerns\HasPagination;
use App\Models\CuttingResult;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CuttingResultController extends Controller
{
    use HasPagination;

    public function index(Request $request): JsonResponse
    {
        $perPage = $this->getPerPage($request);

        $query = CuttingResult::with(['fabric', 'brand', 'article', 'size', 'createdBy', 'updatedBy']);

        if ($request->has('from_date') && $request->has('to_date')) {
            $query->whereBetween('cutting_date', [$request->from_date, $request->to_date]);
        }

        if ($request->has('fabric_id')) {
            $query->where('fabric_id', $request->fabric_id);
        }

        if ($request->has('brand_id')) {
            $query->where('brand_id', $request->brand_id);
        }

        if ($request->has('article_id')) {
            $query->where('article_id', $request->article_id);
        }

        if ($request->has('size_id')) {
            $query->where('size_id', $request->size_id);
        }

        if ($request->has('search')) {
            $query->where('batch_number', 'like', "%{$request->search}%");
        }

        $query->orderBy('cutting_date', 'desc')->orderBy('created_at', 'desc');

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
     * Store a newly created cutting result.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'fabric_id' => 'required|exists:fabrics,id',
            'brand_id' => 'required|exists:brands,id',
            'article_id' => 'required|exists:articles,id',
            'size_id' => 'required|exists:sizes,id',
            'total_cutting' => 'required|integer|min:1',
            'cutting_date' => 'required|date',
            'batch_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $validated['created_by'] = auth()->id();
        $validated['updated_by'] = auth()->id();

        $cuttingResult = CuttingResult::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Cutting result created successfully',
            'data' => $cuttingResult->load(['fabric', 'brand', 'article', 'size', 'createdBy', 'updatedBy'])
        ], 201);
    }

    /**
     * Display the specified cutting result.
     */
    public function show(CuttingResult $cuttingResult): JsonResponse
    {
        $cuttingResult->load(['fabric', 'brand', 'article', 'size', 'createdBy', 'updatedBy', 'cuttingDistributions']);

        return response()->json([
            'success' => true,
            'data' => $cuttingResult
        ]);
    }

    /**
     * Update the specified cutting result.
     */
    public function update(Request $request, CuttingResult $cuttingResult): JsonResponse
    {
        $validated = $request->validate([
            'fabric_id' => 'sometimes|required|exists:fabrics,id',
            'brand_id' => 'sometimes|required|exists:brands,id',
            'article_id' => 'sometimes|required|exists:articles,id',
            'size_id' => 'sometimes|required|exists:sizes,id',
            'total_cutting' => 'sometimes|required|integer|min:1',
            'cutting_date' => 'sometimes|required|date',
            'batch_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $validated['updated_by'] = auth()->id();

        $cuttingResult->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Cutting result updated successfully',
            'data' => $cuttingResult->fresh()->load(['fabric', 'brand', 'article', 'size', 'createdBy', 'updatedBy'])
        ]);
    }

    /**
     * Remove the specified cutting result.
     */
    public function destroy(CuttingResult $cuttingResult): JsonResponse
    {
        // Check if cutting result has distributions
        if ($cuttingResult->cuttingDistributions()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete cutting result with existing distributions'
            ], 400);
        }

        $cuttingResult->delete();

        return response()->json([
            'success' => true,
            'message' => 'Cutting result deleted successfully'
        ]);
    }

    /**
     * Get cutting statistics for a date range.
     */
    public function statistics(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from_date' => 'required|date',
            'to_date' => 'required|date|after_or_equal:from_date',
        ]);

        $stats = [
            'total_cutting' => CuttingResult::whereBetween('cutting_date', [$validated['from_date'], $validated['to_date']])
                ->sum('total_cutting'),
            'total_batches' => CuttingResult::whereBetween('cutting_date', [$validated['from_date'], $validated['to_date']])
                ->count(),
            'by_fabric' => CuttingResult::whereBetween('cutting_date', [$validated['from_date'], $validated['to_date']])
                ->selectRaw('fabric_id, SUM(total_cutting) as total')
                ->with('fabric')
                ->groupBy('fabric_id')
                ->get(),
            'by_article' => CuttingResult::whereBetween('cutting_date', [$validated['from_date'], $validated['to_date']])
                ->selectRaw('article_id, SUM(total_cutting) as total')
                ->with('article')
                ->groupBy('article_id')
                ->get(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
}
