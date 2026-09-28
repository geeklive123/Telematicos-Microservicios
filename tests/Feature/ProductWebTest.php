<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductWebTest extends TestCase
{
    use RefreshDatabase;

    public function test_products_index_responds_successfully_and_shows_catalog_names(): void
    {
        $product = $this->createProduct();

        $this->get(route('productos.index'))
            ->assertOk()
            ->assertSee($product->nombre)
            ->assertSee('Tarjetas gráficas')
            ->assertSee('NVIDIA');
    }

    public function test_create_product_form_responds_successfully(): void
    {
        $category = Category::create(['nombre' => 'Procesadores']);
        $brand = Brand::create(['nombre' => 'AMD']);

        $this->get(route('productos.create'))
            ->assertOk()
            ->assertSee('Nuevo producto')
            ->assertSee($category->nombre)
            ->assertSee($brand->nombre);
    }

    public function test_a_product_can_be_created_from_the_web(): void
    {
        $payload = $this->validPayload();

        $this->post(route('productos.store'), $payload)
            ->assertRedirect(route('productos.index'))
            ->assertSessionHas('success', 'Producto creado correctamente.');

        $this->assertDatabaseHas('products', [
            'nombre' => 'Tarjeta gráfica',
            'categoria_id' => $payload['categoria_id'],
            'marca_id' => $payload['marca_id'],
            'sku' => 'GPU-001',
            'stock' => 4,
        ]);
    }

    public function test_edit_product_form_responds_successfully(): void
    {
        $product = $this->createProduct();

        $this->get(route('productos.edit', $product))
            ->assertOk()
            ->assertSee('Editar producto')
            ->assertSee($product->nombre)
            ->assertSee($product->sku);
    }

    public function test_a_product_can_be_updated_from_the_web(): void
    {
        $product = $this->createProduct();
        $payload = $this->validPayload([
            'nombre' => 'Tarjeta gráfica actualizada',
            'precio' => 3599.90,
            'stock' => 2,
            'sku' => $product->sku,
        ]);

        $this->put(route('productos.update', $product), $payload)
            ->assertRedirect(route('productos.index'))
            ->assertSessionHas('success', 'Producto actualizado correctamente.');

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'nombre' => 'Tarjeta gráfica actualizada',
            'precio' => 3599.90,
            'stock' => 2,
            'sku' => 'GPU-001',
        ]);
    }

    public function test_editing_a_product_can_change_category(): void
    {
        $product = $this->createProduct();
        $newCategory = Category::create(['nombre' => 'Componentes']);

        $this->put(route('productos.update', $product), $this->validPayload([
            'categoria_id' => $newCategory->id,
        ]))->assertRedirect(route('productos.index'));

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'categoria_id' => $newCategory->id,
        ]);
    }

    public function test_editing_a_product_can_change_brand(): void
    {
        $product = $this->createProduct();
        $newBrand = Brand::create(['nombre' => 'ASUS']);

        $this->put(route('productos.update', $product), $this->validPayload([
            'marca_id' => $newBrand->id,
        ]))->assertRedirect(route('productos.index'));

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'marca_id' => $newBrand->id,
        ]);
    }

    public function test_a_product_can_be_deleted_from_the_web(): void
    {
        $product = $this->createProduct();

        $this->delete(route('productos.destroy', $product))
            ->assertRedirect(route('productos.index'))
            ->assertSessionHas('success', 'Producto eliminado correctamente.');

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    public function test_products_can_be_searched_by_name(): void
    {
        $this->createProduct(['nombre' => 'Teclado mecánico', 'sku' => 'KEY-001']);
        $this->createProduct(['nombre' => 'Monitor', 'sku' => 'MON-001']);

        $this->get(route('productos.index', ['q' => 'Teclado']))
            ->assertOk()
            ->assertSee('Teclado mecánico')
            ->assertDontSee('Monitor');
    }

    public function test_products_can_be_searched_by_sku(): void
    {
        $this->createProduct(['nombre' => 'Producto buscado', 'sku' => 'SEARCH-442']);
        $this->createProduct(['nombre' => 'Producto descartado', 'sku' => 'OTHER-100']);

        $this->get(route('productos.index', ['q' => 'SEARCH-442']))
            ->assertOk()
            ->assertSee('Producto buscado')
            ->assertDontSee('Producto descartado');
    }

    public function test_products_can_be_filtered_by_category(): void
    {
        $processors = Category::create(['nombre' => 'Procesadores']);
        $peripherals = Category::create(['nombre' => 'Periféricos']);
        $this->createProduct(['nombre' => 'Procesador filtrado', 'categoria_id' => $processors->id, 'sku' => 'CPU-100']);
        $this->createProduct(['nombre' => 'Mouse descartado', 'categoria_id' => $peripherals->id, 'sku' => 'MOU-100']);

        $this->get(route('productos.index', ['categoria_id' => $processors->id]))
            ->assertOk()
            ->assertSee('Procesador filtrado')
            ->assertDontSee('Mouse descartado');
    }

    public function test_products_can_be_filtered_by_brand(): void
    {
        $amd = Brand::create(['nombre' => 'AMD']);
        $intel = Brand::create(['nombre' => 'Intel']);
        $this->createProduct(['nombre' => 'Producto AMD', 'marca_id' => $amd->id, 'sku' => 'AMD-100']);
        $this->createProduct(['nombre' => 'Producto Intel', 'marca_id' => $intel->id, 'sku' => 'INT-100']);

        $this->get(route('productos.index', ['marca_id' => $amd->id]))
            ->assertOk()
            ->assertSee('Producto AMD')
            ->assertDontSee('Producto Intel');
    }

    public function test_products_can_be_filtered_by_active_status(): void
    {
        $this->createProduct(['nombre' => 'Producto activo', 'activo' => true, 'sku' => 'ACT-100']);
        $this->createProduct(['nombre' => 'Producto inactivo', 'activo' => false, 'sku' => 'INA-100']);

        $this->get(route('productos.index', ['estado' => 'activo']))
            ->assertOk()
            ->assertSee('Producto activo')
            ->assertDontSee('Producto inactivo');
    }

    public function test_products_can_be_filtered_without_stock(): void
    {
        $this->createProduct(['nombre' => 'Producto agotado', 'stock' => 0, 'sku' => 'OUT-100']);
        $this->createProduct(['nombre' => 'Producto disponible', 'stock' => 8, 'sku' => 'AVL-100']);

        $this->get(route('productos.index', ['stock' => 'sin_stock']))
            ->assertOk()
            ->assertSee('Producto agotado')
            ->assertDontSee('Producto disponible');
    }

    public function test_products_can_be_filtered_by_low_stock(): void
    {
        $this->createProduct(['nombre' => 'Producto bajo', 'stock' => 3, 'sku' => 'LOW-100']);
        $this->createProduct(['nombre' => 'Producto agotado', 'stock' => 0, 'sku' => 'OUT-100']);
        $this->createProduct(['nombre' => 'Producto suficiente', 'stock' => 6, 'sku' => 'OK-100']);

        $this->get(route('productos.index', ['stock' => 'bajo_stock']))
            ->assertOk()
            ->assertSee('Producto bajo')
            ->assertDontSee('Producto agotado')
            ->assertDontSee('Producto suficiente');
    }

    public function test_products_are_paginated_and_filters_are_preserved(): void
    {
        foreach (range(1, 11) as $number) {
            $this->createProduct([
                'nombre' => "Producto {$number}",
                'sku' => "PAGE-{$number}",
            ]);
        }

        $this->get(route('productos.index', ['q' => 'Producto']))
            ->assertOk()
            ->assertViewHas('products', fn ($products) => $products->perPage() === 10
                && $products->count() === 10
                && $products->total() === 11)
            ->assertSee('q=Producto', false);

        $this->get(route('productos.index', ['q' => 'Producto', 'page' => 2]))
            ->assertOk()
            ->assertViewHas('products', fn ($products) => $products->currentPage() === 2
                && $products->count() === 1
                && $products->total() === 11);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        $category = Category::firstOrCreate(['nombre' => 'Tarjetas gráficas']);
        $brand = Brand::firstOrCreate(['nombre' => 'NVIDIA']);

        return array_merge([
            'nombre' => 'Tarjeta gráfica',
            'descripcion' => 'Componente para procesamiento gráfico',
            'categoria_id' => $category->id,
            'marca_id' => $brand->id,
            'precio' => 3299.90,
            'stock' => 4,
            'sku' => 'GPU-001',
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
