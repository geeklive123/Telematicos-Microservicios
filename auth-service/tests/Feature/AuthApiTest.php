<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthApiTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['jwt.secret' => 'test-only-secret-with-at-least-32-characters']);
    }

    public function test_health_is_public(): void
    {
        $this->getJson('/api/health')->assertOk()->assertJsonPath('status', 'ok');
    }

    public function test_login_returns_token_role_and_no_password(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $response = $this->postJson('/api/auth/login', ['email' => $user->email, 'password' => 'password']);
        $response->assertOk()->assertJsonPath('user.role', 'admin')->assertJsonPath('expires_in', 3600)->assertJsonMissingPath('user.password');
        $this->assertSame('admin', JWTAuth::setToken($response->json('access_token'))->getPayload()->get('role'));
    }

    public function test_incorrect_password_returns_401(): void
    {
        $user = User::factory()->create();
        $this->postJson('/api/auth/login', ['email' => $user->email, 'password' => 'wrong'])->assertUnauthorized()->assertJsonPath('message', 'Credenciales incorrectas.');
    }

    public function test_unknown_user_returns_401(): void
    {
        $this->postJson('/api/auth/login', ['email' => 'missing@example.com', 'password' => 'wrong'])->assertUnauthorized();
    }

    public function test_inactive_user_returns_403(): void
    {
        $user = User::factory()->create(['activo' => false]);
        $this->postJson('/api/auth/login', ['email' => $user->email, 'password' => 'password'])->assertForbidden()->assertJsonPath('message', 'El usuario está inactivo.');
    }

    public function test_me_requires_a_token(): void
    {
        $this->getJson('/api/auth/me')->assertUnauthorized();
    }

    public function test_me_returns_identity_for_valid_token(): void
    {
        $user = User::factory()->create(['role' => 'vendedor']);
        $token = auth('api')->login($user);
        auth('api')->forgetUser();
        $this->withToken($token)->getJson('/api/auth/me')->assertOk()->assertJsonPath('id', $user->id)->assertJsonPath('role', 'vendedor')->assertJsonPath('activo', true)->assertJsonMissingPath('password');
    }

    public function test_invalid_token_returns_401(): void
    {
        $this->withToken('invalid')->getJson('/api/auth/me')->assertUnauthorized();
    }

    public function test_expired_token_returns_401(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);
        auth('api')->forgetUser();
        auth('api')->unsetToken();
        $this->travel(61)->minutes();

        $this->withToken($token)->getJson('/api/auth/me')->assertUnauthorized();
    }

    public function test_refresh_rejects_invalid_token(): void
    {
        $this->withToken('invalid')->postJson('/api/auth/refresh')->assertUnauthorized();
    }

    public function test_logout_invalidates_token(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);
        auth('api')->forgetUser();
        $this->withToken($token)->postJson('/api/auth/logout')->assertOk();
        auth('api')->forgetUser();
        $this->withToken($token)->getJson('/api/auth/me')->assertUnauthorized();
    }

    public function test_refresh_returns_new_token_and_invalidates_previous_token(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $token = auth('api')->login($user);
        auth('api')->forgetUser();
        $response = $this->withToken($token)->postJson('/api/auth/refresh');
        $response->assertOk()->assertJsonPath('user.role', 'admin');
        $this->assertNotSame($token, $response->json('access_token'));
        $this->assertSame('admin', JWTAuth::setToken($response->json('access_token'))->getPayload()->get('role'));
        auth('api')->forgetUser();
        auth('api')->unsetToken();
        $this->withToken($token)->getJson('/api/auth/me')->assertUnauthorized();
        auth('api')->forgetUser();
        auth('api')->unsetToken();
        $this->withToken($response->json('access_token'))->getJson('/api/auth/me')->assertOk();
    }

    public function test_login_requires_valid_fields(): void
    {
        $this->postJson('/api/auth/login', [])->assertUnprocessable()->assertJsonValidationErrors(['email', 'password']);
    }
}
