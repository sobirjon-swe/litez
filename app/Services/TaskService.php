<?php

namespace App\Services;

use App\Enums\TaskStatus;
use App\Models\Task;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class TaskService
{
    public function getFilteredTasks(array $filters): LengthAwarePaginator
    {
        $query = Task::with(['user', 'client']);

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['client_id'])) {
            $query->where('client_id', $filters['client_id']);
        }

        if (isset($filters['date_from'])) {
            $query->where('deadline', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('deadline', '<=', $filters['date_to']);
        }

        return $query->orderBy('deadline')->paginate(15);
    }

    public function getTodayTasks(): Collection
    {
        return Task::with(['user', 'client'])
            ->whereDate('deadline', Carbon::today())
            ->orderBy('deadline')
            ->get();
    }

    public function getOverdueTasks(): Collection
    {
        return Task::with(['user', 'client'])
            ->where('deadline', '<', Carbon::now())
            ->whereNotIn('status', [TaskStatus::Done->value, TaskStatus::Cancelled->value])
            ->orderBy('deadline')
            ->get();
    }

    public function createTask(array $data, int $userId): Task
    {
        $data['user_id'] = $userId;
        $data['status'] = $data['status'] ?? TaskStatus::Pending->value;

        $task = Task::create($data);
        $task->load(['user', 'client']);

        return $task;
    }

    public function updateTask(Task $task, array $data): Task
    {
        $task->update($data);
        $task->load(['user', 'client']);

        return $task;
    }

    /**
     * @throws ValidationException
     */
    public function updateStatus(Task $task, TaskStatus $newStatus): Task
    {
        $this->validateStatusTransition($task, $newStatus);

        $task->status = $newStatus;

        if ($newStatus === TaskStatus::Done) {
            $task->completed_at = Carbon::now();
        }

        $task->save();

        if ($newStatus === TaskStatus::Done && $task->is_recurring) {
            $this->createRecurringCopy($task);
        }

        $task->load(['user', 'client']);

        return $task;
    }

    /**
     * @throws ValidationException
     */
    public function validateStatusTransition(Task $task, TaskStatus $newStatus): void
    {
        if (!$task->status->canTransitionTo($newStatus)) {
            throw ValidationException::withMessages([
                'status' => ["Переход из {$task->status->value} в {$newStatus->value} невозможен"],
            ])->errorBag('default');
        }
    }

    public function createRecurringCopy(Task $task): Task
    {
        $newDeadline = match ($task->recurrence_type) {
            'daily' => $task->deadline->addDay(),
            'weekly' => $task->deadline->addWeek(),
            default => $task->deadline->addDay(),
        };

        return Task::create([
            'user_id' => $task->user_id,
            'client_id' => $task->client_id,
            'type' => $task->type,
            'title' => $task->title,
            'description' => $task->description,
            'priority' => $task->priority,
            'status' => TaskStatus::Pending,
            'deadline' => $newDeadline,
            'is_recurring' => true,
            'recurrence_type' => $task->recurrence_type,
            'remind_before_minutes' => $task->remind_before_minutes,
            'remind_via' => $task->remind_via,
        ]);
    }

    public function getTasksForClient(int $clientId): Collection
    {
        return Task::with(['user', 'client'])
            ->where('client_id', $clientId)
            ->orderBy('deadline')
            ->get();
    }
}
