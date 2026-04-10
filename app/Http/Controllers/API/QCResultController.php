<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\QCResult;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class QCResultController extends Controller
{
    /**
     * Display a listing of QC results.
     */
    public function index(Request $request): JsonResponse
    {
        $query = QCResult::with([
            'depositCuttingResult.cuttingDistribution',
            'tailor',
            'brand',
            'article',
            'size',
            'qcBy',
            'createdBy',
            'updatedBy'
        ]);

        // Filter by date range
        if ($request->has('from_date') && $request->has('to_date')) {
            $query->whereBetween('qc_date', [$request->from_date, $request->to_date]);
        }

        // Filter by tailor
        if ($request->has('tailor_id')) {
            $query->where('tailor_id', $request->tailor_id);
        }

        // Filter by quality (has defects or not)
        if ($request->has('has_defects')) {
            if ($request->boolean('has_defects')) {
                $query->where('total_to_repair', '>', 0);
            } else {
                $query->where('total_to_repair', 0);
            }
        }

        $qcResults = $query->orderBy('qc_date', 'desc')->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $qcResults
        ]);
    }

    /**
     * Store a newly created QC result.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'deposit_cutting_result_id' => 'required|exists:deposit_cutting_results,id',
            'tailor_id' => 'required|exists:tailors,id',
            'brand_id' => 'required|exists:brands,id',
            'article_id' => 'required|exists:articles,id',
            'size_id' => 'required|exists:sizes,id',
            'total_products' => 'required|integer|min:1',
            'total_to_repair' => 'required|integer|min:0',
            'qc_date' => 'required|date',
            'qc_by' => 'nullable|exists:users,id',
            'defect_details' => 'nullable|array',
            'notes' => 'nullable|string',
        ]);

        // Validate that total_to_repair doesn't exceed total_products
        if ($validated['total_to_repair'] > $validated['total_products']) {
            return response()->json([
                'success' => false,
                'message' => 'Total to repair cannot exceed total products'
            ], 400);
        }

        // Check if QC already exists for this deposit
        $existingQC = QCResult::where('deposit_cutting_result_id', $validated['deposit_cutting_result_id'])->first();
        if ($existingQC) {
            return response()->json([
                'success' => false,
                'message' => 'QC result already exists for this deposit'
            ], 400);
        }

        $validated['created_by'] = auth()->id();
        $validated['updated_by'] = auth()->id();
        $validated['qc_by'] = $validated['qc_by'] ?? auth()->id();

        $qcResult = QCResult::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'QC result created successfully',
            'data' => $qcResult->load([
                'depositCuttingResult.cuttingDistribution',
                'tailor',
                'brand',
                'article',
                'size',
                'qcBy',
                'createdBy',
                'updatedBy'
            ])
        ], 201);
    }

    /**
     * Display the specified QC result.
     */
    public function show(QCResult $qcResult): JsonResponse
    {
        $qcResult->load([
            'depositCuttingResult.cuttingDistribution',
            'tailor',
            'brand',
            'article',
            'size',
            'qcBy',
            'createdBy',
            'updatedBy',
            'repairDistributions'
        ]);

        return response()->json([
            'success' => true,
            'data' => $qcResult
        ]);
    }

    /**
     * Update the specified QC result.
     */
    public function update(Request $request, QCResult $qcResult): JsonResponse
    {
        $validated = $request->validate([
            'total_products' => 'sometimes|required|integer|min:1',
            'total_to_repair' => 'sometimes|required|integer|min:0',
            'qc_date' => 'sometimes|required|date',
            'qc_by' => 'nullable|exists:users,id',
            'defect_details' => 'nullable|array',
            'notes' => 'nullable|string',
        ]);

        // Validate that total_to_repair doesn't exceed total_products
        $totalProducts = $validated['total_products'] ?? $qcResult->total_products;
        $totalToRepair = $validated['total_to_repair'] ?? $qcResult->total_to_repair;

        if ($totalToRepair > $totalProducts) {
            return response()->json([
                'success' => false,
                'message' => 'Total to repair cannot exceed total products'
            ], 400);
        }

        $validated['updated_by'] = auth()->id();

        $qcResult->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'QC result updated successfully',
            'data' => $qcResult->fresh()->load([
                'depositCuttingResult.cuttingDistribution',
                'tailor',
                'brand',
                'article',
                'size',
                'qcBy',
                'createdBy',
                'updatedBy'
            ])
        ]);
    }

    /**
     * Remove the specified QC result.
     */
    public function destroy(QCResult $qcResult): JsonResponse
    {
        // Check if QC result has repair distributions
        if ($qcResult->repairDistributions()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete QC result with existing repair distributions'
            ], 400);
        }

        $qcResult->delete();

        return response()->json([
            'success' => true,
            'message' => 'QC result deleted successfully'
        ]);
    }

    /**
     * Get QC statistics.
     */
    public function statistics(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
        ]);

        $query = QCResult::query();

        if ($request->has('from_date') && $request->has('to_date')) {
            $query->whereBetween('qc_date', [$validated['from_date'], $validated['to_date']]);
        }

        $totalProducts = $query->sum('total_products');
        $totalToRepair = $query->sum('total_to_repair');

        $stats = [
            'total_qc_checks' => $query->count(),
            'total_products_checked' => $totalProducts,
            'total_passed' => $totalProducts - $totalToRepair,
            'total_failed' => $totalToRepair,
            'defect_rate' => $totalProducts > 0 ? round(($totalToRepair / $totalProducts) * 100, 2) : 0,
            'by_tailor' => QCResult::selectRaw('tailor_id, SUM(total_products) as products, SUM(total_to_repair) as defects, COUNT(*) as count')
                ->with('tailor')
                ->whereBetween('qc_date', [$request->from_date ?? now()->startOfMonth(), $request->to_date ?? now()])
                ->groupBy('tailor_id')
                ->get(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
}
