<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdjustProductStockRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Services\ProductStockService;
use Illuminate\Http\JsonResponse;

class ProductStockController extends Controller
{
    public function __construct(private readonly ProductStockService $productStockService) {}

    public function deduct(AdjustProductStockRequest $request, Product $product): JsonResponse
    {
        return $this->respond($request, $product, 'discount');
    }

    public function replenish(AdjustProductStockRequest $request, Product $product): JsonResponse
    {
        return $this->respond($request, $product, 'restore');
    }

    private function respond(AdjustProductStockRequest $request, Product $product, string $type): JsonResponse
    {
        $result = $this->productStockService->adjust($product, $type, $request->validated());
        $operation = $result['operation'];
        $product->refresh()->load(['category:id,nombre', 'brand:id,nombre']);
        $resource = (new ProductResource($product))->resolve();
        $resource['stock'] = $operation->stock_after;

        return response()->json([...$resource,
            'product_id' => $operation->product_id,
            'operation_id' => $operation->id,
            'idempotency_key' => $operation->idempotency_key,
            'stock_before' => $operation->stock_before,
            'stock_after' => $operation->stock_after,
            'already_processed' => $result['already_processed'],
        ]);
    }
}
