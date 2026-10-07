<?php

namespace Tests\Unit\Models;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TaskTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2030-06-15 12:00:00');
    }

    public function test_priority_and_status_are_cast_to_enums(): void
    {
        $task = new Task(['priority' => 'high', 'status' => 'in_progress']);

        $this->assertSame(TaskPriority::High, $task->priority);
        $this->assertSame(TaskStatus::InProgress, $task->status);
    }

    public function test_due_date_is_cast_to_a_date(): void
    {
        $task = new Task(['due_date' => '2030-06-20']);

        $this->assertSame('2030-06-20', $task->due_date->format('Y-m-d'));
        $this->assertSame('00:00:00', $task->due_date->format('H:i:s'));
    }

    public function test_user_id_is_not_mass_assignable(): void
    {
        $task = new Task(['title' => 'Test', 'user_id' => 99]);

        $this->assertNull($task->user_id);
    }

    /**
     * @return array<string, array{?string, TaskStatus, bool}>
     */
    public static function overdueCases(): array
    {
        return [
            'past due and pending' => ['2030-06-14', TaskStatus::Pending, true],
            'past due and in progress' => ['2030-06-01', TaskStatus::InProgress, true],
            'past due but completed' => ['2030-06-14', TaskStatus::Completed, false],
            'due today' => ['2030-06-15', TaskStatus::Pending, false],
            'due in the future' => ['2030-06-16', TaskStatus::Pending, false],
            'no due date' => [null, TaskStatus::Pending, false],
        ];
    }

    #[DataProvider('overdueCases')]
    public function test_is_overdue(?string $dueDate, TaskStatus $status, bool $expected): void
    {
        $task = new Task(['due_date' => $dueDate, 'status' => $status]);

        $this->assertSame($expected, $task->isOverdue());
    }

    public function test_task_belongs_to_a_user(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->for($user)->create();

        $this->assertTrue($task->user->is($user));
    }
}
