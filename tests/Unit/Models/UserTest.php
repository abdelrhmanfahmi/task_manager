<?php

namespace Tests\Unit\Models;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_is_hashed_when_set(): void
    {
        $user = new User(['password' => 'password123']);

        $this->assertNotSame('password123', $user->password);
        $this->assertTrue(Hash::check('password123', $user->password));
    }

    public function test_sensitive_attributes_are_hidden_from_serialization(): void
    {
        $user = User::factory()->create();

        $this->assertArrayNotHasKey('password', $user->toArray());
        $this->assertArrayNotHasKey('remember_token', $user->toArray());
    }

    public function test_user_has_many_tasks(): void
    {
        $user = User::factory()->has(Task::factory()->count(3))->create();
        Task::factory()->create(); // another user's task

        $this->assertCount(3, $user->tasks);
        $this->assertTrue($user->tasks->every(fn (Task $task) => $task->user_id === $user->id));
    }

    public function test_deleting_a_user_deletes_their_tasks(): void
    {
        $user = User::factory()->has(Task::factory()->count(2))->create();

        $user->delete();

        $this->assertDatabaseCount('tasks', 0);
    }
}
