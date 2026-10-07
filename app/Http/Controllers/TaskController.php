<?php

namespace App\Http\Controllers;

use App\Http\Requests\TaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use App\Repositories\Contracts\TaskRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * JSON endpoints consumed by the dashboard via fetch().
 * Ownership on update/delete is enforced by TaskPolicy (see routes/web.php).
 */
class TaskController extends Controller
{
    public function __construct(
        private readonly TaskRepositoryInterface $tasks,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        return response()->json([
            'tasks' => TaskResource::collection($this->tasks->allForUser($userId))->resolve($request),
            'stats' => $this->tasks->statsForUser($userId),
        ]);
    }

    public function store(TaskRequest $request): JsonResponse
    {
        $task = $this->tasks->createForUser($request->user()->id, $request->validated());

        return $this->taskResponse($request, $task, 'Task created successfully.', 201);
    }

    public function update(TaskRequest $request, Task $task): JsonResponse
    {
        $task = $this->tasks->update($task, $request->validated());

        return $this->taskResponse($request, $task, 'Task updated successfully.');
    }

    public function destroy(Request $request, Task $task): JsonResponse
    {
        $this->tasks->delete($task);

        return response()->json([
            'message' => 'Task deleted successfully.',
            'stats' => $this->tasks->statsForUser($request->user()->id),
        ]);
    }

    private function taskResponse(Request $request, Task $task, string $message, int $status = 200): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'task' => (new TaskResource($task))->resolve($request),
            'stats' => $this->tasks->statsForUser($request->user()->id),
        ], $status);
    }
}
