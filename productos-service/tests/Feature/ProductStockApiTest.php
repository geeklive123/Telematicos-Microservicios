<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\StockOperation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductStockApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_deducts_stock_successfully(): void
    {
        $product = $this->createProduct(['stock' => 5]);

        $this->postJson("/api/productos/{$product->id}/descontar-stock", ['cantidad' => 2, 'idempotency_key' => 'test'])
            ->assertOk()
            ->assertJsonPath('id', $product->id)
            ->assertJsonPath('stock', 3);
    }

    public function test_stock_is_persisted_with_the_expected_value_after_deduction(): void
    {
        $product = $this->createProduct(['stock' => 10]);

        $this->postJson("/api/productos/{$product->id}/descontar-stock", ['cantidad' => 4, 'idempotency_key' => 'test'])
            ->assertOk();

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock' => 6,
        ]);
    }

    public function test_deducting_more_than_available_stock_returns_conflict(): void
    {
        $product = $this->createProduct(['stock' => 1]);

        $this->postJson("/api/productos/{$product->id}/descontar-stock", ['cantidad' => 2, 'idempotency_key' => 'test'])
            ->assertConflict()
            ->assertExactJson([
                'message' => 'Stock insuficiente.',
                'stock_disponible' => 1,
            ]);
    }

    public function test_stock_never_becomes_negative(): void
    {
        $product = $this->createProduct(['stock' => 0]);

        $this->postJson("/api/productos/{$product->id}/descontar-stock", ['cantidad' => 1, 'idempotency_key' => 'test'])
            ->assertConflict();

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock' => 0,
        ]);
        $this->assertDatabaseMissing('products', [
            'id' => $product->id,
            'stock' => -1,
        ]);
    }

    public function test_missing_product_returns_not_found(): void
    {
        $this->postJson('/api/productos/999999/descontar-stock', ['cantidad' => 1, 'idempotency_key' => 'test'])
            ->assertNotFound();
    }

    public function test_inactive_product_does_not_allow_stock_deduction(): void
    {
        $product = $this->createProduct(['stock' => 5, 'activo' => false]);

        $this->postJson("/api/productos/{$product->id}/descontar-stock", ['cantidad' => 2, 'idempotency_key' => 'test'])
            ->assertConflict()
            ->assertExactJson([
                'message' => 'El producto está inactivo.',
            ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock' => 5,
        ]);
    }

    public function test_zero_quantity_returns_unprocessable_entity(): void
    {
        $product = $this->createProduct();

        $this->postJson("/api/productos/{$product->id}/descontar-stock", ['cantidad' => 0, 'idempotency_key' => 'test'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('cantidad');
    }

    public function test_it_replenishes_stock_successfully(): void
    {
        $product = $this->createProduct(['stock' => 3]);

        $this->postJson("/api/productos/{$product->id}/reponer-stock", ['cantidad' => 4, 'idempotency_key' => 'test'])
            ->assertOk()
            ->assertJsonPath('id', $product->id)
            ->assertJsonPath('stock', 7);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock' => 7,
        ]);
    }

    public function test_discount_is_recorded_and_replayed_without_changing_stock(): void
    {
        $product = $this->createProduct(['stock' => 9]);
        $body = ['cantidad' => 1, 'idempotency_key' => 'discount-1', 'source_type' => 'pedido', 'source_reference' => 'intento-123'];
        $url = "/api/productos/{$product->id}/descontar-stock";

        $first = $this->postJson($url, $body)->assertOk()->assertJsonPath('stock_before', 9)
            ->assertJsonPath('stock_after', 8)->assertJsonPath('already_processed', false);
        $this->assertDatabaseHas('stock_operations', [
            'id' => $first->json('operation_id'), 'product_id' => $product->id,
            'type' => 'discount', 'quantity' => 1, 'stock_before' => 9, 'stock_after' => 8,
            'status' => 'completed', 'source_type' => 'pedido', 'source_reference' => 'intento-123',
        ]);
        $this->postJson($url, $body)->assertOk()->assertJsonPath('operation_id', $first->json('operation_id'))
            ->assertJsonPath('stock', 8)->assertJsonPath('already_processed', true);
        $this->assertSame(8, $product->fresh()->stock);
        $this->assertDatabaseCount('stock_operations', 1);
    }

    public function test_reused_key_with_different_operation_returns_conflict(): void
    {
        $product = $this->createProduct(['stock' => 9]);
        $other = $this->createProduct(['sku' => 'STOCK-002', 'stock' => 9]);
        $body = ['cantidad' => 1, 'idempotency_key' => 'shared', 'source_reference' => 'attempt'];
        $url = "/api/productos/{$product->id}/descontar-stock";
        $this->postJson($url, $body)->assertOk();

        foreach ([
            [$url, [...$body, 'cantidad' => 2]],
            ["/api/productos/{$other->id}/descontar-stock", $body],
            ["/api/productos/{$product->id}/reponer-stock", $body],
            [$url, [...$body, 'source_reference' => 'other']],
        ] as [$target, $payload]) {
            $this->postJson($target, $payload)->assertConflict()
                ->assertJsonPath('message', 'La clave de idempotencia ya fue utilizada para otra operación.');
        }
        $this->assertSame(8, $product->fresh()->stock);
        $this->assertSame(9, $other->fresh()->stock);
        $this->assertDatabaseCount('stock_operations', 1);
    }

    public function test_restore_is_idempotent(): void
    {
        $product = $this->createProduct(['stock' => 8]);
        $url = "/api/productos/{$product->id}/reponer-stock";
        $body = ['cantidad' => 1, 'idempotency_key' => 'restore-1'];
        $this->postJson($url, $body)->assertOk()->assertJsonPath('stock_before', 8)
            ->assertJsonPath('stock_after', 9)->assertJsonPath('already_processed', false);
        $this->postJson($url, $body)->assertOk()->assertJsonPath('already_processed', true);
        $this->assertSame(9, $product->fresh()->stock);
        $this->assertDatabaseHas('stock_operations', ['type' => 'restore', 'status' => 'completed', 'stock_before' => 8, 'stock_after' => 9]);
    }

    public function test_rejected_discount_is_not_recorded_and_can_be_retried(): void
    {
        $product = $this->createProduct(['stock' => 0]);
        $body = ['cantidad' => 1, 'idempotency_key' => 'retry'];
        $url = "/api/productos/{$product->id}/descontar-stock";
        $this->postJson($url, $body)->assertConflict();
        $this->assertDatabaseCount('stock_operations', 0);
        $this->postJson("/api/productos/{$product->id}/reponer-stock", ['cantidad' => 1, 'idempotency_key' => 'restock'])->assertOk();
        $this->postJson($url, $body)->assertOk()->assertJsonPath('stock_after', 0);
    }

    public function test_stock_update_is_rolled_back_if_operation_insert_fails(): void
    {
        $product = $this->createProduct(['stock' => 9]);
        StockOperation::creating(fn () => throw new \RuntimeException('Simulated insert failure'));
        try {
            $this->postJson("/api/productos/{$product->id}/descontar-stock", ['cantidad' => 1, 'idempotency_key' => 'failure'])->assertServerError();
        } finally {
            StockOperation::flushEventListeners();
        }
        $this->assertSame(9, $product->fresh()->stock);
        $this->assertDatabaseCount('stock_operations', 0);
    }

    public function test_idempotency_key_is_required_and_nonempty(): void
    {
        $product = $this->createProduct();
        foreach ([null, '', '   '] as $key) {
            $this->postJson("/api/productos/{$product->id}/descontar-stock", ['cantidad' => 1, 'idempotency_key' => $key])
                ->assertUnprocessable()->assertJsonValidationErrors('idempotency_key');
        }
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createProduct(array $overrides = []): Product
    {
        $category = Category::firstOrCreate(['nombre' => 'Procesadores']);

        return Product::create(array_merge([
            'nombre' => 'Procesador de prueba',
            'descripcion' => null,
            'categoria_id' => $category->id,
            'marca_id' => null,
            'precio' => 100,
            'stock' => 5,
            'sku' => 'STOCK-001',
            'activo' => true,
        ], $overrides));
    }
}
