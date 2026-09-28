<?php

namespace App\Http\Controllers;

use App\Http\Requests\CategoryRequest;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CategoryWebController extends Controller
{
    public function index(): View
    {
        return view('categories.index', [
            'categories' => Category::withCount('products')->orderBy('nombre')->get(),
        ]);
    }

    public function store(CategoryRequest $request): RedirectResponse
    {
        Category::create($request->validated());

        return to_route('categorias.index')->with('success', 'Categoría creada correctamente.');
    }

    public function update(CategoryRequest $request, Category $category): RedirectResponse
    {
        $category->update($request->validated());

        return to_route('categorias.index')->with('success', 'Categoría actualizada correctamente.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        if ($category->products()->exists()) {
            return to_route('categorias.index')
                ->with('error', 'No se puede eliminar una categoría que está siendo usada por productos.');
        }

        $category->delete();

        return to_route('categorias.index')->with('success', 'Categoría eliminada correctamente.');
    }
}
