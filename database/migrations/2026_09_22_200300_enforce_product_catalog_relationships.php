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
            throw new RuntimeException('No se puede completar la migración: existen productos sin categoria_id.');
        }

        Schema::table('products', function (Blueprint $table) {
            $table->unsignedBigInteger('categoria_id')->nullable(false)->change();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->foreign('categoria_id')
                ->references('id')
                ->on('categorias')
                ->restrictOnDelete()
                ->cascadeOnUpdate();
            $table->foreign('marca_id')
                ->references('id')
                ->on('marcas')
                ->restrictOnDelete()
                ->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['categoria_id']);
            $table->dropForeign(['marca_id']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->unsignedBigInteger('categoria_id')->nullable()->change();
        });
    }
};
