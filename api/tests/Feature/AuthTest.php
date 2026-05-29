<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create role for default (web) guard
        Role::create(['name' => 'operator', 'guard_name' => 'web']);
    }

    public function test_login_with_valid_credentials_returns_token(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'user' => [
                        'id',
                        'name',
                        'email',
                        'opd_id',
                        'sim_asn_user_id',
                        'is_sim_asn_authenticated',
                        'roles',
                        'permissions',
                    ],
                    'token',
                ],
            ])
            ->assertJsonPath('data.user.email', 'test@example.com')
            ->assertJsonPath('success', true);
    }

    public function test_login_with_invalid_credentials_returns_401(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'nobody@example.com',
            'password' => 'wrongpass',
        ]);

        $response->assertStatus(401)
            ->assertJsonPath('success', false);
    }

    public function test_me_endpoint_returns_user_with_sim_asn_context(): void
    {
        $user = User::factory()->create([
            'sim_asn_user_id' => 'sim-asn-uuid-123',
            'sim_asn_token' => ['access_token' => 'tok', 'refresh_token' => null, 'expires_at' => null],
        ]);

        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/auth/me');

        $response->assertOk()
            ->assertJsonPath('data.sim_asn_user_id', 'sim-asn-uuid-123')
            ->assertJsonPath('data.is_sim_asn_authenticated', true);
    }

    public function test_me_endpoint_returns_user_without_sim_asn_context(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/auth/me');

        $response->assertOk()
            ->assertJsonPath('data.sim_asn_user_id', null)
            ->assertJsonPath('data.is_sim_asn_authenticated', false);
    }

    public function test_register_from_sim_asn_creates_user_and_returns_token(): void
    {
        $response = $this->postJson('/api/v1/auth/register-from-sim-asn', [
            'sim_asn_user_id' => 'sim-asn-uuid-new',
            'name' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'opd_id' => null,
            'sim_asn_token' => [
                'access_token' => 'sim-tok-abc',
                'refresh_token' => 'sim-ref-xyz',
                'expires_at' => '2027-01-01T00:00:00Z',
            ],
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.name', 'Budi Santoso')
            ->assertJsonPath('data.user.sim_asn_user_id', 'sim-asn-uuid-new')
            ->assertJsonStructure(['data' => ['token']]);

        $this->assertDatabaseHas('users', [
            'email' => 'budi@example.com',
            'sim_asn_user_id' => 'sim-asn-uuid-new',
        ]);

        $user = User::where('email', 'budi@example.com')->first();
        $this->assertTrue($user->hasRole('operator'));
    }

    public function test_register_from_sim_asn_fails_with_duplicate_sim_asn_user_id(): void
    {
        User::factory()->create(['sim_asn_user_id' => 'existing-uuid']);

        $response = $this->postJson('/api/v1/auth/register-from-sim-asn', [
            'sim_asn_user_id' => 'existing-uuid',
            'name' => 'Duplicate User',
            'email' => 'dup@example.com',
            'sim_asn_token' => ['access_token' => 'tok'],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['sim_asn_user_id']);
    }

    public function test_register_from_sim_asn_fails_with_duplicate_email(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $response = $this->postJson('/api/v1/auth/register-from-sim-asn', [
            'sim_asn_user_id' => 'new-uuid',
            'name' => 'Another User',
            'email' => 'taken@example.com',
            'sim_asn_token' => ['access_token' => 'tok'],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_logout_revokes_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $logoutResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/auth/logout');

        $logoutResponse->assertOk();

        // Token should be deleted from DB
        $this->assertEquals(0, $user->fresh()->tokens()->count(), 'Token should be deleted from DB after logout');
    }

    public function test_protected_routes_require_authentication(): void
    {
        $response = $this->getJson('/api/v1/auth/me');
        $response->assertStatus(401);

        $logoutResponse = $this->postJson('/api/v1/auth/logout');
        $logoutResponse->assertStatus(401);
    }
}
