<?php

namespace Tests\Feature;

use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskStatusTransitionTest extends TestCase
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

    public function test_pending_to_in_progress(): void
    {
        $task = Task::factory()->create([
            'user_id' => $this->user->id,
            'status' => TaskStatus::Pending,
        ]);

        $response = $this->withToken($this->token)
            ->patchJson("/api/tasks/{$task->id}/status", ['status' => 'in_progress']);

        $response->assertOk()
            ->assertJsonPath('data.status', 'in_progress');
    }

    public function test_pending_to_cancelled(): void
    {
        $task = Task::factory()->create([
            'user_id' => $this->user->id,
            'status' => TaskStatus::Pending,
        ]);

        $response = $this->withToken($this->token)
            ->patchJson("/api/tasks/{$task->id}/status", ['status' => 'cancelled']);

        $response->assertOk()
            ->assertJsonPath('data.status', 'cancelled');
    }

    public function test_in_progress_to_done(): void
    {
        $task = Task::factory()->create([
            'user_id' => $this->user->id,
            'status' => TaskStatus::InProgress,
        ]);

        $response = $this->withToken($this->token)
            ->patchJson("/api/tasks/{$task->id}/status", ['status' => 'done']);

        $response->assertOk()
            ->assertJsonPath('data.status', 'done')
            ->assertJsonPath('data.completed_at', fn ($v) => $v !== null);
    }

    public function test_done_to_pending_is_forbidden(): void
    {
        $task = Task::factory()->create([
            'user_id' => $this->user->id,
            'status' => TaskStatus::Done,
            'completed_at' => now(),
        ]);

        $response = $this->withToken($this->token)
            ->patchJson("/api/tasks/{$task->id}/status", ['status' => 'pending']);

        $response->assertStatus(422)
            ->assertJsonPath('errors.status.0', 'Переход из done в pending невозможен');
    }

    public function test_cancelled_to_in_progress_is_forbidden(): void
    {
        $task = Task::factory()->create([
            'user_id' => $this->user->id,
            'status' => TaskStatus::Cancelled,
        ]);

        $response = $this->withToken($this->token)
            ->patchJson("/api/tasks/{$task->id}/status", ['status' => 'in_progress']);

        $response->assertStatus(422);
    }

    public function test_pending_to_done_is_forbidden(): void
    {
        $task = Task::factory()->create([
            'user_id' => $this->user->id,
            'status' => TaskStatus::Pending,
        ]);

        $response = $this->withToken($this->token)
            ->patchJson("/api/tasks/{$task->id}/status", ['status' => 'done']);

        $response->assertStatus(422);
    }

    public function test_recurring_task_creates_copy_on_done(): void
    {
        $task = Task::factory()->recurring('weekly')->create([
            'user_id' => $this->user->id,
            'status' => TaskStatus::InProgress,
            'deadline' => now()->addDay(),
        ]);

        $this->withToken($this->token)
            ->patchJson("/api/tasks/{$task->id}/status", ['status' => 'done']);

        $this->assertDatabaseCount('tasks', 2);

        $newTask = Task::where('id', '!=', $task->id)->first();
        $this->assertEquals(TaskStatus::Pending, $newTask->status);
        $this->assertTrue($newTask->is_recurring);
        $this->assertEquals($task->deadline->addWeek()->format('Y-m-d'), $newTask->deadline->format('Y-m-d'));
    }

    public function test_recurring_task_cancelled_does_not_create_copy(): void
    {
        $task = Task::factory()->recurring('daily')->create([
            'user_id' => $this->user->id,
            'status' => TaskStatus::InProgress,
        ]);

        $this->withToken($this->token)
            ->patchJson("/api/tasks/{$task->id}/status", ['status' => 'cancelled']);

        $this->assertDatabaseCount('tasks', 1);
    }
}
