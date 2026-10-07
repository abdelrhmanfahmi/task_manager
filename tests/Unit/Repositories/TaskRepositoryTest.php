<?php

namespace Tests\Unit\Repositories;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;
use App\Repositories\Contracts\TaskRepositoryInterface;
use App\Repositories\TaskRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private TaskRepository $repository;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new TaskRepository;
        $this->user = User::factory()->create();
    }

    public function test_interface_is_bound_to_the_eloquent_repository(): void
    {
        $this->assertInstanceOf(TaskRepository::class, $this->app->make(TaskRepositoryInterface::class));
    }

    public function test_all_for_user_returns_only_their_tasks_newest_first(): void
    {
        $this->travelTo('2030-01-01');
        $oldest = Task::factory()->for($this->user)->create();
        $this->travelTo('2030-01-02');
        $newest = Task::factory()->for($this->user)->create();
        Task::factory()->create(); // another user's task

        $tasks = $this->repository->allForUser($this->user->id);

        $this->assertSame([$newest->id, $oldest->id], $tasks->modelKeys());
    }

    public function test_all_for_user_breaks_timestamp_ties_by_id(): void
    {
        $first = Task::factory()->for($this->user)->create();
        $second = Task::factory()->for($this->user)->create();

        $tasks = $this->repository->allForUser($this->user->id);

        $this->assertSame([$second->id, $first->id], $tasks->modelKeys());
    }

    public function test_stats_count_tasks_per_status(): void
    {
        Task::factory()->for($this->user)->count(2)->create(['status' => TaskStatus::Pending]);
        Task::factory()->for($this->user)->create(['status' => TaskStatus::InProgress]);
        Task::factory()->for($this->user)->count(3)->create(['status' => TaskStatus::Completed]);
        Task::factory()->count(4)->create(); // another user's tasks

        $this->assertSame(
            ['total' => 6, 'pending' => 2, 'in_progress' => 1, 'completed' => 3],
            $this->repository->statsForUser($this->user->id),
        );
    }

    public function test_stats_are_zero_when_user_has_no_tasks(): void
    {
        $this->assertSame(
            ['total' => 0, 'pending' => 0, 'in_progress' => 0, 'completed' => 0],
            $this->repository->statsForUser($this->user->id),
        );
    }

    public function test_create_for_user_sets_the_owner(): void
    {
        $task = $this->repository->createForUser($this->user->id, [
            'title' => 'New task',
            'priority' => 'high',
            'status' => 'pending',
        ]);

        $this->assertTrue($task->exists);
        $this->assertSame($this->user->id, $task->user_id);
        $this->assertSame(TaskPriority::High, $task->priority);
        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'user_id' => $this->user->id]);
    }

    public function test_create_for_user_ignores_a_user_id_in_the_data(): void
    {
        $other = User::factory()->create();

        $task = $this->repository->createForUser($this->user->id, [
            'title' => 'New task',
            'priority' => 'low',
            'status' => 'pending',
            'user_id' => $other->id,
        ]);

        $this->assertSame($this->user->id, $task->user_id);
    }

    public function test_update_saves_and_returns_fresh_task(): void
    {
        $task = Task::factory()->for($this->user)->create(['status' => TaskStatus::Pending]);

        $updated = $this->repository->update($task, ['title' => 'Renamed', 'status' => 'completed']);

        $this->assertSame('Renamed', $updated->title);
        $this->assertSame(TaskStatus::Completed, $updated->status);
        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'title' => 'Renamed', 'status' => 'completed']);
    }

    public function test_delete_removes_the_task(): void
    {
        $task = Task::factory()->for($this->user)->create();

        $this->repository->delete($task);

        $this->assertModelMissing($task);
    }
}
