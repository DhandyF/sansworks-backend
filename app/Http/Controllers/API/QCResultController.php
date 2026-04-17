<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Concerns\HasPagination;
use App\Http\Requests\QCResultRequest;
use App\Http\Resources\QCResultResource;
use App\Models\QCResult;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class QCResultController extends Controller
{
    use HasPagination;

    public function index(Request $request): JsonResponse
    {
        $perPage = $this->getPerPage($request);

        $query = QCResult::with([
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

        if ($request->has('from_date') && $request->has('to_date')) {
            $query->whereBetween('qc_date', [$request->from_date, $request->to_date]);
        }

        if ($request->has('tailor_id')) {
            $query->where('tailor_id', $request->tailor_id);
        }

        if ($request->has('has_defects')) {
            if ($request->boolean('has_defects')) {
                $query->where('total_to_repair', '>', 0);
            } else {
                $query->where('total_to_repair', 0);
            }
        }

        $query->orderBy('qc_date', 'desc')->orderBy('created_at', 'desc');

        if ($perPage === 'all') {
            $items = $query->get()->map(fn($item) => $item->toArray())->values()->all();
            return response()->json([
                'success' => true,
                'data' => $items,
            ]);
        }

        $result = $query->paginate($perPage);
        return $this->paginatedResponse($result, QCResultResource::class);
    }

    public function store(QCResultRequest $request): JsonResponse
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
        $userId = auth()->id() ?? 1;
        $validated['created_by'] = $userId;
        $validated['updated_by'] = $userId;
        $validated['qc_by'] = $validated['qc_by'] ?? $userId;

        $qcResult = QCResult::create($validated);

        $resource = new QCResultResource($qcResult->load([
            'depositCuttingResult.cuttingDistribution',
            'tailor',
            'brand',
            'article',
            'size',
            'qcBy',
            'createdBy',
            'updatedBy'
        ]));

        return response()->json([
            'success' => true,
            'message' => 'QC result created successfully',
            'data' => $resource
        ]);
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
