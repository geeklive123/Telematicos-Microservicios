<?php

namespace Tests\Feature;

use App\Models\Cliente;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClienteWebTest extends TestCase
{
    use RefreshDatabase;

    public function test_clientes_index_returns_ok(): void
    {
        $this->get('/clientes')
            ->assertOk()
            ->assertViewIs('clientes.index')
            ->assertSee('Administración de Clientes');
    }

    public function test_create_form_returns_ok(): void
    {
        $this->get('/clientes/crear')
            ->assertOk()
            ->assertViewIs('clientes.create')
            ->assertSee('Nuevo cliente');
    }

    public function test_can_create_cliente_from_web(): void
    {
        $payload = $this->validPayload();

        $this->post('/clientes', $payload)
            ->assertRedirectToRoute('clientes.index')
            ->assertSessionHas('success', 'Cliente creado correctamente.');

        $this->assertDatabaseHas('clientes', $payload);
    }

    public function test_edit_form_returns_ok(): void
    {
        $cliente = Cliente::create($this->validPayload());

        $this->get(route('clientes.edit', $cliente))
            ->assertOk()
            ->assertViewIs('clientes.edit')
            ->assertSee($cliente->email);
    }

    public function test_can_update_cliente_from_web(): void
    {
        $cliente = Cliente::create($this->validPayload());
        $payload = $this->validPayload([
            'nombre' => 'Nombre actualizado',
            'email' => $cliente->email,
            'activo' => false,
        ]);

        $this->put(route('clientes.update', $cliente), $payload)
            ->assertRedirectToRoute('clientes.index')
            ->assertSessionHas('success', 'Cliente actualizado correctamente.');

        $this->assertDatabaseHas('clientes', [
            'id' => $cliente->id,
            'nombre' => 'Nombre actualizado',
            'email' => $cliente->email,
            'activo' => false,
        ]);
    }

    public function test_can_delete_cliente_from_web(): void
    {
        $cliente = Cliente::create($this->validPayload());

        $this->delete(route('clientes.destroy', $cliente))
            ->assertRedirectToRoute('clientes.index')
            ->assertSessionHas('success', 'Cliente eliminado correctamente.');

        $this->assertDatabaseMissing('clientes', ['id' => $cliente->id]);
    }

    public function test_can_search_by_nombre(): void
    {
        Cliente::create($this->validPayload([
            'nombre' => 'Mariana',
            'email' => 'mariana@example.com',
        ]));
        Cliente::create($this->validPayload([
            'nombre' => 'Roberto',
            'email' => 'roberto@example.com',
        ]));

        $this->get('/clientes?buscar=Mariana')
            ->assertOk()
            ->assertSee('mariana@example.com')
            ->assertDontSee('roberto@example.com');
    }

    public function test_can_search_by_email(): void
    {
        Cliente::create($this->validPayload([
            'email' => 'especial@example.com',
        ]));
        Cliente::create($this->validPayload([
            'email' => 'otro@example.com',
        ]));

        $this->get('/clientes?buscar=especial%40example.com')
            ->assertOk()
            ->assertSee('especial@example.com')
            ->assertDontSee('otro@example.com');
    }

    public function test_can_filter_active_clientes(): void
    {
        Cliente::create($this->validPayload([
            'email' => 'verde@example.com',
            'activo' => true,
        ]));
        Cliente::create($this->validPayload([
            'email' => 'gris@example.com',
            'activo' => false,
        ]));

        $this->get('/clientes?estado=activos')
            ->assertOk()
            ->assertSee('verde@example.com')
            ->assertDontSee('gris@example.com');
    }

    public function test_can_filter_inactive_clientes(): void
    {
        Cliente::create($this->validPayload([
            'email' => 'verde@example.com',
            'activo' => true,
        ]));
        Cliente::create($this->validPayload([
            'email' => 'gris@example.com',
            'activo' => false,
        ]));

        $this->get('/clientes?estado=inactivos')
            ->assertOk()
            ->assertSee('gris@example.com')
            ->assertDontSee('verde@example.com');
    }

    public function test_pagination_preserves_filters(): void
    {
        foreach (range(1, 11) as $number) {
            Cliente::create($this->validPayload([
                'nombre' => "Cliente {$number}",
                'email' => "cliente{$number}@example.com",
                'activo' => true,
            ]));
        }

        $response = $this->get('/clientes?buscar=Cliente&estado=activos');

        $response
            ->assertOk()
            ->assertViewHas('clientes', fn ($clientes) => $clientes->count() === 10 && $clientes->hasMorePages());

        $nextPageQuery = [];
        parse_str((string) parse_url($response->viewData('clientes')->nextPageUrl(), PHP_URL_QUERY), $nextPageQuery);

        $this->assertSame('Cliente', $nextPageQuery['buscar']);
        $this->assertSame('activos', $nextPageQuery['estado']);
        $this->assertSame('2', $nextPageQuery['page']);
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
