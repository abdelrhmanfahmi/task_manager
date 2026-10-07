<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * One test (or group) per security requirement of the task:
 * queries, password storage, validation, sessions, authorization, XSS.
 */
class SecurityTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['password' => 'password123']);
    }

    // --- Secure database queries -------------------------------------------

    public function test_sql_injection_in_task_input_is_stored_as_plain_text(): void
    {
        $payload = "x'); DROP TABLE tasks; --";

        $this->actingAs($this->user)->postJson('/tasks', [
            'title' => $payload,
            'priority' => 'low',
            'status' => 'pending',
        ])->assertCreated();

        $this->assertDatabaseHas('tasks', ['title' => $payload]);
        $this->assertSame(1, Task::count());
    }

    public function test_sql_injection_in_login_email_is_rejected(): void
    {
        $this->post('/login', ['email' => "' OR '1'='1", 'password' => "' OR '1'='1"])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_sql_injection_in_route_parameter_does_not_match_a_task(): void
    {
        Task::factory()->for($this->user)->create();

        $this->actingAs($this->user)
            ->deleteJson('/tasks/1 OR 1=1')
            ->assertNotFound();

        $this->assertSame(1, Task::count());
    }

    // --- Secure password storage -------------------------------------------

    public function test_password_is_stored_as_a_bcrypt_hash(): void
    {
        $stored = DB::table('users')->where('id', $this->user->id)->value('password');

        $this->assertNotSame('password123', $stored);
        $this->assertStringStartsWith('$2y$', $stored);
        $this->assertTrue(Hash::check('password123', $stored));
    }

    public function test_password_is_never_returned_in_json(): void
    {
        $this->assertArrayNotHasKey('password', $this->user->toArray());
    }

    // --- Backend validation -------------------------------------------------

    public function test_backend_rejects_invalid_task_even_without_javascript(): void
    {
        $this->actingAs($this->user)->postJson('/tasks', [
            'title' => '',
            'priority' => 'urgent',
            'status' => 'done',
            'due_date' => '2030-02-30',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['title', 'priority', 'status', 'due_date']);

        $this->assertSame(0, Task::count());
    }

    // --- Session handling ---------------------------------------------------

    public function test_session_id_is_regenerated_on_login(): void
    {
        $this->get('/login');
        $before = session()->getId();

        $this->post('/login', ['email' => $this->user->email, 'password' => 'password123']);

        $this->assertNotSame($before, session()->getId());
        $this->assertAuthenticatedAs($this->user);
    }

    public function test_logout_destroys_the_session(): void
    {
        $this->actingAs($this->user)->withSession(['secret' => 'value']);
        $tokenBefore = session()->token();

        $this->post('/logout')->assertRedirect('/login');

        $this->assertGuest();
        $this->assertFalse(session()->has('secret'));
        $this->assertNotSame($tokenBefore, session()->token());
    }

    public function test_logged_out_user_cannot_reach_protected_pages(): void
    {
        $this->actingAs($this->user)->post('/logout');

        $this->get('/dashboard')->assertRedirect('/login');
        $this->getJson('/tasks')->assertUnauthorized();
    }

    public function test_session_cookie_is_http_only_same_site_and_encrypted(): void
    {
        $this->assertTrue(config('session.http_only'));
        $this->assertSame('lax', config('session.same_site'));
        $this->assertTrue((bool) config('session.encrypt'));
    }

    // --- User authorization -------------------------------------------------

    public function test_user_cannot_read_update_or_delete_another_users_task(): void
    {
        $foreign = Task::factory()->create(['title' => 'Not yours']);

        $this->actingAs($this->user)->getJson('/tasks')
            ->assertJsonMissing(['title' => 'Not yours']);

        $this->actingAs($this->user)->putJson("/tasks/{$foreign->id}", [
            'title' => 'Hijacked',
            'priority' => 'low',
            'status' => 'pending',
        ])->assertNotFound();

        $this->actingAs($this->user)->deleteJson("/tasks/{$foreign->id}")->assertNotFound();

        $this->assertDatabaseHas('tasks', ['id' => $foreign->id, 'title' => 'Not yours']);
    }

    public function test_user_cannot_move_a_task_to_another_user(): void
    {
        $other = User::factory()->create();
        $task = Task::factory()->for($this->user)->create();

        $this->actingAs($this->user)->putJson("/tasks/{$task->id}", [
            'title' => 'Mine',
            'priority' => 'low',
            'status' => 'pending',
            'user_id' => $other->id,
        ])->assertOk();

        $this->assertSame($this->user->id, $task->fresh()->user_id);
    }

    // --- XSS protection -----------------------------------------------------

    public function test_user_name_is_html_escaped_on_the_dashboard(): void
    {
        $this->user->update(['name' => '<script>alert(1)</script>']);

        $this->actingAs($this->user)->get('/dashboard')
            ->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
    }

    public function test_task_html_is_returned_as_data_not_markup(): void
    {
        $this->actingAs($this->user)->postJson('/tasks', [
            'title' => '<img src=x onerror=alert(1)>',
            'priority' => 'low',
            'status' => 'pending',
        ])->assertCreated()
            ->assertHeader('Content-Type', 'application/json')
            ->assertJsonPath('task.title', '<img src=x onerror=alert(1)>');
    }

    public function test_content_security_policy_blocks_inline_scripts_in_production(): void
    {
        config(['app.debug' => false]);

        $csp = $this->get('/login')->headers->get('Content-Security-Policy');

        $this->assertStringContainsString("default-src 'self'", $csp);
        $this->assertStringNotContainsString('unsafe-inline', $csp);
    }
}
