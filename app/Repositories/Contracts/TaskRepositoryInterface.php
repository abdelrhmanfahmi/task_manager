<?php

namespace App\Repositories\Contracts;

use App\Models\Task;
use Illuminate\Database\Eloquent\Collection;

interface TaskRepositoryInterface
{
    /**
     * All tasks that belong to the given user, newest first.
     *
     * @return Collection<int, Task>
     */
    public function allForUser(int $userId): Collection;

    /**
     * Task counters for the dashboard.
     *
     * @return array{total:int, pending:int, in_progress:int, completed:int}
     */
    public function statsForUser(int $userId): array;

    /**
     * @param  array<string, mixed>  $data
     */
    public function createForUser(int $userId, array $data): Task;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Task $task, array $data): Task;

    public function delete(Task $task): void;
}
