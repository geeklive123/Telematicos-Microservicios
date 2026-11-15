<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('products')->whereNull('categoria_id')->exists()) {
            throw new RuntimeException('No se pueden eliminar las columnas antiguas: existen productos sin categoría migrada.');
        }

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['categoria', 'marca']);
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('categoria', 100)->nullable();
            $table->string('marca', 100)->nullable();
        });

        DB::table('products')
            ->orderBy('id')
            ->get(['id', 'categoria_id', 'marca_id'])
            ->each(function (object $product) {
                DB::table('products')
                    ->where('id', $product->id)
                    ->update([
                        'categoria' => DB::table('categorias')->where('id', $product->categoria_id)->value('nombre'),
                        'marca' => $product->marca_id === null
                            ? null
                            : DB::table('marcas')->where('id', $product->marca_id)->value('nombre'),
                    ]);
            });

        if (DB::table('products')->whereNull('categoria')->exists()) {
            throw new RuntimeException('No se pudo reconstruir la categoría antigua durante el rollback.');
        }

        Schema::table('products', function (Blueprint $table) {
            $table->string('categoria', 100)->nullable(false)->change();
        });
    }
};
