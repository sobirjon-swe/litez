<?php

namespace App\Modules\Delivery\Notifications;

use App\Modules\Delivery\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderDeliveredNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Order $order,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Buyurtma #{$this->order->id} yetkazildi")
            ->line("Hurmatli {$this->order->customer_name},")
            ->line("Sizning #{$this->order->id} raqamli buyurtmangiz muvaffaqiyatli yetkazildi.")
            ->line("Narx: {$this->order->estimated_cost} so'm")
            ->line('Xizmatimizdan foydalanganingiz uchun rahmat!');
    }
}
