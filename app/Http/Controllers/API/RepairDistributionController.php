<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Concerns\HasPagination;
use App\Models\RepairDistribution;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class RepairDistributionController extends Controller
{
    use HasPagination;

    public function index(Request $request): JsonResponse
    {
        $perPage = $this->getPerPage($request);

        $query = RepairDistribution::with([
            'qcResult.depositCuttingResult',
            'tailor',
            'brand',
            'article',
            'size',
            'createdBy',
            'updatedBy',
            'depositRepairResults'
        ]);

        if ($request->has('from_date') && $request->has('to_date')) {
            $query->whereBetween('taken_date', [$request->from_date, $request->to_date]);
        }

        if ($request->has('tailor_id')) {
            $query->where('tailor_id', $request->tailor_id);
        }

        if ($request->has('repair_type')) {
            $query->where('repair_type', $request->repair_type);
        }

        if ($request->has('status')) {
            if ($request->status === 'pending') {
                $query->whereDoesntHave('depositRepairResults');
            } elseif ($request->status === 'completed') {
                $query->whereHas('depositRepairResults');
            }
        }

        if ($request->has('search')) {
            $query->where('repair_number', 'like', "%{$request->search}%");
        }

        $query->orderBy('taken_date', 'desc')->orderBy('created_at', 'desc');

        if ($perPage === 'all') {
            $items = $query->get()->map(fn($item) => $item->toArray())->values()->all();
            return response()->json([
                'success' => true,
                'data' => $items,
            ]);
        }

        $result = $query->paginate($perPage);
        return $this->paginatedResponse($result);
    }

    /**
     * Store a newly created repair distribution.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'qc_result_id' => 'required|exists:qc_results,id',
            'tailor_id' => 'required|exists:tailors,id',
            'brand_id' => 'required|exists:brands,id',
            'article_id' => 'required|exists:articles,id',
            'size_id' => 'required|exists:sizes,id',
            'total_to_repair' => 'required|integer|min:1',
            'taken_date' => 'required|date',
            'deadline_repair_date' => 'required|date|after:taken_date',
            'repair_number' => 'nullable|string|max:255',
            'repair_type' => 'required|in:minor,major,redo',
            'notes' => 'nullable|string',
        ]);

        $validated['created_by'] = auth()->id();
        $validated['updated_by'] = auth()->id();

        $repairDistribution = RepairDistribution::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Repair distribution created successfully',
            'data' => $repairDistribution->load([
                'qcResult.depositCuttingResult',
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
     * Display the specified repair distribution.
     */
    public function show(RepairDistribution $repairDistribution): JsonResponse
    {
        $repairDistribution->load([
            'qcResult.depositCuttingResult',
            'tailor',
            'brand',
            'article',
            'size',
            'createdBy',
            'updatedBy',
            'depositRepairResults'
        ]);

        return response()->json([
            'success' => true,
            'data' => $repairDistribution
        ]);
    }

    /**
     * Update the specified repair distribution.
     */
    public function update(Request $request, RepairDistribution $repairDistribution): JsonResponse
    {
        // Check if distribution has deposits
        if ($repairDistribution->depositRepairResults()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot update distribution with existing deposits'
            ], 400);
        }

        $validated = $request->validate([
            'qc_result_id' => 'sometimes|required|exists:qc_results,id',
            'tailor_id' => 'sometimes|required|exists:tailors,id',
            'brand_id' => 'sometimes|required|exists:brands,id',
            'article_id' => 'sometimes|required|exists:articles,id',
            'size_id' => 'sometimes|required|exists:sizes,id',
            'total_to_repair' => 'sometimes|required|integer|min:1',
            'taken_date' => 'sometimes|required|date',
            'deadline_repair_date' => 'sometimes|required|date|after:taken_date',
            'repair_number' => 'nullable|string|max:255',
            'repair_type' => 'sometimes|required|in:minor,major,redo',
            'notes' => 'nullable|string',
        ]);

        $validated['updated_by'] = auth()->id();

        $repairDistribution->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Repair distribution updated successfully',
            'data' => $repairDistribution->fresh()->load([
                'qcResult.depositCuttingResult',
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
     * Remove the specified repair distribution.
     */
    public function destroy(RepairDistribution $repairDistribution): JsonResponse
    {
        // Check if distribution has deposits
        if ($repairDistribution->depositRepairResults()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete distribution with existing deposits'
            ], 400);
        }

        $repairDistribution->delete();

        return response()->json([
            'success' => true,
            'message' => 'Repair distribution deleted successfully'
        ]);
    }

    /**
     * Get overdue repair distributions.
     */
    public function overdue(): JsonResponse
    {
        $overdue = RepairDistribution::with([
            'tailor',
            'brand',
            'article',
            'size'
        ])
            ->where('deadline_repair_date', '<', now())
            ->whereDoesntHave('depositRepairResults')
            ->orderBy('deadline_repair_date')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $overdue
        ]);
    }

    /**
     * Get repair statistics.
     */
    public function statistics(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
        ]);

        $query = RepairDistribution::query();

        if ($request->has('from_date') && $request->has('to_date')) {
            $query->whereBetween('taken_date', [$validated['from_date'], $validated['to_date']]);
        }

        $stats = [
            'total_repairs' => $query->count(),
            'total_items_to_repair' => $query->sum('total_to_repair'),
            'pending_repairs' => (clone $query)->whereDoesntHave('depositRepairResults')->count(),
            'by_type' => RepairDistribution::selectRaw('repair_type, COUNT(*) as count, SUM(total_to_repair) as total')
                ->whereBetween('taken_date', [$request->from_date ?? now()->startOfMonth(), $request->to_date ?? now()])
                ->groupBy('repair_type')
                ->get(),
            'by_tailor' => RepairDistribution::selectRaw('tailor_id, COUNT(*) as count, SUM(total_to_repair) as total')
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
