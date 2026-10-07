<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\TaskRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Validator as ValidatorInstance;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TaskRequestTest extends TestCase
{
    /**
     * @param  array<string, mixed>  $overrides
     */
    private function validate(array $overrides = []): ValidatorInstance
    {
        $request = new TaskRequest;

        $data = array_merge([
            'title' => 'Write tests',
            'description' => 'Cover every module.',
            'priority' => 'medium',
            'status' => 'pending',
            'due_date' => '2030-01-15',
        ], $overrides);

        return Validator::make($data, $request->rules(), $request->messages());
    }

    public function test_valid_data_passes(): void
    {
        $this->assertTrue($this->validate()->passes());
    }

    public function test_optional_fields_may_be_null(): void
    {
        $this->assertTrue($this->validate(['description' => null, 'due_date' => null])->passes());
    }

    /**
     * @return array<string, array{string, mixed}>
     */
    public static function invalidFields(): array
    {
        return [
            'missing title' => ['title', null],
            'title too long' => ['title', str_repeat('a', 256)],
            'description too long' => ['description', str_repeat('a', 2001)],
            'missing priority' => ['priority', null],
            'unknown priority' => ['priority', 'urgent'],
            'missing status' => ['status', null],
            'unknown status' => ['status', 'archived'],
            'wrong date format' => ['due_date', '15/01/2030'],
            'not a date' => ['due_date', 'tomorrow'],
        ];
    }

    #[DataProvider('invalidFields')]
    public function test_invalid_field_fails(string $field, mixed $value): void
    {
        $validator = $this->validate([$field => $value]);

        $this->assertTrue($validator->fails());
        $this->assertSame([$field], $validator->errors()->keys());
    }

    public function test_custom_messages_are_used(): void
    {
        $errors = $this->validate(['priority' => 'x', 'status' => 'x', 'due_date' => 'x'])->errors();

        $this->assertSame('Please choose a valid priority.', $errors->first('priority'));
        $this->assertSame('Please choose a valid status.', $errors->first('status'));
        $this->assertSame('The due date must be a valid date (YYYY-MM-DD).', $errors->first('due_date'));
    }

    public function test_user_id_is_not_a_validated_field(): void
    {
        $this->assertArrayNotHasKey('user_id', (new TaskRequest)->rules());
    }
}
