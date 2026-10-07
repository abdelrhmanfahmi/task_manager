<?php

namespace Tests\Unit\Resources;

use App\Http\Resources\TaskResource;
use App\Models\Task;
use Illuminate\Http\Request;
use Tests\TestCase;

class TaskResourceTest extends TestCase
{
    public function test_task_is_transformed_to_the_dashboard_shape(): void
    {
        $this->travelTo('2030-06-15 12:00:00');

        $task = (new Task)->forceFill([
            'id' => 7,
            'user_id' => 1,
            'title' => 'Ship release',
            'description' => 'Tag and deploy.',
            'priority' => 'high',
            'status' => 'in_progress',
            'due_date' => '2030-06-10',
            'updated_at' => '2030-06-14 09:30:00',
        ]);

        $data = (new TaskResource($task))->resolve(new Request);

        $this->assertSame([
            'id' => 7,
            'title' => 'Ship release',
            'description' => 'Tag and deploy.',
            'priority' => 'high',
            'priority_label' => 'High',
            'status' => 'in_progress',
            'status_label' => 'In Progress',
            'due_date' => '2030-06-10',
            'is_overdue' => true,
            'updated_at' => $task->updated_at->toIso8601String(),
        ], $data);
    }

    public function test_owner_id_is_not_exposed_and_nulls_are_kept(): void
    {
        $task = (new Task)->forceFill([
            'id' => 1,
            'user_id' => 5,
            'title' => 'No date',
            'description' => null,
            'priority' => 'low',
            'status' => 'pending',
            'due_date' => null,
        ]);

        $data = (new TaskResource($task))->resolve(new Request);

        $this->assertArrayNotHasKey('user_id', $data);
        $this->assertNull($data['description']);
        $this->assertNull($data['due_date']);
        $this->assertNull($data['updated_at']);
        $this->assertFalse($data['is_overdue']);
    }
}
