<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Requests\UpdateTaskStatusRequest;
use App\Http\Resources\TaskResource;
use App\Enums\TaskStatus;
use App\Models\Task;
use App\Services\TaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TaskController extends Controller
{
    public function __construct(
        private readonly TaskService $taskService,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $tasks = $this->taskService->getFilteredTasks($request->only([
            'type', 'priority', 'status', 'client_id', 'date_from', 'date_to',
        ]));

        return TaskResource::collection($tasks);
    }

    public function today(): AnonymousResourceCollection
    {
        return TaskResource::collection($this->taskService->getTodayTasks());
    }

    public function overdue(): AnonymousResourceCollection
    {
        return TaskResource::collection($this->taskService->getOverdueTasks());
    }

    public function store(StoreTaskRequest $request): JsonResponse
    {
        $task = $this->taskService->createTask(
            $request->validated(),
            $request->user()->id,
        );

        return response()->json([
            'data' => new TaskResource($task),
        ], 201);
    }

    public function update(UpdateTaskRequest $request, Task $task): JsonResponse
    {
        $task = $this->taskService->updateTask($task, $request->validated());

        return response()->json([
            'data' => new TaskResource($task),
        ]);
    }

    public function updateStatus(UpdateTaskStatusRequest $request, Task $task): JsonResponse
    {
        $newStatus = TaskStatus::from($request->validated('status'));
        $task = $this->taskService->updateStatus($task, $newStatus);

        return response()->json([
            'data' => new TaskResource($task),
        ]);
    }

    public function destroy(Task $task): JsonResponse
    {
        $task->delete();

        return response()->json([
            'message' => 'Задача удалена',
        ]);
    }
}
