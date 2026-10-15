<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\ProductAlias;

class ProductAliasController extends Controller
{
    public function index(Request $request)
    {
        $query = ProductAlias::withoutGlobalScope('subscriber')
            ->with(['product' => fn($q) => $q->withoutGlobalScope('subscriber')])
            ->orderBy('created_at', 'desc');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('alias_name', 'like', "%{$search}%")
                  ->orWhere('alias_sku', 'like', "%{$search}%")
                  ->orWhereHas('product', fn($p) => $p->withoutGlobalScope('subscriber')
                      ->where('name', 'like', "%{$search}%"));
            });
        }

        $aliases = $query->paginate(25)->withQueryString();

        return view('product-aliases.index', compact('aliases'));
    }

    public function searchProducts(Request $request)
    {
        $q = $request->input('q', '');

        $products = Product::withoutGlobalScope('subscriber')
            ->where('status', 1)
            ->where(function ($query) use ($q) {
                $query->where('name', 'like', "%{$q}%")
                      ->orWhere('sku', 'like', "%{$q}%");
            })
            ->orderBy('name')
            ->limit(30)
            ->get(['id', 'name', 'sku']);

        return response()->json([
            'results' => $products->map(fn($p) => [
                'id'   => $p->id,
                'text' => $p->name . ($p->sku ? ' (' . $p->sku . ')' : ''),
            ]),
        ]);
    }

    public function create()
    {
        $this->checkStaffPermission('products', 'edit');

        return view('product-aliases.create');
    }

    public function store(Request $request)
    {
        $this->checkStaffPermission('products', 'edit');

        $request->validate([
            'product_id'  => 'required|exists:products,id',
            'alias_name'  => 'required|string|max:255',
            'alias_sku'   => 'nullable|string|max:255|unique:product_aliases,alias_sku',
            'description' => 'nullable|string|max:1000',
        ]);

        $product = Product::withoutGlobalScope('subscriber')->findOrFail($request->product_id);

        if ($product->aliases()->withoutGlobalScope('subscriber')
                ->where('alias_name', $request->alias_name)->exists()) {
            return back()
                ->withErrors(['alias_name' => 'This alias name already exists for this product.'])
                ->withInput();
        }

        $product->aliases()->create([
            'alias_name'  => $request->alias_name,
            'alias_sku'   => $request->alias_sku ?: null,
            'description' => $request->description ?: null,
            'status'      => $request->has('status'),
        ]);

        return redirect()->route('product-aliases.index')
            ->with('success', 'Alias "' . $request->alias_name . '" added successfully.');
    }

    public function edit($id)
    {
        $this->checkStaffPermission('products', 'edit');

        $alias = ProductAlias::withoutGlobalScope('subscriber')
            ->with(['product' => fn($q) => $q->withoutGlobalScope('subscriber')])
            ->findOrFail($id);

        return view('product-aliases.edit', compact('alias'));
    }

    public function update(Request $request, $id)
    {
        $this->checkStaffPermission('products', 'edit');

        $alias = ProductAlias::withoutGlobalScope('subscriber')->findOrFail($id);

        $request->validate([
            'product_id'  => 'required|exists:products,id',
            'alias_name'  => 'required|string|max:255',
            'alias_sku'   => 'nullable|string|max:255|unique:product_aliases,alias_sku,' . $id,
            'description' => 'nullable|string|max:1000',
        ]);

        $duplicate = ProductAlias::withoutGlobalScope('subscriber')
            ->where('product_id', $request->product_id)
            ->where('alias_name', $request->alias_name)
            ->where('id', '!=', $id)
            ->exists();

        if ($duplicate) {
            return back()
                ->withErrors(['alias_name' => 'This alias name already exists for this product.'])
                ->withInput();
        }

        $alias->update([
            'product_id'  => $request->product_id,
            'alias_name'  => $request->alias_name,
            'alias_sku'   => $request->alias_sku ?: null,
            'description' => $request->description ?: null,
            'status'      => $request->has('status'),
        ]);

        return redirect()->route('product-aliases.index')
            ->with('success', 'Alias updated successfully.');
    }

    public function destroy($id)
    {
        $this->checkStaffPermission('products', 'edit');

        $alias = ProductAlias::withoutGlobalScope('subscriber')->findOrFail($id);
        $alias->delete();

        return redirect()->route('product-aliases.index')
            ->with('success', 'Alias deleted successfully.');
    }
}
