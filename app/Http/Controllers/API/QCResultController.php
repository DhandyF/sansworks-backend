<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\QCResultRequest;
use App\Http\Resources\QCResultResource;
use App\Models\QCResult;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Request;

class QCResultController extends Controller
{
    /**
     * Display a listing of QC results.
     */
    public function index(Request $request): AnonymousResourceCollection
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

        return QCResultResource::collection($qcResults);
    }

    /**
     * Store a newly created QC result.
     */
    public function store(QCResultRequest $request): QCResultResource
    {
        // Check if QC already exists for this deposit
        $existingQC = QCResult::where('deposit_cutting_result_id', $request->deposit_cutting_result_id)->first();
        if ($existingQC) {
            return response()->json([
                'success' => false,
                'message' => 'QC result already exists for this deposit'
            ], 400);
        }

        $validated = $request->validated();
        $validated['created_by'] = auth()->id();
        $validated['updated_by'] = auth()->id();
        $validated['qc_by'] = $validated['qc_by'] ?? auth()->id();

        $qcResult = QCResult::create($validated);

        return new QCResultResource($qcResult->load([
            'depositCuttingResult.cuttingDistribution',
            'tailor',
            'brand',
            'article',
            'size',
            'qcBy',
            'createdBy',
            'updatedBy'
        ]));
    }

    /**
     * Display the specified QC result.
     */
    public function show(QCResult $qcResult): QCResultResource
    {
        return new QCResultResource($qcResult->load([
            'depositCuttingResult.cuttingDistribution',
            'tailor',
            'brand',
            'article',
            'size',
            'qcBy',
            'createdBy',
            'updatedBy',
            'repairDistributions'
        ]));
    }

    /**
     * Update the specified QC result.
     */
    public function update(QCResultRequest $request, QCResult $qcResult): QCResultResource
    {
        $validated = $request->validated();
        $validated['updated_by'] = auth()->id();

        $qcResult->update($validated);

        return new QCResultResource($qcResult->fresh()->load([
            'depositCuttingResult.cuttingDistribution',
            'tailor',
            'brand',
            'article',
            'size',
            'qcBy',
            'createdBy',
            'updatedBy'
        ]));
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
