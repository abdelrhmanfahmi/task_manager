<?php

namespace Tests\Unit\Enums;

use App\Enums\TaskStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class TaskStatusTest extends TestCase
{
    public function test_values_lists_every_status_in_order(): void
    {
        $this->assertSame(['pending', 'in_progress', 'completed'], TaskStatus::values());
    }

    /**
     * @return array<string, array{TaskStatus, string}>
     */
    public static function labels(): array
    {
        return [
            'pending' => [TaskStatus::Pending, 'Pending'],
            'in progress' => [TaskStatus::InProgress, 'In Progress'],
            'completed' => [TaskStatus::Completed, 'Completed'],
        ];
    }

    #[DataProvider('labels')]
    public function test_label_is_human_readable(TaskStatus $status, string $label): void
    {
        $this->assertSame($label, $status->label());
    }

    public function test_unknown_value_is_rejected(): void
    {
        $this->assertNull(TaskStatus::tryFrom('archived'));
    }
}
