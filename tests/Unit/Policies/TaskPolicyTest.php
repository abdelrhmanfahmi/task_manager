<?php

namespace Tests\Unit\Policies;

use App\Models\Task;
use App\Models\User;
use App\Policies\TaskPolicy;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class TaskPolicyTest extends TestCase
{
    private TaskPolicy $policy;

    private User $owner;

    private Task $task;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new TaskPolicy;
        $this->owner = (new User)->forceFill(['id' => 1]);
        $this->task = (new Task)->forceFill(['user_id' => 1]);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function abilities(): array
    {
        return [
            'update' => ['update'],
            'delete' => ['delete'],
        ];
    }

    #[DataProvider('abilities')]
    public function test_owner_is_allowed(string $ability): void
    {
        $this->assertTrue($this->policy->{$ability}($this->owner, $this->task)->allowed());
    }

    #[DataProvider('abilities')]
    public function test_other_user_is_denied_with_not_found(string $ability): void
    {
        $stranger = (new User)->forceFill(['id' => 2]);

        $response = $this->policy->{$ability}($stranger, $this->task);

        $this->assertTrue($response->denied());
        $this->assertSame(404, $response->status());
    }
}
