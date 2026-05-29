<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuthDebugTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::create(['name' => 'operator', 'guard_name' => 'sanctum']);
    }

    public function test_debug_logout_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        fwrite(STDERR, "Token: {$token}\n");
        fwrite(STDERR, 'Token count before: '.$user->tokens()->count()."\n");

        // Try without Sanctum::actingAs
        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/auth/logout');

        fwrite(STDERR, 'Logout status: '.$response->getStatusCode()."\n");

        // Check token count after
        $user->refresh();
        fwrite(STDERR, 'Token count after: '.$user->tokens()->count()."\n");

        // Check me response
        $meResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/auth/me');
        fwrite(STDERR, 'Me status after logout: '.$meResponse->getStatusCode()."\n");
        fwrite(STDERR, 'Me body: '.$meResponse->getContent()."\n");

        $this->assertTrue(true);
    }

    public function test_debug_logout_with_bearer_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        fwrite(STDERR, 'Token count before: '.$user->tokens()->count()."\n");

        // Logout with bearer token
        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/auth/logout');

        fwrite(STDERR, 'Logout status: '.$response->getStatusCode()."\n");

        $user->refresh();
        fwrite(STDERR, 'Token count after: '.$user->tokens()->count()."\n");

        // With bearer token: token should be deleted, me should 401
        $meResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/auth/me');
        fwrite(STDERR, 'Me status after logout: '.$meResponse->getStatusCode()."\n");

        $this->assertTrue(true);
    }
}
