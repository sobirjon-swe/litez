<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Client;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'role' => UserRole::Admin,
        ]);

        $manager = User::factory()->create([
            'name' => 'Manager User',
            'email' => 'manager@example.com',
            'role' => UserRole::Manager,
        ]);

        $clients = Client::factory(5)->create();

        foreach ([$admin, $manager] as $user) {
            Task::factory(5)->create([
                'user_id' => $user->id,
                'client_id' => $clients->random()->id,
            ]);

            Task::factory(2)->recurring('weekly')->create([
                'user_id' => $user->id,
                'client_id' => $clients->random()->id,
            ]);

            Task::factory(2)->withReminder(30, 'email')->create([
                'user_id' => $user->id,
                'client_id' => $clients->random()->id,
            ]);

            Task::factory(1)->overdue()->create([
                'user_id' => $user->id,
                'client_id' => $clients->random()->id,
            ]);
        }

        $this->call(CatalogSeeder::class);
        $this->call(DeliverySeeder::class);
    }
}
