<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_endpoint_returns_ok(): void
    {
        $this->getJson('/api/health')
            ->assertOk()
            ->assertExactJson([
                'service' => 'productos-service',
                'status' => 'ok',
            ]);
    }

    public function test_it_lists_products_with_category_and_brand(): void
    {
        $firstProduct = $this->createProduct(['nombre' => 'Teclado']);
        $secondProduct = $this->createProduct(['nombre' => 'Mouse', 'sku' => 'MOU-001']);

        $this->getJson('/api/productos')
            ->assertOk()
            ->assertJsonCount(2)
            ->assertJsonFragment(['id' => $firstProduct->id, 'nombre' => 'Teclado'])
            ->assertJsonFragment(['id' => $secondProduct->id, 'nombre' => 'Mouse'])
            ->assertJsonFragment(['id' => $firstProduct->categoria_id, 'nombre' => 'Procesadores'])
            ->assertJsonFragment(['id' => $firstProduct->marca_id, 'nombre' => 'AMD']);
    }

    public function test_it_creates_a_valid_product_with_category_and_brand(): void
    {
        $payload = $this->validPayload();

        $this->postJson('/api/productos', $payload)
            ->assertCreated()
            ->assertJsonFragment([
                'nombre' => 'Procesador Ryzen 7',
                'categoria_id' => $payload['categoria_id'],
                'marca_id' => $payload['marca_id'],
                'sku' => 'CPU-001',
                'activo' => true,
            ]);

        $this->assertDatabaseHas('products', [
            'nombre' => 'Procesador Ryzen 7',
            'categoria_id' => $payload['categoria_id'],
            'marca_id' => $payload['marca_id'],
            'sku' => 'CPU-001',
            'precio' => 1299.99,
            'stock' => 10,
        ]);
    }

    public function test_it_rejects_invalid_product_data(): void
    {
        $this->postJson('/api/productos', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'nombre',
                'categoria_id',
                'precio',
                'stock',
                'activo',
            ]);
    }

    public function test_it_rejects_a_missing_category(): void
    {
        $this->postJson('/api/productos', $this->validPayload(['categoria_id' => 999999]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('categoria_id');
    }

    public function test_brand_can_be_null(): void
    {
        $payload = $this->validPayload(['marca_id' => null]);

        $this->postJson('/api/productos', $payload)
            ->assertCreated()
            ->assertJsonPath('marca_id', null)
            ->assertJsonPath('marca', null);

        $this->assertDatabaseHas('products', [
            'sku' => 'CPU-001',
            'marca_id' => null,
        ]);
    }

    public function test_it_shows_an_existing_product(): void
    {
        $product = $this->createProduct();

        $this->getJson("/api/productos/{$product->id}")
            ->assertOk()
            ->assertJsonFragment([
                'id' => $product->id,
                'nombre' => $product->nombre,
                'sku' => $product->sku,
            ]);
    }

    public function test_it_returns_not_found_for_a_missing_product(): void
    {
        $this->getJson('/api/productos/999999')->assertNotFound();
    }

    public function test_it_updates_a_product(): void
    {
        $product = $this->createProduct();
        $payload = $this->validPayload([
            'nombre' => 'Procesador actualizado',
            'precio' => 1399.99,
            'stock' => 5,
            'sku' => 'CPU-002',
            'activo' => false,
        ]);

        $this->putJson("/api/productos/{$product->id}", $payload)
            ->assertOk()
            ->assertJsonFragment([
                'nombre' => 'Procesador actualizado',
                'sku' => 'CPU-002',
                'activo' => false,
            ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'nombre' => 'Procesador actualizado',
            'precio' => 1399.99,
            'stock' => 5,
            'sku' => 'CPU-002',
            'activo' => false,
        ]);
    }

    public function test_it_updates_a_product_while_keeping_its_sku(): void
    {
        $product = $this->createProduct();
        $payload = $this->validPayload([
            'nombre' => 'Procesador con mismo SKU',
            'sku' => $product->sku,
        ]);

        $this->putJson("/api/productos/{$product->id}", $payload)
            ->assertOk()
            ->assertJsonFragment([
                'id' => $product->id,
                'nombre' => 'Procesador con mismo SKU',
                'sku' => $product->sku,
            ]);
    }

    public function test_it_rejects_a_duplicate_sku(): void
    {
        $this->createProduct(['sku' => 'DUP-001']);

        $this->postJson('/api/productos', $this->validPayload(['sku' => 'DUP-001']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('sku');
    }

    public function test_it_deletes_a_product(): void
    {
        $product = $this->createProduct();

        $this->deleteJson("/api/productos/{$product->id}")->assertNoContent();

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    public function test_it_rejects_a_negative_price(): void
    {
        $this->postJson('/api/productos', $this->validPayload(['precio' => -0.01]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('precio');
    }

    public function test_it_rejects_negative_stock(): void
    {
        $this->postJson('/api/productos', $this->validPayload(['stock' => -1]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('stock');
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        $category = Category::firstOrCreate(['nombre' => 'Procesadores']);
        $brand = Brand::firstOrCreate(['nombre' => 'AMD']);

        return array_merge([
            'nombre' => 'Procesador Ryzen 7',
            'descripcion' => 'Procesador de ocho núcleos',
            'categoria_id' => $category->id,
            'marca_id' => $brand->id,
            'precio' => 1299.99,
            'stock' => 10,
            'sku' => 'CPU-001',
            'activo' => true,
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createProduct(array $overrides = []): Product
    {
        return Product::create($this->validPayload($overrides));
    }
}
