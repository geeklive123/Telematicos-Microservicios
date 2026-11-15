<?php

namespace App\Http\Controllers;

use App\Http\Requests\BrandRequest;
use App\Models\Brand;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class BrandWebController extends Controller
{
    public function index(): View
    {
        return view('brands.index', [
            'brands' => Brand::withCount('products')->orderBy('nombre')->get(),
        ]);
    }

    public function store(BrandRequest $request): RedirectResponse
    {
        Brand::create($request->validated());

        return to_route('marcas.index')->with('success', 'Marca creada correctamente.');
    }

    public function update(BrandRequest $request, Brand $brand): RedirectResponse
    {
        $brand->update($request->validated());

        return to_route('marcas.index')->with('success', 'Marca actualizada correctamente.');
    }

    public function destroy(Brand $brand): RedirectResponse
    {
        if ($brand->products()->exists()) {
            return to_route('marcas.index')
                ->with('error', 'No se puede eliminar una marca que está siendo usada por productos.');
        }

        $brand->delete();

        return to_route('marcas.index')->with('success', 'Marca eliminada correctamente.');
    }
}
