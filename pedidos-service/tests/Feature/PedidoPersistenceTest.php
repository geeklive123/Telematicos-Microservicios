<?php

namespace Tests\Feature;

use App\Models\Pedido;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PedidoPersistenceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Artisan::call('migrate', ['--force' => true]);
    }

    public function test_order_tables_have_the_expected_schema(): void
    {
        $this->assertTrue(Schema::hasTable('pedidos'));
        $this->assertTrue(Schema::hasTable('detalle_pedidos'));

        $this->assertTrue(Schema::hasColumns('pedidos', [
            'id',
            'cliente_id',
            'subtotal',
            'total',
            'estado',
            'metodo_pago',
            'fecha',
            'created_at',
            'updated_at',
        ]));

        $this->assertTrue(Schema::hasColumns('detalle_pedidos', [
            'id',
            'pedido_id',
            'producto_id',
            'producto_nombre',
            'precio_unitario',
            'cantidad',
            'subtotal',
            'created_at',
            'updated_at',
        ]));
    }

    public function test_only_pedido_id_has_an_internal_foreign_key(): void
    {
        $pedidoForeignKeys = Schema::getForeignKeys('pedidos');
        $detalleForeignKeys = Schema::getForeignKeys('detalle_pedidos');

        $this->assertFalse($this->hasForeignKeyFor($pedidoForeignKeys, 'cliente_id'));
        $this->assertFalse($this->hasForeignKeyFor($detalleForeignKeys, 'producto_id'));
        $this->assertTrue($this->hasForeignKeyFor(
            $detalleForeignKeys,
            'pedido_id',
            'pedidos',
            'id',
        ));

        $pedidoForeignKey = collect($detalleForeignKeys)->first(
            fn (array $foreignKey): bool => in_array('pedido_id', $foreignKey['columns'], true)
        );

        $this->assertSame('restrict', strtolower($pedidoForeignKey['on_delete']));
    }

    public function test_models_can_be_created_with_their_relationships_and_casts(): void
    {
        $pedido = Pedido::create([
            'cliente_id' => 25,
            'subtotal' => '1234.56',
            'total' => '1234.56',
            'estado' => 'pendiente',
            'metodo_pago' => 'tarjeta',
            'fecha' => '2026-10-06 10:30:00',
        ]);

        $detalle = $pedido->detalles()->create([
            'producto_id' => 80,
            'producto_nombre' => 'Procesador de prueba',
            'precio_unitario' => '617.28',
            'cantidad' => '2',
            'subtotal' => '1234.56',
        ]);

        $pedido->refresh();
        $detalle->refresh();

        $this->assertInstanceOf(HasMany::class, $pedido->detalles());
        $this->assertInstanceOf(BelongsTo::class, $detalle->pedido());
        $this->assertCount(1, $pedido->detalles);
        $this->assertTrue($pedido->is($detalle->pedido));
        $this->assertSame('1234.56', $pedido->subtotal);
        $this->assertSame('1234.56', $pedido->total);
        $this->assertSame('617.28', $detalle->precio_unitario);
        $this->assertSame('1234.56', $detalle->subtotal);
        $this->assertSame(2, $detalle->cantidad);
        $this->assertInstanceOf(Carbon::class, $pedido->fecha);
        $this->assertSame('2026-10-06 10:30:00', $pedido->fecha->format('Y-m-d H:i:s'));
    }

    /**
     * @param  list<array{
     *     columns: list<string>,
     *     foreign_table: string,
     *     foreign_columns: list<string>
     * }>  $foreignKeys
     */
    private function hasForeignKeyFor(
        array $foreignKeys,
        string $column,
        ?string $foreignTable = null,
        ?string $foreignColumn = null,
    ): bool {
        return collect($foreignKeys)->contains(function (array $foreignKey) use (
            $column,
            $foreignTable,
            $foreignColumn,
        ): bool {
            if (! in_array($column, $foreignKey['columns'], true)) {
                return false;
            }

            if ($foreignTable !== null && $foreignKey['foreign_table'] !== $foreignTable) {
                return false;
            }

            return $foreignColumn === null
                || in_array($foreignColumn, $foreignKey['foreign_columns'], true);
        });
    }
}
