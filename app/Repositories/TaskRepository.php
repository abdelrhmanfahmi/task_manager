<?php

namespace App\Repositories;

use App\Enums\TaskStatus;
use App\Models\Task;
use App\Repositories\Contracts\TaskRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class TaskRepository implements TaskRepositoryInterface
{
    public function allForUser(int $userId): Collection
    {
        return Task::query()
            ->where('user_id', $userId)
            ->latest()
            ->latest('id')
            ->get();
    }

    public function statsForUser(int $userId): array
    {
        $counts = Task::query()
            ->where('user_id', $userId)
            ->toBase()
            ->selectRaw('status, COUNT(*) AS aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $stats = ['total' => (int) $counts->sum()];

        foreach (TaskStatus::cases() as $status) {
            $stats[$status->value] = (int) ($counts[$status->value] ?? 0);
        }

        return $stats;
    }

    public function createForUser(int $userId, array $data): Task
    {
        $task = new Task($data);
        $task->user_id = $userId;
        $task->save();

        return $task->refresh();
    }

    public function update(Task $task, array $data): Task
    {
        $task->update($data);

        return $task->refresh();
    }

    public function delete(Task $task): void
    {
        $task->delete();
    }
}
