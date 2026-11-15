<?php

namespace Tests\Feature;

use App\Models\Cliente;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ClienteTest extends TestCase
{
    use RefreshDatabase;

    public function test_clientes_table_exists(): void
    {
        $this->assertTrue(Schema::hasTable('clientes'));
    }

    public function test_clientes_table_has_expected_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('clientes', [
            'id',
            'nombre',
            'apellido',
            'email',
            'telefono',
            'direccion',
            'activo',
            'created_at',
            'updated_at',
        ]));
    }

    public function test_cliente_can_be_persisted(): void
    {
        $cliente = Cliente::create([
            'nombre' => 'Ana',
            'apellido' => 'Paredes',
            'email' => 'ana.paredes@example.com',
            'telefono' => null,
            'direccion' => null,
        ]);

        $this->assertDatabaseHas('clientes', [
            'id' => $cliente->id,
            'nombre' => 'Ana',
            'apellido' => 'Paredes',
            'email' => 'ana.paredes@example.com',
            'telefono' => null,
            'direccion' => null,
            'activo' => true,
        ]);
    }

    public function test_activo_is_cast_to_boolean(): void
    {
        $cliente = Cliente::create([
            'nombre' => 'Luis',
            'apellido' => 'Vargas',
            'email' => 'luis.vargas@example.com',
            'activo' => 0,
        ])->fresh();

        $this->assertIsBool($cliente->activo);
        $this->assertFalse($cliente->activo);
    }

    public function test_duplicate_email_violates_unique_constraint(): void
    {
        Cliente::create([
            'nombre' => 'Marta',
            'apellido' => 'Rojas',
            'email' => 'duplicado@example.com',
        ]);

        $this->expectException(QueryException::class);

        Cliente::create([
            'nombre' => 'Carlos',
            'apellido' => 'Flores',
            'email' => 'duplicado@example.com',
        ]);
    }
}
