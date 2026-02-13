<?php

namespace App\Modules\Delivery\Jobs;

use App\Modules\Delivery\Contracts\SmsNotificationInterface;
use App\Modules\Delivery\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendOrderSmsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Order $order,
        public string $message,
    ) {}

    public function handle(SmsNotificationInterface $sms): void
    {
        $sms->send($this->order->customer_phone, $this->message);
    }
}
