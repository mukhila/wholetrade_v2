<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;

class ProductCatalogueController extends Controller
{
    public function index(Request $request)
    {
        $products = Product::withoutGlobalScope('subscriber')
            ->with(['category', 'brand', 'price', 'inventory', 'images'])
            ->where('status', true)
            ->latest()
            ->paginate(12);

        return response()->json($products);
    }
}
