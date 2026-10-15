<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;

class StockReportController extends Controller
{
    // ---------------------------------------------------------------
    // Shared helper: build a filtered base query for product_inventories
    // ---------------------------------------------------------------
    private function buildFilteredBase(Request $request, bool $withStatusFilter = true)
    {
        $query = DB::table('product_inventories')
            ->join('products', 'product_inventories.product_id', '=', 'products.id');

        if (auth()->check() && !auth()->user()->isSuperAdmin() && auth()->user()->subscriber_id) {
            $subscriberId = auth()->user()->subscriber_id;
            $query->where(function ($q) use ($subscriberId) {
                $q->where('product_inventories.subscriber_id', $subscriberId)
                  ->orWhereNull('product_inventories.subscriber_id');
            });
        }

        if ($request->filled('category_id')) {
            $query->where('products.category_id', $request->category_id);
        }
        if ($request->filled('brand_id')) {
            $query->where('products.brand_id', $request->brand_id);
        }
        if ($withStatusFilter && $request->filled('inventory_status')) {
            $this->applyStatusFilter($query, $request->inventory_status);
        }
        if ($request->filled('batch_no')) {
            $query->where('product_inventories.lot_no', 'like', "%{$request->batch_no}%");
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('products.sku',  'like', "%{$search}%")
                  ->orWhere('products.name', 'like', "%{$search}%");
            });
        }

        return $query;
    }

    private function applyStatusFilter($query, string $status): void
    {
        if ($status === 'low_stock') {
            $query->whereRaw('product_inventories.stock_in_hand <= products.min_stock_qty')
                  ->where('product_inventories.stock_in_hand', '>', 0);
        } elseif ($status === 'out_of_stock') {
            $query->where('product_inventories.stock_in_hand', '<=', 0);
        } elseif ($status === 'normal') {
            $query->whereRaw('product_inventories.stock_in_hand > products.min_stock_qty');
        }
    }

    // ---------------------------------------------------------------
    // Main Report (JSON + Print)
    // ---------------------------------------------------------------
    public function getStockReport(Request $request)
    {
        // --- Main data query ---
        $query = $this->buildFilteredBase($request)
            ->select(
                'product_inventories.*',
                'products.sku',
                'products.name as product_name',
                'products.category_id',
                'products.brand_id',
                'products.min_stock_qty',
                'products.reorder_qty'
            );

        $total = $query->count();

        $perPage = $request->get('per_page', 15);
        $page    = $request->get('page', 1);
        $offset  = ($page - 1) * $perPage;

        $inventoryItems = $request->filled('print')
            ? (clone $query)->orderBy('product_inventories.created_at', 'desc')->get()
            : (clone $query)->orderBy('product_inventories.created_at', 'desc')->skip($offset)->take($perPage)->get();

        // --- Enrich items ---
        $categoryIds = $inventoryItems->pluck('category_id')->filter()->unique()->toArray();
        $brandIds    = $inventoryItems->pluck('brand_id')->filter()->unique()->toArray();
        $productIds  = $inventoryItems->pluck('product_id')->filter()->unique()->toArray();

        $categories = DB::table('item_categories')->whereIn('id', $categoryIds)->pluck('name', 'id');
        $brands     = DB::table('product_brands')->whereIn('id', $brandIds)->pluck('name', 'id');
        $prices     = DB::table('product_prices')->whereIn('product_id', $productIds)->get()->keyBy('product_id');

        $inventory = $inventoryItems->map(function ($item) use ($categories, $brands, $prices) {
            $item = (array) $item;
            $item['product'] = [
                'sku'           => $item['sku'],
                'name'          => $item['product_name'],
                'min_stock_qty' => $item['min_stock_qty'],
                'reorder_qty'   => $item['reorder_qty'],
                'category'      => isset($categories[$item['category_id']]) ? ['name' => $categories[$item['category_id']]] : null,
                'brand'         => isset($brands[$item['brand_id']])        ? ['name' => $brands[$item['brand_id']]]        : null,
                'price'         => isset($prices[$item['product_id']])      ? (array) $prices[$item['product_id']]          : null,
            ];
            return (object) $item;
        });

        // --- KPIs from FILTERED data ---
        // Sums respect ALL active filters (including inventory_status)
        $filteredBase = $this->buildFilteredBase($request, withStatusFilter: true);
        $totalStockQty   = (clone $filteredBase)->sum('product_inventories.stock_in_hand');
        $totalStockValue = (clone $filteredBase)->sum('product_inventories.stock_value');
        $filteredCount   = (clone $filteredBase)->count();

        // Low/Out-of-stock counts across the filtered set (excluding inventory_status so we always get the breakdown)
        $crossBase = $this->buildFilteredBase($request, withStatusFilter: false);
        $lowStockCount = (clone $crossBase)
            ->where('product_inventories.stock_in_hand', '>', 0)
            ->whereRaw('product_inventories.stock_in_hand <= products.min_stock_qty')
            ->count();
        $outOfStockCount = (clone $crossBase)
            ->where('product_inventories.stock_in_hand', '<=', 0)
            ->count();

        $kpis = [
            'total_stock_qty'       => $totalStockQty,
            'total_stock_value'     => round($totalStockValue, 2),
            'low_stock_count'       => $lowStockCount,
            'out_of_stock_count'    => $outOfStockCount,
            'expiring_soon_count'   => 0,
            'filtered_count'        => $filteredCount,
        ];

        if ($request->filled('print')) {
            return view('reports.print.stock', [
                'inventory' => $inventory,
                'filters'   => $request->except(['print', 'page', 'per_page']),
                'kpis'      => $kpis,
            ]);
        }

        return response()->json([
            'data' => $inventory->toArray(),
            'pagination' => [
                'total'        => $total,
                'per_page'     => (int) $perPage,
                'current_page' => (int) $page,
                'last_page'    => (int) ceil($total / $perPage),
            ],
            'kpis' => $kpis,
        ]);
    }

    public function printStockReport(Request $request)
    {
        $request->merge(['print' => true]);
        return $this->getStockReport($request);
    }

    // ---------------------------------------------------------------
    // Export (also fully filtered)
    // ---------------------------------------------------------------
    public function exportStockReport(Request $request)
    {
        $this->checkStaffPermission('reports', 'export');
        $inventory = $this->buildFilteredBase($request)
            ->leftJoin('item_categories', 'products.category_id', '=', 'item_categories.id')
            ->leftJoin('product_brands',  'products.brand_id',    '=', 'product_brands.id')
            ->select(
                'product_inventories.*',
                'products.sku',
                'products.name as product_name',
                'products.min_stock_qty',
                'products.reorder_qty',
                'item_categories.name as category_name',
                'product_brands.name as brand_name'
            )
            ->orderBy('product_inventories.created_at', 'desc')
            ->get();

        $filename = 'stock_report_' . now()->format('Y-m-d_His') . '.csv';

        return Response::stream(function () use ($inventory) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['SKU', 'Product Name', 'Category', 'Brand', 'Stock in Hand',
                            'Min Stock Qty', 'Reorder Qty', 'Stock Value', 'Batch No', 'Status']);

            foreach ($inventory as $item) {
                $stockStatus = 'Normal';
                if ($item->stock_in_hand <= 0) {
                    $stockStatus = 'Out of Stock';
                } elseif ($item->stock_in_hand <= $item->min_stock_qty) {
                    $stockStatus = 'Low Stock';
                }

                fputcsv($file, [
                    $item->sku,
                    $item->product_name,
                    $item->category_name ?? '-',
                    $item->brand_name    ?? '-',
                    $item->stock_in_hand,
                    $item->min_stock_qty,
                    $item->reorder_qty,
                    $item->stock_value,
                    $item->lot_no ?? '-',
                    $stockStatus,
                ]);
            }
            fclose($file);
        }, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
