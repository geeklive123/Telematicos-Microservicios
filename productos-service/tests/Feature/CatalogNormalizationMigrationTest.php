<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CatalogNormalizationMigrationTest extends TestCase
{
    public function test_existing_products_keep_their_category_and_brand_after_normalization(): void
    {
        $originalConnection = DB::getDefaultConnection();

        Config::set('database.connections.catalog_migration_test', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
        DB::purge('catalog_migration_test');
        DB::setDefaultConnection('catalog_migration_test');

        try {
            $this->createLegacyProductsTable();

            DB::table('products')->insert([
                [
                    'nombre' => 'RTX 4060',
                    'descripcion' => null,
                    'categoria' => 'Tarjetas gráficas',
                    'marca' => 'NVIDIA',
                    'precio' => 3200,
                    'stock' => 5,
                    'sku' => 'GPU-001',
                    'activo' => true,
                ],
                [
                    'nombre' => 'Gabinete genérico',
                    'descripcion' => null,
                    'categoria' => 'Gabinetes',
                    'marca' => null,
                    'precio' => 450,
                    'stock' => 2,
                    'sku' => 'CASE-001',
                    'activo' => true,
                ],
            ]);

            foreach ($this->normalizationMigrations() as $migrationPath) {
                (require $migrationPath)->up();
            }

            $graphicsCard = DB::table('products')->where('sku', 'GPU-001')->first();
            $genericCase = DB::table('products')->where('sku', 'CASE-001')->first();

            $this->assertNotNull($graphicsCard->categoria_id);
            $this->assertNotNull($graphicsCard->marca_id);
            $this->assertSame(
                'Tarjetas gráficas',
                DB::table('categorias')->where('id', $graphicsCard->categoria_id)->value('nombre')
            );
            $this->assertSame(
                'NVIDIA',
                DB::table('marcas')->where('id', $graphicsCard->marca_id)->value('nombre')
            );
            $this->assertSame(
                'Gabinetes',
                DB::table('categorias')->where('id', $genericCase->categoria_id)->value('nombre')
            );
            $this->assertNull($genericCase->marca_id);
            $this->assertFalse(Schema::hasColumn('products', 'categoria'));
            $this->assertFalse(Schema::hasColumn('products', 'marca'));
        } finally {
            DB::disconnect('catalog_migration_test');
            DB::setDefaultConnection($originalConnection);
        }
    }

    private function createLegacyProductsTable(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 150);
            $table->text('descripcion')->nullable();
            $table->string('categoria', 100);
            $table->string('marca', 100)->nullable();
            $table->decimal('precio', 10, 2)->default(0);
            $table->unsignedInteger('stock')->default(0);
            $table->string('sku', 100)->nullable()->unique();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    /**
     * @return list<string>
     */
    private function normalizationMigrations(): array
    {
        return [
            database_path('migrations/2026_09_22_200000_create_categories_and_brands_tables.php'),
            database_path('migrations/2026_09_22_200100_add_catalog_ids_to_products_table.php'),
            database_path('migrations/2026_09_22_200200_backfill_product_catalog_ids.php'),
            database_path('migrations/2026_09_22_200300_enforce_product_catalog_relationships.php'),
            database_path('migrations/2026_09_22_200400_drop_legacy_catalog_columns_from_products.php'),
        ];
    }
}
