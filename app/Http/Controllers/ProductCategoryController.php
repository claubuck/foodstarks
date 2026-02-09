<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductCategoryRequest;
use App\Http\Requests\UpdateProductCategoryRequest;
use App\Models\ProductCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductCategoryController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (! in_array($request->user()?->role, ['owner', 'manager'])) {
                abort(403, 'No autorizado.');
            }
            return $next($request);
        });
    }

    public function index(Request $request): Response
    {
        $user = $request->user();
        $categories = ProductCategory::where('restaurant_id', $user->restaurant_id)
            ->orderBy('name')
            ->get(['id', 'name', 'description']);

        return Inertia::render('Settings/Categories', [
            'categories' => $categories,
        ]);
    }

    public function store(StoreProductCategoryRequest $request): RedirectResponse
    {
        ProductCategory::create([
            'restaurant_id' => $request->user()->restaurant_id,
            'name' => $request->name,
            'description' => $request->description,
        ]);

        return redirect()->route('product-categories.index')->with('success', 'Categoría creada.');
    }

    public function update(UpdateProductCategoryRequest $request, ProductCategory $productCategory): RedirectResponse
    {
        $user = $request->user();
        if ($productCategory->restaurant_id !== $user->restaurant_id) {
            abort(404);
        }
        $productCategory->update($request->only(['name', 'description']));

        return redirect()->route('product-categories.index')->with('success', 'Categoría actualizada.');
    }

    public function destroy(Request $request, ProductCategory $productCategory): RedirectResponse
    {
        $user = $request->user();
        if ($productCategory->restaurant_id !== $user->restaurant_id) {
            abort(404);
        }
        if ($productCategory->products()->exists()) {
            return redirect()->route('product-categories.index')
                ->with('error', 'No se puede eliminar: tiene productos asociados.');
        }
        $productCategory->delete();

        return redirect()->route('product-categories.index')->with('success', 'Categoría eliminada.');
    }
}
