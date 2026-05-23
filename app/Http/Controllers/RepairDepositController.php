<?php

namespace App\Http\Controllers;

use App\Http\Requests\RepairDeposit\StoreRepairDepositRequest;
use App\Http\Requests\RepairDeposit\UpdateRepairDepositRequest;
use App\Http\Resources\RepairDepositResource;
use App\Services\RepairDepositService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class RepairDepositController extends Controller
{
    public function __construct(protected RepairDepositService $service) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        return RepairDepositResource::collection($this->service->paginate(
            $request->integer('per_page', 15),
            $request->query('search'),
            $request->query('tailor_filter')
        ));
    }

    public function store(StoreRepairDepositRequest $request)
    {
        $deposit = $this->service->create($request->validated());
        return response()->json(new RepairDepositResource($deposit->load(['repair.tailor', 'repair.brand', 'repair.article', 'tailor'])), 201);
    }

    public function show(string $id)
    {
        return response()->json(new RepairDepositResource($this->service->find($id)));
    }

    public function update(UpdateRepairDepositRequest $request, string $id)
    {
        $deposit = $this->service->update($id, $request->validated());
        return response()->json(new RepairDepositResource($deposit->load(['repair.tailor', 'repair.brand', 'repair.article', 'tailor'])));
    }

    public function destroy(string $id)
    {
        $this->service->delete($id);
        return response()->json(null, 204);
    }

    public function remaining(Request $request)
    {
        $validated = $request->validate([
            'repair_id' => 'required|exists:repairs,id',
        ]);

        return response()->json($this->service->getRemaining($validated['repair_id']));
    }
}