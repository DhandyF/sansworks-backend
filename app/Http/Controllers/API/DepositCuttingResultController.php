<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Concerns\HasPagination;
use App\Models\DepositCuttingResult;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DepositCuttingResultController extends Controller
{
    use HasPagination;

    public function index(Request $request): JsonResponse
    {
        $perPage = $this->getPerPage($request);

        $query = DepositCuttingResult::with([
            'cuttingDistribution.cuttingResult.fabric',
            'tailor',
            'brand',
            'article',
            'size',
            'createdBy',
            'updatedBy',
            'qcResults'
        ]);

        if ($request->has('from_date') && $request->has('to_date')) {
            $query->whereBetween('deposit_date', [$request->from_date, $request->to_date]);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('tailor_id')) {
            $query->where('tailor_id', $request->tailor_id);
        }

        if ($request->has('brand_id')) {
            $query->where('brand_id', $request->brand_id);
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
     * Store a newly created deposit cutting result.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'cutting_distribution_id' => 'required|exists:cutting_distributions,id',
            'tailor_id' => 'required|exists:tailors,id',
            'brand_id' => 'required|exists:brands,id',
            'article_id' => 'required|exists:articles,id',
            'size_id' => 'required|exists:sizes,id',
            'total_sewing_result' => 'required|integer|min:0',
            'deposit_date' => 'required|date',
            'status' => 'nullable|in:done,in_progress,overdue',
            'quality_notes' => 'nullable|string',
        ]);

        // Get the distribution and calculate total already deposited
        $distribution = \App\Models\CuttingDistribution::findOrFail($validated['cutting_distribution_id']);
        $totalDistributed = $distribution->total_cutting;
        
        $totalDeposited = DepositCuttingResult::where('cutting_distribution_id', $validated['cutting_distribution_id'])
            ->sum('total_sewing_result');
        
        $remaining = $totalDistributed - $totalDeposited;
        
        // Check if deposit would exceed available
        if ($validated['total_sewing_result'] > $remaining) {
            return response()->json([
                'success' => false,
                'message' => 'Total sewing result (' . $validated['total_sewing_result'] . ') exceeds available cutting (' . $remaining . ')'
            ], 400);
        }

        $validated['created_by'] = auth()->id();
        $validated['updated_by'] = auth()->id();

        $deposit = DepositCuttingResult::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Deposit cutting result created successfully',
            'data' => $deposit->load([
                'cuttingDistribution.cuttingResult.fabric',
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
     * Display the specified deposit cutting result.
     */
    public function show(DepositCuttingResult $depositCuttingResult): JsonResponse
    {
        $depositCuttingResult->load([
            'cuttingDistribution.cuttingResult.fabric',
            'tailor',
            'brand',
            'article',
            'size',
            'createdBy',
            'updatedBy',
            'qcResults'
        ]);

        return response()->json([
            'success' => true,
            'data' => $depositCuttingResult
        ]);
    }

    /**
     * Update the specified deposit cutting result.
     */
    public function update(Request $request, DepositCuttingResult $depositCuttingResult): JsonResponse
    {
        $validated = $request->validate([
            'total_sewing_result' => 'sometimes|required|integer|min:0',
            'deposit_date' => 'sometimes|required|date',
            'status' => 'nullable|in:done,in_progress,overdue',
            'quality_notes' => 'nullable|string',
        ]);

        $validated['updated_by'] = auth()->id();

        $depositCuttingResult->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Deposit cutting result updated successfully',
            'data' => $depositCuttingResult->fresh()->load([
                'cuttingDistribution.cuttingResult.fabric',
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
     * Remove the specified deposit cutting result.
     */
    public function destroy(DepositCuttingResult $depositCuttingResult): JsonResponse
    {
        // Check if deposit has QC results
        if ($depositCuttingResult->qcResults()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete deposit with existing QC results'
            ], 400);
        }

        $depositCuttingResult->delete();

        return response()->json([
            'success' => true,
            'message' => 'Deposit cutting result deleted successfully'
        ]);
    }

    /**
     * Get deposit statistics.
     */
    public function statistics(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
        ]);

        $query = DepositCuttingResult::query();

        if ($request->has('from_date') && $request->has('to_date')) {
            $query->whereBetween('deposit_date', [$validated['from_date'], $validated['to_date']]);
        }

        $stats = [
            'total_deposits' => $query->count(),
            'total_sewing_result' => $query->sum('total_sewing_result'),
            'total_sewing_price' => $query->sum('sewing_price'),
            'completed' => (clone $query)->where('status', 'done')->count(),
            'in_progress' => (clone $query)->where('status', 'in_progress')->count(),
            'overdue' => (clone $query)->where('status', 'overdue')->count(),
            'by_tailor' => DepositCuttingResult::selectRaw('tailor_id, SUM(total_sewing_result) as total, COUNT(*) as count')
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
