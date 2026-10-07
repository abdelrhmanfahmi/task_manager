<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * A user may only touch their own tasks. Foreign tasks respond with 404
 * so their existence is not revealed.
 */
class TaskPolicy
{
    public function update(User $user, Task $task): Response
    {
        return $this->owns($user, $task);
    }

    public function delete(User $user, Task $task): Response
    {
        return $this->owns($user, $task);
    }

    private function owns(User $user, Task $task): Response
    {
        return $task->user_id === $user->id
            ? Response::allow()
            : Response::denyAsNotFound();
    }
}
