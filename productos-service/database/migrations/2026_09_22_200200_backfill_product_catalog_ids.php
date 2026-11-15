<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('products')
            ->select('categoria')
            ->distinct()
            ->orderBy('categoria')
            ->pluck('categoria')
            ->each(function (string $name) use ($now) {
                $categoryId = DB::table('categorias')->where('nombre', $name)->value('id');

                if ($categoryId === null) {
                    $categoryId = DB::table('categorias')->insertGetId([
                        'nombre' => $name,
                        'activo' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }

                DB::table('products')
                    ->where('categoria', $name)
                    ->update(['categoria_id' => $categoryId]);
            });

        DB::table('products')
            ->whereNotNull('marca')
            ->select('marca')
            ->distinct()
            ->orderBy('marca')
            ->pluck('marca')
            ->each(function (string $name) use ($now) {
                $brandId = DB::table('marcas')->where('nombre', $name)->value('id');

                if ($brandId === null) {
                    $brandId = DB::table('marcas')->insertGetId([
                        'nombre' => $name,
                        'activo' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }

                DB::table('products')
                    ->where('marca', $name)
                    ->update(['marca_id' => $brandId]);
            });
    }

    public function down(): void
    {
        DB::table('products')->update([
            'categoria_id' => null,
            'marca_id' => null,
        ]);
    }
};
