<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Product;
use Illuminate\Http\Request;
use App\Models\ProductCategory;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        $branchId = $this->currentBranchId($request);
        if (! $branchId) {
            return redirect()->route('dashboard');
        }

        $filters = $request->only('search');
        $products = Product::query()
            ->where('restaurant_id', $user->restaurant_id)
            ->where('branch_id', $branchId)
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->with('category')
            ->paginate(12);

        $categories = ProductCategory::where('restaurant_id', $user->restaurant_id)->get();

        $editingProduct = null;
        if ($request->filled('edit')) {
            $editingProduct = Product::where('restaurant_id', $user->restaurant_id)
                ->where('branch_id', $branchId)
                ->find($request->edit);
        }

        return Inertia::render('Products/Index', [
            'products' => $products,
            'categories' => $categories,
            'filters' => $filters,
            'editingProduct' => $editingProduct,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProductRequest $request)
    {
        $user = auth()->user();
        $branchId = $this->currentBranchId($request);
        if (! $branchId) {
            return redirect()->route('dashboard');
        }

        $validated = $request->validated();
        $restaurantId = $user->restaurant_id;

        $imageUrl = null;
        if ($request->hasFile('image_url')) {
            $file = $request->file('image_url');
            $imagePath = $file->store("Restaurants/{$restaurantId}/Products", 'public');
            $imageUrl = asset("storage/{$imagePath}");
        }

        Product::create([
            ...$validated,
            'image_url' => $imageUrl,
            'restaurant_id' => $restaurantId,
            'branch_id' => $branchId,
        ]);

        return redirect()->route('products.index')->with('success', 'Producto creado correctamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Product $product)
    {
        $user = auth()->user();
        $branchId = $this->currentBranchId();
        if ($product->restaurant_id !== $user->restaurant_id || $product->branch_id !== $branchId) {
            abort(404);
        }
        return redirect()->route('products.index', ['edit' => $product->id]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProductRequest $request, Product $product)
    {
        $user = auth()->user();
        $branchId = $this->currentBranchId($request);
        if ($product->restaurant_id !== $user->restaurant_id || $product->branch_id !== $branchId) {
            abort(404);
        }

        $validated = $request->validated();

        if ($request->hasFile('image_url')) {
            if ($product->image_url) {
                $path = str_replace(asset('storage/'), '', $product->image_url);
                Storage::disk('public')->delete($path);
            }
            $file = $request->file('image_url');
            $imagePath = $file->store("Restaurants/{$product->restaurant_id}/Products", 'public');
            $validated['image_url'] = asset("storage/{$imagePath}");
        }

        $product->update($validated);

        return redirect()->route('products.index')->with('success', 'Producto actualizado correctamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        //
    }
}
