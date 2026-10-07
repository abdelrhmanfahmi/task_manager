<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Creates the test accounts. Safe to run more than once: existing
 * accounts are matched by email and left untouched.
 */
class UserSeeder extends Seeder
{
    public const JOHN_EMAIL = 'john@example.com';

    public const JANE_EMAIL = 'jane@example.com';

    public const PASSWORD = 'password123';

    public function run(): void
    {
        foreach ($this->users() as $email => $name) {
            User::firstOrCreate(
                ['email' => $email],
                ['name' => $name, 'password' => self::PASSWORD],
            );
        }
    }

    /**
     * @return array<string, string> email => name
     */
    private function users(): array
    {
        return [
            self::JOHN_EMAIL => 'John Doe',
            self::JANE_EMAIL => 'Jane Smith',
        ];
    }
}
