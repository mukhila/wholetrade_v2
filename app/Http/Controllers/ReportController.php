<?php

namespace App\Http\Controllers;

use App\Models\Associate;
use App\Models\ItemCategory;
use App\Models\Product;
use App\Models\ProductBrand;
use App\Models\User;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index()
    {
        return view('reports.index');
    }

    public function getFilterOptions()
    {
        return response()->json([
            'categories' => ItemCategory::select('id', 'name')->get(),
            'brands' => ProductBrand::select('id', 'name')->get(),
            'suppliers' => Associate::where('type', 'Supplier')->select('id', 'name')->get(),
            'customers' => Associate::where('type', 'Customer')->select('id', 'name')->get(),
            'salespersons' => User::whereHas('roles', function($q) {
                $q->whereIn('name', ['admin', 'salesperson']);
            })->select('id', 'name')->get(),
            'products' => Product::select('id', 'sku', 'name')->get(),
        ]);
    }
}
