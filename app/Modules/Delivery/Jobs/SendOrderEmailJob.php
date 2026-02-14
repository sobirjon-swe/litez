<?php

namespace App\Modules\Delivery\Jobs;

use App\Modules\Delivery\Models\Order;
use App\Modules\Delivery\Notifications\OrderDeliveredNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;

class SendOrderEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Order $order,
    ) {}

    public function handle(): void
    {
        Notification::route('mail', $this->order->customer_email)
            ->notify(new OrderDeliveredNotification($this->order));
    }
}
