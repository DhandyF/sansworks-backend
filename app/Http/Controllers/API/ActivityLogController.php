<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Concerns\HasPagination;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ActivityLogController extends Controller
{
    use HasPagination;

    public function index(Request $request): JsonResponse
    {
        $perPage = $this->getPerPage($request);

        $query = ActivityLog::with(['user']);

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->has('action')) {
            $query->where('action', $request->action);
        }

        if ($request->has('table_name')) {
            $query->where('table_name', $request->table_name);
        }

        if ($request->has('from_date') && $request->has('to_date')) {
            $query->whereBetween('created_at', [$request->from_date, $request->to_date]);
        }

        if ($request->has('record_id')) {
            $query->where('record_id', $request->record_id);
        }

        $query->orderBy('created_at', 'desc');

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
     * Display the specified activity log.
     */
    public function show(ActivityLog $activityLog): JsonResponse
    {
        $activityLog->load(['user']);

        return response()->json([
            'success' => true,
            'data' => $activityLog
        ]);
    }

    /**
     * Get activity summary by action type.
     */
    public function summary(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
        ]);

        $query = ActivityLog::query();

        if ($request->has('from_date') && $request->has('to_date')) {
            $query->whereBetween('created_at', [$validated['from_date'], $validated['to_date']]);
        }

        $summary = [
            'total_activities' => $query->count(),
            'by_action' => ActivityLog::selectRaw('action, COUNT(*) as count')
                ->when($request->has('from_date') && $request->has('to_date'), function ($q) use ($request) {
                    return $q->whereBetween('created_at', [$request->from_date, $request->to_date]);
                })
                ->groupBy('action')
                ->get()
                ->pluck('count', 'action'),
            'by_table' => ActivityLog::selectRaw('table_name, COUNT(*) as count')
                ->when($request->has('from_date') && $request->has('to_date'), function ($q) use ($request) {
                    return $q->whereBetween('created_at', [$request->from_date, $request->to_date]);
                })
                ->groupBy('table_name')
                ->get()
                ->pluck('count', 'table_name'),
            'by_user' => ActivityLog::selectRaw('user_id, COUNT(*) as count')
                ->with('user')
                ->when($request->has('from_date') && $request->has('to_date'), function ($q) use ($request) {
                    return $q->whereBetween('created_at', [$request->from_date, $request->to_date]);
                })
                ->groupBy('user_id')
                ->get()
                ->map(function ($item) {
                    return [
                        'user_name' => $item->user?->name ?? 'System',
                        'count' => $item->count,
                    ];
                }),
        ];

        return response()->json([
            'success' => true,
            'data' => $summary
        ]);
    }

    /**
     * Get recent activities for dashboard.
     */
    public function recent(): JsonResponse
    {
        $recentActivities = ActivityLog::with(['user'])
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $recentActivities
        ]);
    }
}
