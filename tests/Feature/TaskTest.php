<?php

namespace Tests\Feature;

use App\Enums\TaskStatus;
use App\Models\Client;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->token = $this->user->createToken('test')->plainTextToken;
    }

    public function test_can_create_task(): void
    {
        $client = Client::factory()->create();

        $response = $this->withToken($this->token)->postJson('/api/tasks', [
            'type' => 'call',
            'title' => 'Test Task',
            'priority' => 'high',
            'client_id' => $client->id,
            'deadline' => now()->addDays(3)->toIso8601String(),
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.title', 'Test Task')
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.priority', 'high');
    }

    public function test_can_list_tasks(): void
    {
        Task::factory(3)->create(['user_id' => $this->user->id]);

        $response = $this->withToken($this->token)->getJson('/api/tasks');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_can_filter_tasks_by_status(): void
    {
        Task::factory(2)->create(['user_id' => $this->user->id, 'status' => TaskStatus::Pending]);
        Task::factory(1)->create(['user_id' => $this->user->id, 'status' => TaskStatus::InProgress]);

        $response = $this->withToken($this->token)->getJson('/api/tasks?status=pending');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_can_update_task(): void
    {
        $task = Task::factory()->create(['user_id' => $this->user->id]);

        $response = $this->withToken($this->token)->putJson("/api/tasks/{$task->id}", [
            'title' => 'Updated Title',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.title', 'Updated Title');
    }

    public function test_can_soft_delete_task(): void
    {
        $task = Task::factory()->create(['user_id' => $this->user->id]);

        $response = $this->withToken($this->token)->deleteJson("/api/tasks/{$task->id}");

        $response->assertOk();
        $this->assertSoftDeleted('tasks', ['id' => $task->id]);
    }

    public function test_can_get_today_tasks(): void
    {
        Task::factory()->create([
            'user_id' => $this->user->id,
            'deadline' => now()->setTime(18, 0),
        ]);
        Task::factory()->create([
            'user_id' => $this->user->id,
            'deadline' => now()->addDays(5),
        ]);

        $response = $this->withToken($this->token)->getJson('/api/tasks/today');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_can_get_overdue_tasks(): void
    {
        Task::factory()->overdue()->create(['user_id' => $this->user->id]);
        Task::factory()->create(['user_id' => $this->user->id]);

        $response = $this->withToken($this->token)->getJson('/api/tasks/overdue');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_can_get_client_tasks(): void
    {
        $client = Client::factory()->create();
        Task::factory(3)->create(['user_id' => $this->user->id, 'client_id' => $client->id]);
        Task::factory(2)->create(['user_id' => $this->user->id]);

        $response = $this->withToken($this->token)->getJson("/api/clients/{$client->id}/tasks");

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_unauthenticated_request_returns_401(): void
    {
        $response = $this->getJson('/api/tasks');

        $response->assertStatus(401);
    }
}
