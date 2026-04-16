<?php

namespace App\Http\Concerns;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

trait HasPagination
{
    protected function paginatedResponse(LengthAwarePaginator $paginator, string $resourceClass = null): JsonResponse
    {
        $items = $paginator->items();

        if ($resourceClass) {
            $items = collect($items)->map(fn($item) => (new $resourceClass($item))->resolve())->values()->all();
        }

        return response()->json([
            'success' => true,
            'data' => $items,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ]
        ]);
    }

    protected function getPerPage(Request $request): int|string
    {
        $perPage = $request->input('per_page', 15);

        if ($perPage === 'all') {
            return 'all';
        }

        return min(max((int) $perPage, 1), 100);
    }
}