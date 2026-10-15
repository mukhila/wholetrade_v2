<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class ProductReportController extends Controller
{
    // ---------------------------------------------------------------
    // Shared helper: apply all active filters to a Product query
    // ---------------------------------------------------------------
    private function applyFilters($query, Request $request)
    {
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }
        if ($request->filled('brand_id')) {
            $query->where('brand_id', $request->brand_id);
        }
        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status === 'Active' ? 1 : 0);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('sku',  'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%");
            });
        }
        return $query;
    }

    // ---------------------------------------------------------------
    // Main Report (JSON + Print)
    // ---------------------------------------------------------------
    public function getProductReport(Request $request)
    {
        $query = $this->applyFilters(
            Product::with([
                'category',
                'brand',
                'supplier',
                'uom',
                'price' => fn($q) => $q->withoutGlobalScope('subscriber'),
            ]),
            $request
        );

        $perPage = $request->filled('print') ? $query->count() : $request->get('per_page', 15);
        $products = $query->latest()->paginate($perPage);

        // --- KPIs from FILTERED data ---
        $filteredBase = fn() => $this->applyFilters(Product::query(), $request);

        $totalProducts    = $filteredBase()->count();
        $activeProducts   = $filteredBase()->where('status', 1)->count();
        $inactiveProducts = $filteredBase()->where('status', 0)->count();

        // High/Low margin products within the filtered set
        $priceQuery = fn() => $this->applyFilters(
            Product::with(['price' => fn($q) => $q->withoutGlobalScope('subscriber')]),
            $request
        );

        $highMarginProducts = $priceQuery()->get()
            ->filter(function ($product) {
                if ($product->price && $product->price->cost_price_item > 0) {
                    $margin = (($product->price->sales_price_item - $product->price->cost_price_item)
                              / $product->price->cost_price_item) * 100;
                    return $margin > 30;
                }
                return false;
            })->count();

        $lowMarginProducts = $priceQuery()->get()
            ->filter(function ($product) {
                if ($product->price && $product->price->cost_price_item > 0) {
                    $margin = (($product->price->sales_price_item - $product->price->cost_price_item)
                              / $product->price->cost_price_item) * 100;
                    return $margin < 10;
                }
                return false;
            })->count();

        $kpis = [
            'total_products'       => $totalProducts,
            'active_products'      => $activeProducts,
            'inactive_products'    => $inactiveProducts,
            'high_margin_products' => $highMarginProducts,
            'low_margin_products'  => $lowMarginProducts,
        ];

        if ($request->filled('print')) {
            return view('reports.print.products', [
                'products' => $products,
                'filters'  => $request->except(['print', 'page', 'per_page']),
                'kpis'     => $kpis,
            ]);
        }

        return response()->json([
            'data' => $products->items(),
            'pagination' => [
                'total'        => $products->total(),
                'per_page'     => $products->perPage(),
                'current_page' => $products->currentPage(),
                'last_page'    => $products->lastPage(),
            ],
            'kpis' => $kpis,
        ]);
    }

    public function printProductReport(Request $request)
    {
        $request->merge(['print' => true]);
        return $this->getProductReport($request);
    }

    // ---------------------------------------------------------------
    // Export — now respects all active filters
    // ---------------------------------------------------------------
    public function exportProductReport(Request $request)
    {
        $this->checkStaffPermission('reports', 'export');
        $products = $this->applyFilters(
            Product::with([
                'category',
                'brand',
                'supplier',
                'uom',
                'price' => fn($q) => $q->withoutGlobalScope('subscriber'),
            ]),
            $request
        )->get();

        $filename = 'product_report_' . now()->format('Y-m-d_His') . '.csv';

        return Response::stream(function () use ($products) {
            $file = fopen('php://output', 'w');
            fputcsv($file, [
                'SKU', 'Product Name', 'Category', 'Brand', 'Supplier', 'UOM',
                'Cost Price (Item)', 'Cost Price (Box)', 'Sales Price (Item)', 'Sales Price (Box)',
                'Retail Price', 'Margin %', 'Status',
            ]);

            foreach ($products as $product) {
                $costPrice  = $product->price->cost_price_item  ?? 0;
                $salesPrice = $product->price->sales_price_item ?? 0;
                $margin     = $costPrice > 0
                    ? (($salesPrice - $costPrice) / $costPrice) * 100
                    : 0;

                fputcsv($file, [
                    $product->sku,
                    $product->name,
                    $product->category->name  ?? '-',
                    $product->brand->name     ?? '-',
                    $product->supplier->name  ?? '-',
                    $product->uom->name       ?? '-',
                    $costPrice,
                    $product->price->cost_price_box   ?? 0,
                    $salesPrice,
                    $product->price->sales_price_box  ?? 0,
                    $product->price->retail_price_item ?? 0,
                    round($margin, 2),
                    $product->status ? 'Active' : 'Inactive',
                ]);
            }
            fclose($file);
        }, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
