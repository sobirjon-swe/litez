<?php

namespace App\Modules\Delivery\DTOs;

readonly class PaymentResult
{
    public function __construct(
        public string $payment_url,
        public string $transaction_id,
    ) {}
}
