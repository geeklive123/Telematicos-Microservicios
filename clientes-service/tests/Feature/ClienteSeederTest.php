<?php

namespace Tests\Feature;

use App\Models\Cliente;
use Database\Seeders\ClienteSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClienteSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_cliente_seeder_is_idempotent_and_sets_expected_statuses(): void
    {
        $this->seed(ClienteSeeder::class);
        $this->seed(ClienteSeeder::class);

        $this->assertDatabaseCount('clientes', 6);
        $this->assertSame(5, Cliente::query()->where('activo', true)->count());

        $lucia = Cliente::query()
            ->where('email', 'lucia.vargas@example.com')
            ->firstOrFail();

        $this->assertSame('LucÃ­a', $lucia->nombre);
        $this->assertFalse($lucia->activo);
    }
}
