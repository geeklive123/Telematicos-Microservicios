<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_category_can_be_created(): void
    {
        $this->post(route('categorias.store'), [
            'nombre' => 'Procesadores',
            'descripcion' => 'CPU para equipos de escritorio',
            'activo' => true,
        ])->assertRedirect(route('categorias.index'));

        $this->assertDatabaseHas('categorias', [
            'nombre' => 'Procesadores',
            'activo' => true,
        ]);
    }

    public function test_a_duplicate_category_name_returns_an_error(): void
    {
        Category::create(['nombre' => 'Procesadores']);

        $this->from(route('categorias.index'))
            ->post(route('categorias.store'), [
                'nombre' => 'Procesadores',
                'descripcion' => null,
                'activo' => true,
            ])
            ->assertRedirect(route('categorias.index'))
            ->assertSessionHasErrors('nombre');
    }

    public function test_a_brand_can_be_created(): void
    {
        $this->post(route('marcas.store'), [
            'nombre' => 'AMD',
            'activo' => true,
        ])->assertRedirect(route('marcas.index'));

        $this->assertDatabaseHas('marcas', [
            'nombre' => 'AMD',
            'activo' => true,
        ]);
    }

    public function test_a_duplicate_brand_name_returns_an_error(): void
    {
        Brand::create(['nombre' => 'AMD']);

        $this->from(route('marcas.index'))
            ->post(route('marcas.store'), [
                'nombre' => 'AMD',
                'activo' => true,
            ])
            ->assertRedirect(route('marcas.index'))
            ->assertSessionHasErrors('nombre');
    }

    public function test_a_category_used_by_products_cannot_be_deleted(): void
    {
        $category = Category::create(['nombre' => 'Procesadores']);
        $product = $this->createProduct($category);

        $this->delete(route('categorias.destroy', $category))
            ->assertRedirect(route('categorias.index'))
            ->assertSessionHas('error', 'No se puede eliminar una categoría que está siendo usada por productos.');

        $this->assertDatabaseHas('categorias', ['id' => $category->id]);
        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }

    public function test_a_brand_used_by_products_cannot_be_deleted(): void
    {
        $category = Category::create(['nombre' => 'Procesadores']);
        $brand = Brand::create(['nombre' => 'AMD']);
        $product = $this->createProduct($category, $brand);

        $this->delete(route('marcas.destroy', $brand))
            ->assertRedirect(route('marcas.index'))
            ->assertSessionHas('error', 'No se puede eliminar una marca que está siendo usada por productos.');

        $this->assertDatabaseHas('marcas', ['id' => $brand->id]);
        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }

    private function createProduct(Category $category, ?Brand $brand = null): Product
    {
        return Product::create([
            'nombre' => 'Producto de prueba',
            'descripcion' => null,
            'categoria_id' => $category->id,
            'marca_id' => $brand?->id,
            'precio' => 100,
            'stock' => 1,
            'sku' => 'TEST-001',
            'activo' => true,
        ]);
    }
}
