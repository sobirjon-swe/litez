<?php

namespace Tests\Unit;

use App\Enums\TaskStatus;
use App\Models\Task;
use App\Services\TaskService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TaskServiceTest extends TestCase
{
    use RefreshDatabase;

    private TaskService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new TaskService();
    }

    public function test_validate_status_transition_throws_on_invalid(): void
    {
        $task = Task::factory()->create(['status' => TaskStatus::Done]);

        $this->expectException(ValidationException::class);

        $this->service->validateStatusTransition($task, TaskStatus::Pending);
    }

    public function test_validate_status_transition_passes_on_valid(): void
    {
        $task = Task::factory()->create(['status' => TaskStatus::Pending]);

        $this->service->validateStatusTransition($task, TaskStatus::InProgress);

        $this->assertTrue(true);
    }

    public function test_create_recurring_copy(): void
    {
        $task = Task::factory()->recurring('daily')->create([
            'deadline' => now()->addDay(),
        ]);

        $newTask = $this->service->createRecurringCopy($task);

        $this->assertEquals(TaskStatus::Pending, $newTask->status);
        $this->assertTrue($newTask->is_recurring);
        $this->assertEquals($task->title, $newTask->title);
        $this->assertEquals(
            $task->deadline->addDay()->format('Y-m-d'),
            $newTask->deadline->format('Y-m-d'),
        );
    }

    public function test_create_recurring_copy_weekly(): void
    {
        $task = Task::factory()->recurring('weekly')->create([
            'deadline' => now()->addDay(),
        ]);

        $newTask = $this->service->createRecurringCopy($task);

        $this->assertEquals(
            $task->deadline->addWeek()->format('Y-m-d'),
            $newTask->deadline->format('Y-m-d'),
        );
    }
}
