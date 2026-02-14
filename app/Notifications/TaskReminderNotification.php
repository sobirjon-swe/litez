<?php

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Task $task,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Напоминание: {$this->task->title}")
            ->greeting("Здравствуйте, {$notifiable->name}!")
            ->line("Напоминаем о задаче: {$this->task->title}")
            ->line("Тип: {$this->task->type->value}")
            ->line("Приоритет: {$this->task->priority->value}")
            ->line("Дедлайн: {$this->task->deadline->format('d.m.Y H:i')}")
            ->line($this->task->description ? "Описание: {$this->task->description}" : '')
            ->action('Открыть задачу', url("/api/tasks/{$this->task->id}"));
    }
}
