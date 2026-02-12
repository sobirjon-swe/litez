<?php

use App\Enums\TaskStatus;
use App\Jobs\SendTaskReminderJob;
use App\Models\Task;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function () {
    $tasks = Task::query()
        ->whereNotNull('remind_before_minutes')
        ->whereNotNull('remind_via')
        ->whereNull('reminder_sent_at')
        ->whereNotIn('status', [TaskStatus::Done->value, TaskStatus::Cancelled->value])
        ->whereRaw('deadline - (remind_before_minutes || \' minutes\')::interval <= now()')
        ->get();

    foreach ($tasks as $task) {
        SendTaskReminderJob::dispatch($task);
    }
})->everyMinute()->name('send-task-reminders');

Schedule::call(function () {
    $overdueTasks = Task::query()
        ->where('deadline', '<', now())
        ->whereNotIn('status', [TaskStatus::Done->value, TaskStatus::Cancelled->value])
        ->get();

    foreach ($overdueTasks as $task) {
        Log::warning("Overdue task: [{$task->id}] {$task->title} (deadline: {$task->deadline})");
    }
})->everyMinute()->name('log-overdue-tasks');
