<?php

namespace Tests\Feature;

use App\Enums\TaskStatus;
use App\Jobs\SendTaskReminderJob;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ReminderTest extends TestCase
{
    use RefreshDatabase;

    public function test_reminder_job_is_dispatched_for_due_tasks(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $task = Task::factory()->create([
            'user_id' => $user->id,
            'deadline' => now()->addMinutes(15),
            'remind_before_minutes' => 30,
            'remind_via' => 'email',
            'status' => TaskStatus::Pending,
            'reminder_sent_at' => null,
        ]);

        $this->artisan('schedule:run');

        Queue::assertPushed(SendTaskReminderJob::class);
    }

    public function test_reminder_not_sent_if_already_sent(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        Task::factory()->create([
            'user_id' => $user->id,
            'deadline' => now()->addMinutes(15),
            'remind_before_minutes' => 30,
            'remind_via' => 'email',
            'status' => TaskStatus::Pending,
            'reminder_sent_at' => now()->subMinutes(5),
        ]);

        $this->artisan('schedule:run');

        Queue::assertNotPushed(SendTaskReminderJob::class);
    }
}
