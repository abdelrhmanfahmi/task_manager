<?php

namespace Tests\Feature;

use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Write tests',
            'description' => 'Cover the task endpoints.',
            'priority' => 'high',
            'status' => 'pending',
            'due_date' => '2030-01-15',
        ], $overrides);
    }

    public function test_guest_cannot_access_task_endpoints(): void
    {
        $this->getJson('/tasks')->assertUnauthorized();
        $this->postJson('/tasks', $this->validPayload())->assertUnauthorized();
    }

    public function test_user_only_sees_their_own_tasks_and_stats(): void
    {
        Task::factory()->for($this->user)->count(2)->create(['status' => TaskStatus::Pending]);
        Task::factory()->for($this->user)->create(['status' => TaskStatus::Completed]);
        Task::factory()->count(3)->create(); // belongs to other users

        $this->actingAs($this->user)
            ->getJson('/tasks')
            ->assertOk()
            ->assertJsonCount(3, 'tasks')
            ->assertJsonPath('stats', ['total' => 3, 'pending' => 2, 'in_progress' => 0, 'completed' => 1]);
    }

    public function test_user_can_create_a_task(): void
    {
        $this->actingAs($this->user)
            ->postJson('/tasks', $this->validPayload())
            ->assertCreated()
            ->assertJsonPath('task.title', 'Write tests')
            ->assertJsonPath('task.status_label', 'Pending')
            ->assertJsonPath('stats.total', 1);

        $this->assertDatabaseHas('tasks', ['user_id' => $this->user->id, 'title' => 'Write tests']);
    }

    public function test_user_id_cannot_be_injected_through_input(): void
    {
        $other = User::factory()->create();

        $this->actingAs($this->user)
            ->postJson('/tasks', $this->validPayload(['user_id' => $other->id]))
            ->assertCreated();

        $this->assertDatabaseHas('tasks', ['user_id' => $this->user->id]);
        $this->assertDatabaseMissing('tasks', ['user_id' => $other->id]);
    }

    public function test_task_validation_rules(): void
    {
        $this->actingAs($this->user)
            ->postJson('/tasks', [
                'title' => '',
                'priority' => 'urgent',
                'status' => 'done',
                'due_date' => '2025-02-30',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['title', 'priority', 'status', 'due_date']);
    }

    public function test_user_can_update_their_task(): void
    {
        $task = Task::factory()->for($this->user)->create();

        $this->actingAs($this->user)
            ->putJson("/tasks/{$task->id}", $this->validPayload(['title' => 'Updated', 'status' => 'completed']))
            ->assertOk()
            ->assertJsonPath('task.title', 'Updated')
            ->assertJsonPath('task.status', 'completed');
    }

    public function test_user_can_delete_their_task(): void
    {
        $task = Task::factory()->for($this->user)->create();

        $this->actingAs($this->user)
            ->deleteJson("/tasks/{$task->id}")
            ->assertOk()
            ->assertJsonPath('stats.total', 0);

        $this->assertModelMissing($task);
    }

    public function test_user_cannot_update_or_delete_another_users_task(): void
    {
        $foreign = Task::factory()->create(['title' => 'Not yours']);

        $this->actingAs($this->user)
            ->putJson("/tasks/{$foreign->id}", $this->validPayload())
            ->assertNotFound();

        $this->actingAs($this->user)
            ->deleteJson("/tasks/{$foreign->id}")
            ->assertNotFound();

        $this->assertDatabaseHas('tasks', ['id' => $foreign->id, 'title' => 'Not yours']);
    }
}
