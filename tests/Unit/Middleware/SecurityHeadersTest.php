<?php

namespace Tests\Unit\Middleware;

use App\Http\Middleware\SecurityHeaders;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    private function handle(): Response
    {
        return (new SecurityHeaders)->handle(new Request, fn () => new Response('ok'));
    }

    public function test_basic_security_headers_are_always_set(): void
    {
        $response = $this->handle();

        $this->assertSame('nosniff', $response->headers->get('X-Content-Type-Options'));
        $this->assertSame('DENY', $response->headers->get('X-Frame-Options'));
        $this->assertSame('same-origin', $response->headers->get('Referrer-Policy'));
        $this->assertSame('ok', $response->getContent());
    }

    public function test_content_security_policy_is_set_outside_debug_mode(): void
    {
        config(['app.debug' => false]);

        $csp = $this->handle()->headers->get('Content-Security-Policy');

        $this->assertStringContainsString("default-src 'self'", $csp);
        $this->assertStringContainsString("frame-ancestors 'none'", $csp);
    }

    public function test_content_security_policy_is_skipped_in_debug_mode(): void
    {
        config(['app.debug' => true]);

        $this->assertFalse($this->handle()->headers->has('Content-Security-Policy'));
    }
}
