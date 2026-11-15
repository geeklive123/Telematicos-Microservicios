<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pedido_attempts', function (Blueprint $table) {
            $table->id();
            $table->uuid('attempt_uuid')->unique();
            $table->unsignedBigInteger('cliente_id');
            $table->string('status', 32);
            $table->string('error_code')->nullable();
            $table->text('error_message')->nullable();
            $table->foreignId('pedido_id')->nullable()->unique()->constrained('pedidos')->restrictOnDelete();
            $table->json('payload');
            $table->json('resolved_details')->nullable();
            $table->json('stock_operations')->nullable();
            $table->boolean('claimed')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pedido_attempts');
    }
};
