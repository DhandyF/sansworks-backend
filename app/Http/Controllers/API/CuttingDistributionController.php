<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Concerns\HasPagination;
use App\Models\CuttingDistribution;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class CuttingDistributionController extends Controller
{
    use HasPagination;

    public function index(Request $request): JsonResponse
    {
        $perPage = $this->getPerPage($request);

        $query = CuttingDistribution::with([
            'cuttingResult.fabric',
            'tailor',
            'brand',
            'article',
            'size',
            'createdBy',
            'updatedBy',
            'depositCuttingResults'
        ]);

        if ($request->has('from_date') && $request->has('to_date')) {
            $query->whereBetween('taken_date', [$request->from_date, $request->to_date]);
        }

        if ($request->has('tailor_id')) {
            $query->where('tailor_id', $request->tailor_id);
        }

        if ($request->has('status')) {
            if ($request->status === 'pending') {
                $query->whereDoesntHave('depositCuttingResults');
            } elseif ($request->status === 'completed') {
                $query->whereHas('depositCuttingResults');
            }
        }

        if ($request->has('brand_id')) {
            $query->where('brand_id', $request->brand_id);
        }

        if ($request->has('search')) {
            $query->where('distribution_number', 'like', "%{$request->search}%");
        }

        $query->orderBy('taken_date', 'desc')->orderBy('created_at', 'desc');

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
     * Store a newly created cutting distribution.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'cutting_result_id' => 'required|exists:cutting_results,id',
            'tailor_id' => 'required|exists:tailors,id',
            'brand_id' => 'required|exists:brands,id',
            'article_id' => 'required|exists:articles,id',
            'size_id' => 'required|exists:sizes,id',
            'total_cutting' => 'required|integer|min:1',
            'taken_date' => 'required|date',
            'deadline_date' => 'required|date|after:taken_date',
            'distribution_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $validated['created_by'] = auth()->id();
        $validated['updated_by'] = auth()->id();

        $cuttingDistribution = CuttingDistribution::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Cutting distribution created successfully',
            'data' => $cuttingDistribution->load([
                'cuttingResult.fabric',
                'tailor',
                'brand',
                'article',
                'size',
                'createdBy',
                'updatedBy'
            ])
        ], 201);
    }

    /**
     * Display the specified cutting distribution.
     */
    public function show(CuttingDistribution $cuttingDistribution): JsonResponse
    {
        $cuttingDistribution->load([
            'cuttingResult.fabric',
            'tailor',
            'brand',
            'article',
            'size',
            'createdBy',
            'updatedBy',
            'depositCuttingResults'
        ]);

        return response()->json([
            'success' => true,
            'data' => $cuttingDistribution
        ]);
    }

    /**
     * Update the specified cutting distribution.
     */
    public function update(Request $request, CuttingDistribution $cuttingDistribution): JsonResponse
    {
        // Check if distribution has deposits
        if ($cuttingDistribution->depositCuttingResults()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot update distribution with existing deposits'
            ], 400);
        }

        $validated = $request->validate([
            'cutting_result_id' => 'sometimes|required|exists:cutting_results,id',
            'tailor_id' => 'sometimes|required|exists:tailors,id',
            'brand_id' => 'sometimes|required|exists:brands,id',
            'article_id' => 'sometimes|required|exists:articles,id',
            'size_id' => 'sometimes|required|exists:sizes,id',
            'total_cutting' => 'sometimes|required|integer|min:1',
            'taken_date' => 'sometimes|required|date',
            'deadline_date' => 'sometimes|required|date|after:taken_date',
            'distribution_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $validated['updated_by'] = auth()->id();

        $cuttingDistribution->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Cutting distribution updated successfully',
            'data' => $cuttingDistribution->fresh()->load([
                'cuttingResult.fabric',
                'tailor',
                'brand',
                'article',
                'size',
                'createdBy',
                'updatedBy'
            ])
        ]);
    }

    /**
     * Remove the specified cutting distribution.
     */
    public function destroy(CuttingDistribution $cuttingDistribution): JsonResponse
    {
        // Check if distribution has deposits
        if ($cuttingDistribution->depositCuttingResults()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete distribution with existing deposits'
            ], 400);
        }

        $cuttingDistribution->delete();

        return response()->json([
            'success' => true,
            'message' => 'Cutting distribution deleted successfully'
        ]);
    }

    /**
     * Get overdue distributions.
     */
    public function overdue(): JsonResponse
    {
        $overdue = CuttingDistribution::with([
            'tailor',
            'brand',
            'article',
            'size'
        ])
            ->where('deadline_date', '<', now())
            ->whereDoesntHave('depositCuttingResults')
            ->orderBy('deadline_date')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $overdue
        ]);
    }

    /**
     * Get distribution statistics.
     */
    public function statistics(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
        ]);

        $query = CuttingDistribution::query();

        if ($request->has('from_date') && $request->has('to_date')) {
            $query->whereBetween('taken_date', [$validated['from_date'], $validated['to_date']]);
        }

        $totalDistributed = $query->sum('total_cutting');
        $pendingCount = (clone $query)->whereDoesntHave('depositCuttingResults')->count();
        $completedCount = (clone $query)->whereHas('depositCuttingResults')->count();

        $stats = [
            'total_distributed' => $totalDistributed,
            'pending_distributions' => $pendingCount,
            'completed_distributions' => $completedCount,
            'by_tailor' => CuttingDistribution::selectRaw('tailor_id, SUM(total_cutting) as total, COUNT(*) as count')
                ->with('tailor')
                ->whereBetween('taken_date', [$request->from_date ?? now()->startOfMonth(), $request->to_date ?? now()])
                ->groupBy('tailor_id')
                ->get(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
}
