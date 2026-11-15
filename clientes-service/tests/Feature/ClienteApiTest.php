<?php

namespace Tests\Feature;

use App\Models\Cliente;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClienteApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_returns_ok(): void
    {
        $this->getJson('/api/health')
            ->assertOk()
            ->assertExactJson([
                'service' => 'clientes-service',
                'status' => 'ok',
            ]);
    }

    public function test_can_list_clientes(): void
    {
        $first = Cliente::create($this->validPayload([
            'email' => 'primero@example.com',
        ]));
        $second = Cliente::create($this->validPayload([
            'email' => 'segundo@example.com',
        ]));

        $this->getJson('/api/clientes')
            ->assertOk()
            ->assertJsonCount(2)
            ->assertJsonFragment(['id' => $first->id])
            ->assertJsonFragment(['id' => $second->id]);
    }

    public function test_can_create_valid_cliente(): void
    {
        $payload = $this->validPayload();

        $response = $this->postJson('/api/clientes', $payload);

        $response
            ->assertCreated()
            ->assertJsonFragment($payload);

        $this->assertDatabaseHas('clientes', $payload);
    }

    public function test_create_with_invalid_data_returns_unprocessable_entity(): void
    {
        $this->postJson('/api/clientes', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'nombre',
                'apellido',
                'email',
                'activo',
            ]);
    }

    public function test_can_show_existing_cliente(): void
    {
        $cliente = Cliente::create($this->validPayload());

        $this->getJson("/api/clientes/{$cliente->id}")
            ->assertOk()
            ->assertJsonFragment([
                'id' => $cliente->id,
                'email' => $cliente->email,
            ]);
    }

    public function test_missing_cliente_returns_not_found(): void
    {
        $this->getJson('/api/clientes/999999')
            ->assertNotFound();
    }

    public function test_can_update_cliente(): void
    {
        $cliente = Cliente::create($this->validPayload());
        $payload = $this->validPayload([
            'nombre' => 'Elena',
            'apellido' => 'Mendoza',
            'email' => 'elena.mendoza@example.com',
            'telefono' => '76543210',
            'direccion' => 'Calle Nueva 456',
            'activo' => false,
        ]);

        $this->putJson("/api/clientes/{$cliente->id}", $payload)
            ->assertOk()
            ->assertJsonFragment($payload);

        $this->assertDatabaseHas('clientes', [
            'id' => $cliente->id,
            'email' => 'elena.mendoza@example.com',
            'activo' => false,
        ]);
    }

    public function test_can_update_cliente_keeping_same_email(): void
    {
        $cliente = Cliente::create($this->validPayload());
        $payload = $this->validPayload([
            'nombre' => 'Nombre actualizado',
            'email' => $cliente->email,
        ]);

        $this->putJson("/api/clientes/{$cliente->id}", $payload)
            ->assertOk()
            ->assertJsonFragment([
                'nombre' => 'Nombre actualizado',
                'email' => $cliente->email,
            ]);
    }

    public function test_duplicate_email_returns_unprocessable_entity(): void
    {
        Cliente::create($this->validPayload());

        $this->postJson('/api/clientes', $this->validPayload([
            'nombre' => 'Otra',
            'email' => 'ana.paredes@example.com',
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }

    public function test_can_delete_cliente(): void
    {
        $cliente = Cliente::create($this->validPayload());

        $this->deleteJson("/api/clientes/{$cliente->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('clientes', [
            'id' => $cliente->id,
        ]);
    }

    public function test_invalid_email_returns_unprocessable_entity(): void
    {
        $this->postJson('/api/clientes', $this->validPayload([
            'email' => 'correo-invalido',
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }

    public function test_invalid_activo_returns_unprocessable_entity(): void
    {
        $this->postJson('/api/clientes', $this->validPayload([
            'activo' => 'activo',
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('activo');
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'nombre' => 'Ana',
            'apellido' => 'Paredes',
            'email' => 'ana.paredes@example.com',
            'telefono' => '71234567',
            'direccion' => 'Avenida Principal 123',
            'activo' => true,
        ], $overrides);
    }
}
