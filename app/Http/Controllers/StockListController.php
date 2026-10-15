<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Product;
use App\Models\ItemCategory;
use App\Models\ProductBrand;

class StockListController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::withoutGlobalScope('subscriber')
            ->with([
                'category' => fn($q) => $q->withoutGlobalScopes(),
                'brand'    => fn($q) => $q->withoutGlobalScopes(),
                'price'    => fn($q) => $q->withoutGlobalScopes(),
                'image'    => fn($q) => $q->withoutGlobalScopes(),
            ])
            ->where('status', true);

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }
        if ($request->filled('brand_id')) {
            $query->where('brand_id', $request->brand_id);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku',  'like', "%{$search}%");
            });
        }

        $categories = ItemCategory::withoutGlobalScope('subscriber')->where('status', true)->get();
        $brands     = ProductBrand::withoutGlobalScope('subscriber')->where('status', true)->get();

        // ── Sorting ────────────────────────────────────────────────────────
        $allowedSorts = [
            'name'          => 'products.name',
            'sku'           => 'products.sku',
            'stock_qty_pcs' => 'products.stock_qty_pcs',
            'stock_qty_box' => 'products.stock_qty_box',
            'cost_item'     => 'product_prices.cost_price_item',
            'sell_item'     => 'product_prices.sales_price_item',
            'total_cost'    => null,
        ];
        $priceSorts = ['cost_item', 'sell_item', 'total_cost'];

        $sortBy  = array_key_exists($request->get('sort_by'), $allowedSorts)
            ? $request->get('sort_by') : 'name';
        $sortDir = in_array($request->get('sort_dir'), ['asc', 'desc'])
            ? $request->get('sort_dir') : 'asc';

        if (in_array($sortBy, $priceSorts)) {
            $query->select('products.*')
                  ->leftJoin('product_prices', 'products.id', '=', 'product_prices.product_id');
        }

        if ($sortBy === 'total_cost') {
            $query->orderByRaw(
                'GREATEST(0, COALESCE(products.stock_qty_pcs,0)) * COALESCE(product_prices.cost_price_item,0)
                 + GREATEST(0, COALESCE(products.stock_qty_box,0)) * COALESCE(product_prices.cost_price_box,0) ' . $sortDir
            );
        } else {
            $query->orderBy($allowedSorts[$sortBy], $sortDir);
        }

        $products = $query->paginate(25)->withQueryString();

        // ── Grand totals — ALL filtered products via SQL ───────────────────
        $totalsQuery = DB::table('products')
            ->leftJoin('product_prices', 'products.id', '=', 'product_prices.product_id')
            ->where('products.status', true)
            ->whereNull('products.deleted_at');

        if ($request->filled('category_id')) {
            $totalsQuery->where('products.category_id', $request->category_id);
        }
        if ($request->filled('brand_id')) {
            $totalsQuery->where('products.brand_id', $request->brand_id);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $totalsQuery->where(function ($q) use ($search) {
                $q->where('products.name', 'like', "%{$search}%")
                  ->orWhere('products.sku',  'like', "%{$search}%");
            });
        }

        $totals = $totalsQuery->selectRaw("
            COALESCE(SUM(GREATEST(0, COALESCE(products.stock_qty_pcs, 0))), 0) AS total_qty_pcs,
            COALESCE(SUM(GREATEST(0, COALESCE(products.stock_qty_box, 0))), 0) AS total_qty_box,
            COALESCE(SUM(
                GREATEST(0, COALESCE(products.stock_qty_pcs, 0)) * COALESCE(product_prices.cost_price_item, 0) +
                GREATEST(0, COALESCE(products.stock_qty_box, 0)) * COALESCE(product_prices.cost_price_box, 0)
            ), 0) AS total_cost
        ")->first();

        $totalQtyPcs    = (int)   ($totals->total_qty_pcs ?? 0);
        $totalQtyBox    = (int)   ($totals->total_qty_box ?? 0);
        $totalCostValue = round($totals->total_cost ?? 0, 2);

        return view('stock-list.index', compact(
            'products', 'categories', 'brands',
            'totalQtyPcs', 'totalQtyBox', 'totalCostValue'
        ));
    }

    public function print(Request $request)
    {
        $query = Product::withoutGlobalScope('subscriber')
            ->with([
                'category'  => fn($q) => $q->withoutGlobalScopes(),
                'brand'     => fn($q) => $q->withoutGlobalScopes(),
                'price'     => fn($q) => $q->withoutGlobalScopes(),
            ])
            ->where('status', true);

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }
        if ($request->filled('brand_id')) {
            $query->where('brand_id', $request->brand_id);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku',  'like', "%{$search}%");
            });
        }

        $products = $query->orderBy('name')->get();

        $totalQtyPcs    = $products->sum(fn($p) => max(0, $p->stock_qty_pcs ?? 0));
        $totalCostValue = $products->sum(fn($p) =>
            max(0, $p->stock_qty_pcs ?? 0) * ($p->price?->cost_price_item ?? 0)
        );

        $filters = $request->only(['category_id', 'brand_id', 'search']);

        return view('stock-list.print', compact(
            'products', 'filters', 'totalQtyPcs', 'totalCostValue'
        ));
    }

    public function export(Request $request)
    {
        $this->checkStaffPermission('stock-list', 'export');
        $query = Product::withoutGlobalScope('subscriber')
            ->with([
                'category'  => fn($q) => $q->withoutGlobalScopes(),
                'brand'     => fn($q) => $q->withoutGlobalScopes(),
                'price'     => fn($q) => $q->withoutGlobalScopes(),
            ])
            ->where('status', true);

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }
        if ($request->filled('brand_id')) {
            $query->where('brand_id', $request->brand_id);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku',  'like', "%{$search}%");
            });
        }

        $products = $query->orderBy('name')->get();
        $filename = 'stock_list_' . now()->format('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($products) {
            $file = fopen('php://output', 'w');
            fputcsv($file, [
                'SKU', 'Product Name',
                'Cost Price (Item)', 'Selling Price (Item)',
                'Qty (Loose/PCS)',
                'Total Cost Value',
            ]);

            foreach ($products as $p) {
                $qtyPcs   = max(0, $p->stock_qty_pcs  ?? 0);
                $costItem = $p->price?->cost_price_item  ?? 0;
                $sellItem = $p->price?->sales_price_item ?? 0;
                $totalCost = $qtyPcs * $costItem;

                fputcsv($file, [
                    $p->sku,
                    $p->name,
                    number_format($costItem, 2),
                    number_format($sellItem, 2),
                    number_format($qtyPcs,   0),
                    number_format($totalCost, 2),
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function addStockForm(Request $request)
    {
        $query = Product::withoutGlobalScope('subscriber')
            ->with([
                'category' => fn($q) => $q->withoutGlobalScopes(),
                'brand'    => fn($q) => $q->withoutGlobalScopes(),
                'price'    => fn($q) => $q->withoutGlobalScopes(),
            ])
            ->where('status', true);

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }
        if ($request->filled('brand_id')) {
            $query->where('brand_id', $request->brand_id);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku',  'like', "%{$search}%");
            });
        }

        $products   = $query->orderBy('name')->paginate(50)->withQueryString();
        $categories = ItemCategory::withoutGlobalScope('subscriber')->where('status', true)->get();
        $brands     = ProductBrand::withoutGlobalScope('subscriber')->where('status', true)->get();

        return view('stock-list.add-stock', compact('products', 'categories', 'brands'));
    }

    public function addStock(Request $request)
    {
        $this->checkStaffPermission('stock-list', 'create');
        $request->validate([
            'add_qty'   => 'required|array',
            'add_qty.*' => 'nullable|numeric|min:0',
        ]);

        $updatedCount = 0;

        DB::beginTransaction();
        try {
            foreach ($request->add_qty as $productId => $addQty) {
                $addQty = (float) $addQty;

                // Skip blank or zero rows
                if ($addQty <= 0) {
                    continue;
                }

                $product = Product::withoutGlobalScope('subscriber')
                    ->where('id', $productId)
                    ->where('status', true)
                    ->first();

                if (!$product) {
                    continue;
                }

                // Clamp current stock to 0 before adding
                $currentQty             = max(0, $product->stock_qty_pcs ?? 0);
                $product->stock_qty_pcs = $currentQty + $addQty;
                $product->save();

                $updatedCount++;
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->withErrors(['error' => 'Stock update failed: ' . $e->getMessage()]);
        }

        return redirect()
            ->route('stock-list.index')
            ->with('success', "Stock added successfully for {$updatedCount} product(s).");
    }

    public function importForm()
    {
        return view('stock-list.import');
    }

    public function import(Request $request)
    {
        $this->checkStaffPermission('stock-list', 'import');
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:5120',
        ]);

        $file   = $request->file('csv_file');
        $handle = fopen($file->getRealPath(), 'r');

        // Skip header row
        fgetcsv($handle);

        $updated = 0;
        $errors  = [];
        $row     = 1;

        DB::beginTransaction();
        try {
            while (($data = fgetcsv($handle)) !== false) {
                $row++;
                if (count($data) < 3) {
                    $errors[] = "Row {$row}: Not enough columns (need SKU, Qty Box, Qty PCS).";
                    continue;
                }

                $sku    = trim($data[0]);
                $rawBox = trim($data[1]);
                $rawPcs = trim($data[2]);
                $qtyBox = is_numeric($rawBox) ? (float) $rawBox : null;
                $qtyPcs = is_numeric($rawPcs) ? (float) $rawPcs : null;

                if (empty($sku)) {
                    $errors[] = "Row {$row}: SKU is empty.";
                    continue;
                }
                if ($qtyBox !== null && $qtyBox < 0) {
                    $errors[] = "Row {$row}: Qty Box cannot be negative (got {$rawBox}).";
                    continue;
                }
                if ($qtyPcs !== null && $qtyPcs < 0) {
                    $errors[] = "Row {$row}: Qty PCS cannot be negative (got {$rawPcs}).";
                    continue;
                }

                $product = Product::withoutGlobalScope('subscriber')
                    ->where('sku', $sku)->first();

                if (!$product) {
                    $errors[] = "Row {$row}: Product with SKU '{$sku}' not found.";
                    continue;
                }

                $updateData = [];
                if ($qtyBox !== null) $updateData['stock_qty_box'] = $qtyBox;
                if ($qtyPcs !== null) $updateData['stock_qty_pcs'] = $qtyPcs;

                if (!empty($updateData)) {
                    $product->update($updateData);
                    $updated++;
                }
            }
            fclose($handle);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            fclose($handle);
            return back()->withErrors(['error' => 'Import failed: ' . $e->getMessage()]);
        }

        $message = "{$updated} product(s) updated successfully.";
        if (count($errors)) {
            $message .= ' ' . count($errors) . ' row(s) had errors.';
            return redirect()->route('stock-list.index')
                ->with('success', $message)
                ->with('import_errors', $errors);
        }

        return redirect()->route('stock-list.index')->with('success', $message);
    }
}
