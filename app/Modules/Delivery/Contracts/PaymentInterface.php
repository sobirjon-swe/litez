<?php

namespace App\Modules\Delivery\Contracts;

use App\Modules\Delivery\DTOs\PaymentResult;
use App\Modules\Delivery\Models\Order;

interface PaymentInterface
{
    public function createPayment(Order $order): PaymentResult;

    public function verifyWebhook(array $payload, string $signature): bool;
}
