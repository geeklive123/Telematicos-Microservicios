<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JwtAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private function tokenFor(string $role): string
    {
        $payload = app('tymon.jwt.payload.factory')->customClaims(['sub' => '42', 'role' => $role])->make();

        return app('tymon.jwt.manager')->encode($payload)->get();
    }

    public function test_health_is_public(): void
    {
        $this->withHeader('Authorization', '')->getJson('/api/health')->assertOk();
    }

    public function test_missing_token_returns_401(): void
    {
        $this->withHeader('Authorization', '')->getJson('/api/pedidos')->assertUnauthorized();
    }

    public function test_invalid_token_returns_401(): void
    {
        $this->withToken('invalid')->getJson('/api/pedidos')->assertUnauthorized();
    }

    public function test_token_with_modified_signature_returns_401(): void
    {
        $parts = explode('.', $this->tokenFor('admin'));
        $parts[2] = str_repeat('A', strlen($parts[2]));
        $this->withToken(implode('.', $parts))->getJson('/api/pedidos')->assertUnauthorized();
    }

    public function test_expired_token_returns_401(): void
    {
        $token = $this->tokenFor('admin');
        $this->travel(61)->minutes();
        $this->withToken($token)->getJson('/api/pedidos')->assertUnauthorized();
    }

    public function test_admin_can_list(): void
    {
        $this->withToken($this->tokenFor('admin'))->getJson('/api/pedidos')->assertOk();
    }

    public function test_vendedor_can_list(): void
    {
        $this->withToken($this->tokenFor('vendedor'))->getJson('/api/pedidos')->assertOk();
    }

    public function test_unknown_role_is_forbidden(): void
    {
        $this->withToken($this->tokenFor('otro'))->getJson('/api/pedidos')->assertForbidden();
    }

    public function test_vendedor_cannot_use_admin_operation(): void
    {
        $this->withToken($this->tokenFor('vendedor'))->deleteJson('/api/pedidos/99999')->assertForbidden();
    }
}
