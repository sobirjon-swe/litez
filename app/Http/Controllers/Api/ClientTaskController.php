<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TaskResource;
use App\Models\Client;
use App\Services\TaskService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ClientTaskController extends Controller
{
    public function __construct(
        private readonly TaskService $taskService,
    ) {}

    public function index(Client $client): AnonymousResourceCollection
    {
        return TaskResource::collection(
            $this->taskService->getTasksForClient($client->id)
        );
    }
}
