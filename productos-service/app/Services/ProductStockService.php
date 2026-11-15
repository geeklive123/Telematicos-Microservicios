<?php

namespace App\Services;

use App\Exceptions\ProductStockConflictException;
use App\Models\Product;
use App\Models\StockOperation;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

class ProductStockService
{
    /** @return array{operation: StockOperation, already_processed: bool} */
    public function adjust(Product $product, string $type, array $data): array
    {
        try {
            return DB::transaction(function () use ($product, $type, $data): array {
                $lockedProduct = Product::query()->lockForUpdate()->findOrFail($product->getKey());
                $existing = StockOperation::query()->where('idempotency_key', $data['idempotency_key'])->first();

                if ($existing !== null) {
                    return $this->replay($existing, $lockedProduct, $type, $data);
                }

                $before = $lockedProduct->stock;
                $quantity = (int) $data['cantidad'];
                if ($type === 'discount' && ! $lockedProduct->activo) {
                    throw new ProductStockConflictException('El producto estÃ¡ inactivo.');
                }
                if ($type === 'discount' && $before < $quantity) {
                    throw new ProductStockConflictException('Stock insuficiente.', ['stock_disponible' => $before]);
                }

                $lockedProduct->stock = $type === 'discount' ? $before - $quantity : $before + $quantity;
                $lockedProduct->save();

                $operation = StockOperation::create([
                    'idempotency_key' => $data['idempotency_key'],
                    'product_id' => $lockedProduct->id,
                    'type' => $type,
                    'quantity' => $quantity,
                    'stock_before' => $before,
                    'stock_after' => $lockedProduct->stock,
                    'status' => 'completed',
                    'source_type' => $data['source_type'] ?? null,
                    'source_reference' => $data['source_reference'] ?? null,
                ]);

                return ['operation' => $operation, 'already_processed' => false];
            }, 3);
        } catch (UniqueConstraintViolationException $exception) {
            // A concurrent request for another product may have won the unique key.
            $existing = StockOperation::query()->where('idempotency_key', $data['idempotency_key'])->first();
            if ($existing === null) {
                throw $exception;
            }

            return $this->replay($existing, $product, $type, $data);
        }
    }

    /** @return array{operation: StockOperation, already_processed: bool} */
    private function replay(StockOperation $operation, Product $product, string $type, array $data): array
    {
        if ($operation->idempotency_key !== $data['idempotency_key']
            || $operation->product_id !== $product->id
            || $operation->type !== $type
            || $operation->quantity !== (int) $data['cantidad']
            || $operation->source_type !== ($data['source_type'] ?? null)
            || $operation->source_reference !== ($data['source_reference'] ?? null)) {
            throw new ProductStockConflictException('La clave de idempotencia ya fue utilizada para otra operaciÃ³n.');
        }

        return ['operation' => $operation, 'already_processed' => true];
    }
}
