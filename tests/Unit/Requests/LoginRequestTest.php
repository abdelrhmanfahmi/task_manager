<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\LoginRequest;
use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LoginRequestTest extends TestCase
{
    use RefreshDatabase;

    private function makeRequest(string $email, string $password): LoginRequest
    {
        return LoginRequest::create('/login', 'POST', ['email' => $email, 'password' => $password], server: ['REMOTE_ADDR' => '10.0.0.1']);
    }

    /**
     * @return array<string, array{array<string, mixed>}>
     */
    public static function invalidCredentials(): array
    {
        return [
            'missing email' => [['password' => 'secret']],
            'invalid email' => [['email' => 'not-an-email', 'password' => 'secret']],
            'missing password' => [['email' => 'john@example.com']],
        ];
    }

    #[DataProvider('invalidCredentials')]
    public function test_rules_reject_invalid_input(array $data): void
    {
        $this->assertTrue(Validator::make($data, (new LoginRequest)->rules())->fails());
    }

    public function test_authenticate_logs_in_with_correct_credentials(): void
    {
        $user = User::factory()->create(['password' => 'password123']);

        $this->makeRequest($user->email, 'password123')->authenticate();

        $this->assertTrue(Auth::user()->is($user));
    }

    public function test_authenticate_throws_on_wrong_password(): void
    {
        $user = User::factory()->create(['password' => 'password123']);

        $this->expectException(ValidationException::class);

        $this->makeRequest($user->email, 'wrong')->authenticate();
    }

    public function test_authenticate_locks_out_after_five_failed_attempts(): void
    {
        Event::fake([Lockout::class]);
        $user = User::factory()->create(['password' => 'password123']);

        for ($i = 0; $i < 5; $i++) {
            try {
                $this->makeRequest($user->email, 'wrong')->authenticate();
            } catch (ValidationException) {
                // expected failure
            }
        }

        try {
            // even the correct password is refused while locked out
            $this->makeRequest($user->email, 'password123')->authenticate();
            $this->fail('Expected the request to be rate limited.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('Too many login attempts', $e->errors()['email'][0]);
        }

        Event::assertDispatched(Lockout::class);
        $this->assertGuest();
    }
}
