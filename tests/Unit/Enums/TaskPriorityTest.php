<?php

namespace Tests\Unit\Enums;

use App\Enums\TaskPriority;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class TaskPriorityTest extends TestCase
{
    public function test_values_lists_every_priority_in_order(): void
    {
        $this->assertSame(['low', 'medium', 'high'], TaskPriority::values());
    }

    /**
     * @return array<string, array{TaskPriority, string}>
     */
    public static function labels(): array
    {
        return [
            'low' => [TaskPriority::Low, 'Low'],
            'medium' => [TaskPriority::Medium, 'Medium'],
            'high' => [TaskPriority::High, 'High'],
        ];
    }

    #[DataProvider('labels')]
    public function test_label_is_capitalised_value(TaskPriority $priority, string $label): void
    {
        $this->assertSame($label, $priority->label());
    }

    public function test_unknown_value_is_rejected(): void
    {
        $this->assertNull(TaskPriority::tryFrom('urgent'));
    }
}
