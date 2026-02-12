<?php

namespace App\Jobs;

use App\Enums\RemindVia;
use App\Models\Task;
use App\Notifications\TaskReminderNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class SendTaskReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly Task $task,
    ) {}

    public function handle(): void
    {
        $this->task->load('user');

        if ($this->task->remind_via === RemindVia::Email) {
            $this->task->user->notify(new TaskReminderNotification($this->task));
        } elseif ($this->task->remind_via === RemindVia::Sms) {
            $this->sendSmsMock();
        }

        $this->task->update(['reminder_sent_at' => now()]);
    }

    private function sendSmsMock(): void
    {
        $message = sprintf(
            "[%s] SMS to %s: Напоминание о задаче \"%s\" (дедлайн: %s)\n",
            now()->format('Y-m-d H:i:s'),
            $this->task->user->email,
            $this->task->title,
            $this->task->deadline->format('d.m.Y H:i'),
        );

        $logPath = storage_path('logs/sms.log');
        file_put_contents($logPath, $message, FILE_APPEND | LOCK_EX);
    }
}
