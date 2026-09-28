<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductWebController extends Controller
{
    public function index(Request $request): View
    {
        $products = Product::query()
            ->with(['category:id,nombre', 'brand:id,nombre'])
            ->when($request->filled('q'), function ($query) use ($request) {
                $search = trim((string) $request->query('q'));

                $query->where(function ($query) use ($search) {
                    $query->where('nombre', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('categoria_id'), fn ($query) => $query->where('categoria_id', $request->integer('categoria_id')))
            ->when($request->filled('marca_id'), fn ($query) => $query->where('marca_id', $request->integer('marca_id')))
            ->when($request->query('estado') === 'activo', fn ($query) => $query->where('activo', true))
            ->when($request->query('estado') === 'inactivo', fn ($query) => $query->where('activo', false))
            ->when($request->query('stock') === 'con_stock', fn ($query) => $query->where('stock', '>', 0))
            ->when($request->query('stock') === 'sin_stock', fn ($query) => $query->where('stock', 0))
            ->when($request->query('stock') === 'bajo_stock', fn ($query) => $query->whereBetween('stock', [1, 5]))
            ->latest()
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        $summary = Product::query()
            ->toBase()
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN activo = 1 THEN 1 ELSE 0 END) as activos')
            ->selectRaw('SUM(CASE WHEN stock = 0 THEN 1 ELSE 0 END) as sin_stock')
            ->selectRaw('SUM(CASE WHEN stock > 0 AND stock <= 5 THEN 1 ELSE 0 END) as bajo_stock')
            ->first();

        return view('products.index', [
            'products' => $products,
            'summary' => $summary,
            'categories' => Category::orderBy('nombre')->get(['id', 'nombre']),
            'brands' => Brand::orderBy('nombre')->get(['id', 'nombre']),
        ]);
    }

    public function create(): View
    {
        return view('products.create', [
            'categories' => Category::where('activo', true)->orderBy('nombre')->get(),
            'brands' => Brand::where('activo', true)->orderBy('nombre')->get(),
        ]);
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        Product::create($request->validated());

        return to_route('productos.index')
            ->with('success', 'Producto creado correctamente.');
    }

    public function edit(Product $product): View
    {
        return view('products.edit', [
            'product' => $product,
            'categories' => Category::query()
                ->where(fn ($query) => $query->where('activo', true)->orWhereKey($product->categoria_id))
                ->orderBy('nombre')
                ->get(),
            'brands' => Brand::query()
                ->where(fn ($query) => $query->where('activo', true)->orWhereKey($product->marca_id))
                ->orderBy('nombre')
                ->get(),
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $product->update($request->validated());

        return to_route('productos.index')
            ->with('success', 'Producto actualizado correctamente.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $product->delete();

        return to_route('productos.index')
            ->with('success', 'Producto eliminado correctamente.');
    }
}
