<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\TaskSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_seeder_creates_test_accounts_with_known_password(): void
    {
        $this->seed(UserSeeder::class);

        $this->assertDatabaseCount('users', 2);

        foreach ([UserSeeder::JOHN_EMAIL, UserSeeder::JANE_EMAIL] as $email) {
            $user = User::where('email', $email)->sole();
            $this->assertTrue(Hash::check(UserSeeder::PASSWORD, $user->password));
        }
    }

    public function test_task_seeder_requires_users_to_exist(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $this->seed(TaskSeeder::class);
    }

    public function test_database_seeder_creates_users_and_their_tasks(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(10, User::where('email', UserSeeder::JOHN_EMAIL)->sole()->tasks()->count());
        $this->assertSame(2, User::where('email', UserSeeder::JANE_EMAIL)->sole()->tasks()->count());
    }

    public function test_seeding_twice_does_not_create_duplicates(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(2, User::count());
        $this->assertSame(12, Task::count());
    }

    public function test_seeded_user_can_log_in(): void
    {
        $this->seed(UserSeeder::class);

        $this->post('/login', ['email' => UserSeeder::JOHN_EMAIL, 'password' => UserSeeder::PASSWORD])
            ->assertRedirect('/dashboard');

        $this->assertAuthenticated();
    }
}
