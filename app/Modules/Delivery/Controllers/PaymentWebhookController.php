<?php

namespace App\Modules\Delivery\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Delivery\Resources\OrderResource;
use App\Modules\Delivery\Services\OrderService;
use Illuminate\Http\Request;

class PaymentWebhookController extends Controller
{
    public function __construct(
        private OrderService $orderService,
    ) {}

    public function handle(Request $request)
    {
        $payload = $request->all();
        $signature = $request->header('X-Signature', '');

        $order = $this->orderService->handleWebhook($payload, $signature);

        return new OrderResource($order);
    }
}
