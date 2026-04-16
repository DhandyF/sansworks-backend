<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Concerns\HasPagination;
use App\Models\DepositRepairResult;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DepositRepairResultController extends Controller
{
    use HasPagination;

    public function index(Request $request): JsonResponse
    {
        $perPage = $this->getPerPage($request);

        $query = DepositRepairResult::with([
            'repairDistribution.qcResult',
            'tailor',
            'brand',
            'article',
            'size',
            'createdBy',
            'updatedBy'
        ]);

        if ($request->has('from_date') && $request->has('to_date')) {
            $query->whereBetween('deposit_date', [$request->from_date, $request->to_date]);
        }

        if ($request->has('repair_quality_rating')) {
            $query->where('repair_quality_rating', $request->repair_quality_rating);
        }

        if ($request->has('tailor_id')) {
            $query->where('tailor_id', $request->tailor_id);
        }

        $query->orderBy('deposit_date', 'desc')->orderBy('created_at', 'desc');

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
     * Store a newly created deposit repair result.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'repair_distribution_id' => 'required|exists:repair_distributions,id',
            'tailor_id' => 'required|exists:tailors,id',
            'brand_id' => 'required|exists:brands,id',
            'article_id' => 'required|exists:articles,id',
            'size_id' => 'required|exists:sizes,id',
            'deposit_date' => 'required|date',
            'total_repaired' => 'required|integer|min:0',
            'repair_quality_rating' => 'required|in:excellent,good,fair,poor',
            'notes' => 'nullable|string',
        ]);

        // Check if deposit already exists for this distribution
        $existingDeposit = DepositRepairResult::where('repair_distribution_id', $validated['repair_distribution_id'])->first();
        if ($existingDeposit) {
            return response()->json([
                'success' => false,
                'message' => 'Deposit already exists for this repair distribution'
            ], 400);
        }

        $validated['created_by'] = auth()->id();
        $validated['updated_by'] = auth()->id();

        $deposit = DepositRepairResult::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Deposit repair result created successfully',
            'data' => $deposit->load([
                'repairDistribution.qcResult',
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
     * Display the specified deposit repair result.
     */
    public function show(DepositRepairResult $depositRepairResult): JsonResponse
    {
        $depositRepairResult->load([
            'repairDistribution.qcResult',
            'tailor',
            'brand',
            'article',
            'size',
            'createdBy',
            'updatedBy'
        ]);

        return response()->json([
            'success' => true,
            'data' => $depositRepairResult
        ]);
    }

    /**
     * Update the specified deposit repair result.
     */
    public function update(Request $request, DepositRepairResult $depositRepairResult): JsonResponse
    {
        $validated = $request->validate([
            'deposit_date' => 'sometimes|required|date',
            'total_repaired' => 'sometimes|required|integer|min:0',
            'repair_quality_rating' => 'sometimes|required|in:excellent,good,fair,poor',
            'notes' => 'nullable|string',
        ]);

        $validated['updated_by'] = auth()->id();

        $depositRepairResult->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Deposit repair result updated successfully',
            'data' => $depositRepairResult->fresh()->load([
                'repairDistribution.qcResult',
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
     * Remove the specified deposit repair result.
     */
    public function destroy(DepositRepairResult $depositRepairResult): JsonResponse
    {
        $depositRepairResult->delete();

        return response()->json([
            'success' => true,
            'message' => 'Deposit repair result deleted successfully'
        ]);
    }

    /**
     * Get deposit repair statistics.
     */
    public function statistics(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
        ]);

        $query = DepositRepairResult::query();

        if ($request->has('from_date') && $request->has('to_date')) {
            $query->whereBetween('deposit_date', [$validated['from_date'], $validated['to_date']]);
        }

        $stats = [
            'total_deposits' => $query->count(),
            'total_repaired' => $query->sum('total_repaired'),
            'quality_ratings' => [
                'excellent' => (clone $query)->where('repair_quality_rating', 'excellent')->count(),
                'good' => (clone $query)->where('repair_quality_rating', 'good')->count(),
                'fair' => (clone $query)->where('repair_quality_rating', 'fair')->count(),
                'poor' => (clone $query)->where('repair_quality_rating', 'poor')->count(),
            ],
            'by_tailor' => DepositRepairResult::selectRaw('tailor_id, SUM(total_repaired) as total, COUNT(*) as count')
                ->with('tailor')
                ->whereBetween('deposit_date', [$request->from_date ?? now()->startOfMonth(), $request->to_date ?? now()])
                ->groupBy('tailor_id')
                ->get(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
}
