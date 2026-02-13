<?php

namespace App\Modules\Delivery\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Delivery\Models\Order;
use App\Modules\Delivery\Requests\CalculateRouteRequest;
use App\Modules\Delivery\Requests\StoreOrderRequest;
use App\Modules\Delivery\Requests\UpdateOrderStatusRequest;
use App\Modules\Delivery\Resources\OrderResource;
use App\Modules\Delivery\Services\OrderService;

class OrderController extends Controller
{
    public function __construct(
        private OrderService $orderService,
    ) {}

    public function calculate(CalculateRouteRequest $request)
    {
        $result = $this->orderService->calculateRoute($request->validated());

        return response()->json($result);
    }

    public function store(StoreOrderRequest $request)
    {
        $order = $this->orderService->create($request->validated());

        return (new OrderResource($order))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Order $order)
    {
        return new OrderResource($order);
    }

    public function updateStatus(UpdateOrderStatusRequest $request, Order $order)
    {
        $order = $this->orderService->updateStatus($order, $request->validated('status'));

        return new OrderResource($order);
    }

    public function pay(Order $order)
    {
        $result = $this->orderService->initiatePayment($order);

        return response()->json([
            'payment_url' => $result->payment_url,
            'transaction_id' => $result->transaction_id,
        ]);
    }
}
