<?php

namespace Database\Seeders;

use App\Modules\Delivery\Models\Order;
use Illuminate\Database\Seeder;

class DeliverySeeder extends Seeder
{
    public function run(): void
    {
        Order::factory()->count(4)->create();
        Order::factory()->count(3)->paid()->create();
        Order::factory()->count(3)->inDelivery()->create();
        Order::factory()->count(3)->delivered()->create();
        Order::factory()->count(2)->cancelled()->create();
    }
}
