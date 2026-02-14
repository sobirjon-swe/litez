<?php

namespace Database\Factories;

use App\Modules\Delivery\Enums\OrderStatus;
use App\Modules\Delivery\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        $distanceKm = fake()->randomFloat(2, 1, 50);

        return [
            'customer_name' => fake()->name(),
            'customer_phone' => fake()->phoneNumber(),
            'customer_email' => fake()->safeEmail(),
            'origin_address' => fake()->address(),
            'origin_lat' => fake()->randomFloat(7, 41.28, 41.35),
            'origin_lng' => fake()->randomFloat(7, 69.20, 69.35),
            'destination_address' => fake()->address(),
            'destination_lat' => fake()->randomFloat(7, 41.28, 41.35),
            'destination_lng' => fake()->randomFloat(7, 69.20, 69.35),
            'distance_km' => $distanceKm,
            'duration_minutes' => (int) ceil($distanceKm / 40 * 60),
            'estimated_cost' => 5000 + ($distanceKm * 800),
            'status' => OrderStatus::Pending,
        ];
    }

    public function paid(): static
    {
        return $this->state(fn () => [
            'status' => OrderStatus::Paid,
            'paid_at' => now(),
        ]);
    }

    public function inDelivery(): static
    {
        return $this->state(fn () => [
            'status' => OrderStatus::InDelivery,
            'paid_at' => now()->subHour(),
        ]);
    }

    public function delivered(): static
    {
        return $this->state(fn () => [
            'status' => OrderStatus::Delivered,
            'paid_at' => now()->subHours(2),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn () => [
            'status' => OrderStatus::Cancelled,
        ]);
    }
}
