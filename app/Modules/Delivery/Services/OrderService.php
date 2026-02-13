<?php

namespace App\Modules\Delivery\Services;

use App\Modules\Delivery\Contracts\GeocoderInterface;
use App\Modules\Delivery\Contracts\PaymentInterface;
use App\Modules\Delivery\Contracts\RoutingInterface;
use App\Modules\Delivery\DTOs\PaymentResult;
use App\Modules\Delivery\Enums\OrderStatus;
use App\Modules\Delivery\Jobs\SendOrderEmailJob;
use App\Modules\Delivery\Jobs\SendOrderSmsJob;
use App\Modules\Delivery\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function __construct(
        private GeocoderInterface $geocoder,
        private RoutingInterface $routing,
        private PaymentInterface $payment,
        private PricingService $pricing,
    ) {}

    public function create(array $data): Order
    {
        return DB::transaction(function () use ($data) {
            $originPoint = $this->geocoder->geocode($data['origin_address']);
            $destinationPoint = $this->geocoder->geocode($data['destination_address']);

            $route = $this->routing->calculateRoute($originPoint, $destinationPoint);
            $cost = $this->pricing->calculate($route->distance_km);

            $order = Order::create([
                'customer_name' => $data['customer_name'],
                'customer_phone' => $data['customer_phone'],
                'customer_email' => $data['customer_email'],
                'origin_address' => $data['origin_address'],
                'origin_lat' => $originPoint->lat,
                'origin_lng' => $originPoint->lng,
                'destination_address' => $data['destination_address'],
                'destination_lat' => $destinationPoint->lat,
                'destination_lng' => $destinationPoint->lng,
                'distance_km' => $route->distance_km,
                'duration_minutes' => $route->duration_minutes,
                'estimated_cost' => $cost,
                'status' => OrderStatus::Pending,
            ]);

            SendOrderSmsJob::dispatch($order, "Buyurtma #{$order->id} qabul qilindi. Narx: {$cost} so'm.");

            return $order;
        });
    }

    public function calculateRoute(array $data): array
    {
        $originPoint = $this->geocoder->geocode($data['origin_address']);
        $destinationPoint = $this->geocoder->geocode($data['destination_address']);

        $route = $this->routing->calculateRoute($originPoint, $destinationPoint);
        $cost = $this->pricing->calculate($route->distance_km);

        return [
            'origin' => ['lat' => $originPoint->lat, 'lng' => $originPoint->lng],
            'destination' => ['lat' => $destinationPoint->lat, 'lng' => $destinationPoint->lng],
            'distance_km' => $route->distance_km,
            'duration_minutes' => $route->duration_minutes,
            'estimated_cost' => $cost,
        ];
    }

    public function updateStatus(Order $order, string $newStatus): Order
    {
        $newStatusEnum = OrderStatus::from($newStatus);

        if (! $order->status->canTransitionTo($newStatusEnum)) {
            throw ValidationException::withMessages([
                'status' => "Cannot transition from {$order->status->value} to {$newStatus}.",
            ]);
        }

        $order->update(['status' => $newStatus]);

        SendOrderSmsJob::dispatch($order, "Buyurtma #{$order->id} holati: {$newStatus}.");

        if ($newStatusEnum === OrderStatus::Delivered) {
            SendOrderEmailJob::dispatch($order);
        }

        return $order->fresh();
    }

    public function initiatePayment(Order $order): PaymentResult
    {
        if ($order->status !== OrderStatus::Pending) {
            throw ValidationException::withMessages([
                'status' => 'Payment can only be initiated for pending orders.',
            ]);
        }

        return $this->payment->createPayment($order);
    }

    public function handleWebhook(array $payload, string $signature): Order
    {
        if (! $this->payment->verifyWebhook($payload, $signature)) {
            abort(403, 'Invalid webhook signature.');
        }

        $order = Order::findOrFail($payload['order_id']);

        $order->update([
            'status' => OrderStatus::Paid,
            'paid_at' => now(),
        ]);

        SendOrderSmsJob::dispatch($order, "Buyurtma #{$order->id} to'landi.");

        return $order->fresh();
    }
}
