<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class ProductController extends Controller
{
    public function index(): JsonResponse
    {
        $products = Product::with(['category:id,nombre', 'brand:id,nombre'])->get();

        return response()->json(ProductResource::collection($products)->resolve());
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = Product::create($request->validated());

        return (new ProductResource($product->load(['category:id,nombre', 'brand:id,nombre'])))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Product $product): JsonResponse
    {
        return (new ProductResource($product->load(['category:id,nombre', 'brand:id,nombre'])))
            ->response();
    }

    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        $product->update($request->validated());

        return (new ProductResource($product->load(['category:id,nombre', 'brand:id,nombre'])))
            ->response();
    }

    public function destroy(Product $product): Response
    {
        $product->delete();

        return response()->noContent();
    }
}
