<?php

namespace App\Http\Controllers;

use App\Http\Requests\Shipment\StoreShipmentRequest;
use App\Http\Requests\Shipment\UpdateShipmentRequest;
use App\Http\Resources\ShipmentResource;
use App\Services\ShipmentService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ShipmentController extends Controller
{
    public function __construct(protected ShipmentService $service) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        return ShipmentResource::collection($this->service->paginate(
            $request->integer('per_page', 15),
            $request->query('search'),
            $request->query('brand_filter')
        ));
    }

    public function store(StoreShipmentRequest $request)
    {
        $shipment = $this->service->create($request->validated());
        return response()->json(new ShipmentResource($shipment->load(['preOrder.brand', 'preOrder.article', 'preOrder.size'])), 201);
    }

    public function show(string $id)
    {
        return response()->json(new ShipmentResource($this->service->find($id)));
    }

    public function update(UpdateShipmentRequest $request, string $id)
    {
        $shipment = $this->service->update($id, $request->validated());
        return response()->json(new ShipmentResource($shipment->load(['preOrder.brand', 'preOrder.article', 'preOrder.size'])));
    }

    public function destroy(string $id)
    {
        $this->service->delete($id);
        return response()->json(null, 204);
    }

    public function trashed(Request $request): AnonymousResourceCollection
    {
        return ShipmentResource::collection($this->service->getTrashed(
            $request->integer('per_page', 15),
            $request->query('search')
        ));
    }

    public function restore(string $id)
    {
        $shipment = $this->service->restore($id);
        return response()->json(new ShipmentResource($shipment->load(['preOrder.brand', 'preOrder.article', 'preOrder.size'])));
    }

    public function remaining(Request $request)
    {
        $validated = $request->validate([
            'pre_order_id' => 'required|exists:pre_orders,id',
        ]);

        $preOrder = \App\Models\PreOrder::findOrFail($validated['pre_order_id']);
        $totalShipped = $this->service->getTotalShipped($validated['pre_order_id']);

        return response()->json([
            'total_pcs' => $preOrder->total_pcs,
            'shipped' => $totalShipped,
            'remaining' => $preOrder->total_pcs - $totalShipped,
        ]);
    }
}