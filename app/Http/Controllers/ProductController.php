<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ItemCategory;
use App\Models\ProductBrand;
use App\Models\Associate;
use App\Models\UnitOfMeasure;
use App\Models\Size;
use App\Models\Color;
use App\Models\ProductAlias;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::withoutGlobalScope('subscriber')
            ->with([
                'category'     => fn($q) => $q->withoutGlobalScopes(),
                'brand'        => fn($q) => $q->withoutGlobalScopes(),
                'uom'          => fn($q) => $q->withoutGlobalScopes(),
                'inventory'    => fn($q) => $q->withoutGlobalScopes(),
                'price'        => fn($q) => $q->withoutGlobalScopes(),
                'image' => fn($q) => $q->withoutGlobalScopes(),
            ])
            ->latest();

        // Filters
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('brand_id')) {
            $query->where('brand_id', $request->brand_id);
        }

        if ($request->filled('status') && $request->status !== '') {
            $query->where('status', (bool) $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        $products = $query->paginate(25);

        // Fetch Data for Filters
        $categories = ItemCategory::withoutGlobalScope('subscriber')->where('status', true)->get();
        $brands = ProductBrand::withoutGlobalScope('subscriber')->where('status', true)->get();

        // Check role for View Selection
        $user = auth()->user();
        if ($user && ($user->hasRole('salesperson') || $user->hasRole('store_person'))) {
            return view('products.grid', compact('products', 'categories', 'brands'));
        }

        return view('products.index', compact('products', 'categories', 'brands'));
    }

    public function create()
    {
        $categories = ItemCategory::withoutGlobalScope('subscriber')->where('status', true)->get();
        $brands = ProductBrand::withoutGlobalScope('subscriber')->where('status', true)->get();
        $suppliers = Associate::withoutGlobalScope('subscriber')->where('type', 'Supplier')->where('status', true)->get();
        $uoms = UnitOfMeasure::withoutGlobalScope('subscriber')->where('status', true)->get();
        $sizes = Size::withoutGlobalScope('subscriber')->where('status', true)->get();
        $colors = Color::withoutGlobalScope('subscriber')->where('status', true)->get();

        // SKU + Barcode Prediction
        $lastId = Product::withoutGlobalScope('subscriber')->max('id') ?? 0;
        $sku = 'SKU-' . str_pad($lastId + 1, 6, '0', STR_PAD_LEFT);
        $predictedBarcode = 'WT' . str_pad($lastId + 1, 8, '0', STR_PAD_LEFT);

        return view('products.create', compact('categories', 'brands', 'suppliers', 'uoms', 'sizes', 'colors', 'sku', 'predictedBarcode'));
    }

    public function store(Request $request)
    {
        $this->checkStaffPermission('products', 'create');
        $request->validate([
            'sku' => 'required|unique:products,sku',
            'name' => 'required|string|max:255',
            'product_type' => 'required|in:STANDARD,VARIANT,COMPOSITE',
            'category_id' => 'nullable|exists:item_categories,id',
            'brand_id' => 'nullable|exists:product_brands,id',
            'uom_id' => 'nullable|exists:unit_of_measures,id',
            'cost_price_item' => 'nullable|numeric|min:0',
            'cost_price_box' => 'nullable|numeric|min:0',
            'sales_price_item' => 'nullable|numeric|min:0',
            'min_stock_qty' => 'nullable|integer|min:0',
            'expiry_date' => 'nullable|date',
            'image' => 'nullable|image|max:2048',
        ]);

        DB::beginTransaction();

        try {
            // 1. Create Product
            $productData = $request->only([
                'sku', 'name', 'description', 'product_type',
                'category_id', 'brand_id', 'supplier_id', 'uom_id',
                'barcode', 'expiry_date', 'weight', 'pack_size_id', 'pack_size_qty'
            ]);
            $productData['status'] = $request->has('status');
            $productData['inventory_control'] = $request->has('inventory_control');
            $productData['min_stock_qty'] = $request->input('min_stock_qty', 0) ?? 0;
            $productData['reorder_qty'] = $request->input('reorder_qty', 0) ?? 0;
            $productData['stock_qty_pcs'] = $request->input('stock_qty_pcs', 0) ?? 0;
            $productData['stock_qty_box'] = $request->input('stock_qty_box', 0) ?? 0;
            $productData['pack_size_qty'] = $request->input('pack_size_qty', 1) ?? 1;

            $product = Product::create($productData);

            // Auto-generate barcode if none was provided
            if (empty($product->barcode)) {
                $product->update(['barcode' => 'WT' . str_pad($product->id, 8, '0', STR_PAD_LEFT)]);
            }

            // 2. Create Prices
            $priceData = $request->only([
                'cost_price_item', 'cost_price_box',
                'margin_type', 'margin', 'tax_rate',
                'sales_price_item', 'sales_price_box',
                'min_sales_price_item', 'min_sales_price_box',
                'retail_price_item', 'retail_price_box',
                'min_retail_price_item', 'min_retail_price_box'
            ]);
            // fill defaults if null
            foreach ($priceData as $key => $val) {
                if (is_null($val) && str_contains($key, 'margin_type')) $priceData[$key] = 'percentage';
                elseif (is_null($val)) $priceData[$key] = 0;
            }
            $product->price()->create($priceData);

            // 3. Create Inventory
            $inventoryData = $request->only(['have_batch', 'have_serial', 'lot_no']);
            $inventoryData['have_batch'] = $request->has('have_batch');
            $inventoryData['have_serial'] = $request->has('have_serial');
            $inventoryData['stock_in_hand'] = $request->input('stock_in_hand', 0);
            
            $product->inventory()->create($inventoryData);

            // 4. Handle Image
            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $ext  = $file->getClientOriginalExtension() ?: 'jpg';
                $path = $file->storeAs('products', Str::random(40) . '.' . $ext, 'public_uploads');
                $product->images()->create([
                    'image_path' => $path,
                    'is_primary'  => true,
                ]);
            }

            DB::commit();
            return redirect()->route('products.index')->with('success', 'Product created successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Failed to create product: ' . $e->getMessage()]);
        }
    }

    public function show($id)
    {
        $product = Product::withoutGlobalScope('subscriber')
            ->with([
                'category'  => fn($q) => $q->withoutGlobalScopes(),
                'brand'     => fn($q) => $q->withoutGlobalScopes(),
                'uom'       => fn($q) => $q->withoutGlobalScopes(),
                'inventory' => fn($q) => $q->withoutGlobalScopes(),
                'price'     => fn($q) => $q->withoutGlobalScopes(),
                'images'    => fn($q) => $q->withoutGlobalScopes(),
                'image'     => fn($q) => $q->withoutGlobalScopes(),
                'aliases'   => fn($q) => $q->withoutGlobalScope('subscriber'),
            ])
            ->findOrFail($id);
        return view('products.show', compact('product'));
    }

    public function edit($id)
    {
        $product = Product::withoutGlobalScope('subscriber')
            ->with([
                'price'     => fn($q) => $q->withoutGlobalScopes(),
                'inventory' => fn($q) => $q->withoutGlobalScopes(),
                'images'    => fn($q) => $q->withoutGlobalScopes(),
                'aliases'   => fn($q) => $q->withoutGlobalScope('subscriber'),
            ])
            ->findOrFail($id);

        $categories = ItemCategory::withoutGlobalScope('subscriber')->where('status', true)->get();
        $brands = ProductBrand::withoutGlobalScope('subscriber')->where('status', true)->get();
        $suppliers = Associate::withoutGlobalScope('subscriber')->where('type', 'Supplier')->where('status', true)->get();
        $uoms = UnitOfMeasure::withoutGlobalScope('subscriber')->where('status', true)->get();
        $sizes = Size::withoutGlobalScope('subscriber')->where('status', true)->get();
        $colors = Color::withoutGlobalScope('subscriber')->where('status', true)->get();

        return view('products.edit', compact('product', 'categories', 'brands', 'suppliers', 'uoms', 'sizes', 'colors'));
    }

    public function update(Request $request, $id)
    {
        $this->checkStaffPermission('products', 'edit');
        $request->validate([
            'sku' => 'required|unique:products,sku,' . $id,
            'name' => 'required|string|max:255',
            'product_type' => 'required|in:STANDARD,VARIANT,COMPOSITE',
            'category_id' => 'nullable|exists:item_categories,id',
            'brand_id' => 'nullable|exists:product_brands,id',
            'uom_id' => 'nullable|exists:unit_of_measures,id',
            'cost_price_item' => 'nullable|numeric|min:0',
            'cost_price_box' => 'nullable|numeric|min:0',
            'sales_price_item' => 'nullable|numeric|min:0',
            'expiry_date' => 'nullable|date',
            'image' => 'nullable|image|max:2048',
        ]);

        $product = Product::withoutGlobalScope('subscriber')->findOrFail($id);

        DB::beginTransaction();
        try {
            // 1. Update Product
            $productData = $request->only([
                'sku', 'name', 'description', 'product_type',
                'category_id', 'brand_id', 'supplier_id', 'uom_id',
                'barcode', 'expiry_date', 'weight', 'pack_size_id'
            ]);
            $productData['status'] = $request->has('status');
            $productData['inventory_control'] = $request->has('inventory_control');
            $productData['min_stock_qty'] = $request->input('min_stock_qty', 0) ?? 0;
            $productData['reorder_qty'] = $request->input('reorder_qty', 0) ?? 0;
            $productData['stock_qty_pcs'] = $request->input('stock_qty_pcs', 0) ?? 0;
            $productData['stock_qty_box'] = $request->input('stock_qty_box', 0) ?? 0;
            $productData['pack_size_qty'] = $request->input('pack_size_qty', 1) ?? 1;
            $product->update($productData);

            // 2. Update Prices
             $priceData = $request->only([
                'cost_price_item', 'cost_price_box',
                'margin_type', 'margin', 'tax_rate',
                'sales_price_item', 'sales_price_box',
                'min_sales_price_item', 'min_sales_price_box',
                'retail_price_item', 'retail_price_box',
                'min_retail_price_item', 'min_retail_price_box'
            ]);
             // fill defaults if null
            foreach ($priceData as $key => $val) {
                if (is_null($val) && str_contains($key, 'margin_type')) $priceData[$key] = 'percentage';
                elseif (is_null($val)) $priceData[$key] = 0;
            }
            $product->price()->updateOrCreate(['product_id' => $id], $priceData);

            // 3. Update Inventory
            $inventoryData = $request->only(['have_batch', 'have_serial', 'lot_no']);
            $inventoryData['have_batch'] = $request->has('have_batch');
            $inventoryData['have_serial'] = $request->has('have_serial');
            $product->inventory()->updateOrCreate(['product_id' => $id], $inventoryData);

            // 4. Replace image
            if ($request->hasFile('image')) {
                $old = $product->images()->first();
                if ($old) {
                    Storage::disk('public_uploads')->delete($old->image_path);
                    $old->delete();
                }
                $file = $request->file('image');
                $ext  = $file->getClientOriginalExtension() ?: 'jpg';
                $path = $file->storeAs('products', Str::random(40) . '.' . $ext, 'public_uploads');
                $product->images()->create([
                    'image_path' => $path,
                    'is_primary'  => true,
                ]);
            }

             DB::commit();
             $returnUrl = $request->input('return_url');
             if ($returnUrl && str_starts_with($returnUrl, url('/'))) {
                 return redirect($returnUrl)->with('success', 'Product updated successfully.');
             }
             return redirect()->route('products.index')->with('success', 'Product updated successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Failed to update: ' . $e->getMessage()]);
        }
    }

    public function export(): StreamedResponse
    {
        $products = Product::withoutGlobalScope('subscriber')
            ->with([
                'category' => fn($q) => $q->withoutGlobalScopes(),
                'brand'    => fn($q) => $q->withoutGlobalScopes(),
                'price'    => fn($q) => $q->withoutGlobalScopes(),
            ])
            ->latest()
            ->get();

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="products_' . date('Ymd_His') . '.csv"',
        ];

        return response()->stream(function () use ($products) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'sku', 'name', 'description', 'product_type',
                'category', 'brand', 'barcode', 'weight', 'pack_size_qty',
                'status', 'inventory_control',
                'min_stock_qty', 'reorder_qty', 'stock_qty_pcs', 'stock_qty_box',
                'cost_price_item', 'cost_price_box',
                'sales_price_item', 'sales_price_box',
            ]);

            foreach ($products as $p) {
                fputcsv($handle, [
                    $p->sku,
                    $p->name,
                    $p->description,
                    $p->product_type,
                    $p->category->name ?? '',
                    $p->brand->name ?? '',
                    $p->barcode,
                    $p->weight,
                    $p->pack_size_qty,
                    $p->status ? 1 : 0,
                    $p->inventory_control ? 1 : 0,
                    $p->min_stock_qty,
                    $p->reorder_qty,
                    $p->stock_qty_pcs,
                    $p->stock_qty_box,
                    $p->price?->cost_price_item ?? 0,
                    $p->price?->cost_price_box ?? 0,
                    $p->price?->sales_price_item ?? 0,
                    $p->price?->sales_price_box ?? 0,
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }

    public function importTemplate(): StreamedResponse
    {
        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="products_import_template.csv"',
        ];

        return response()->stream(function () {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'sku', 'name', 'description', 'product_type',
                'category', 'brand', 'barcode', 'weight', 'pack_size_qty',
                'status', 'inventory_control',
                'min_stock_qty', 'reorder_qty', 'stock_qty_pcs', 'stock_qty_box',
                'cost_price_item', 'cost_price_box',
                'sales_price_item', 'sales_price_box',
            ]);

            // Sample row
            fputcsv($handle, [
                'SKU-000001', 'Sample Product', 'A sample product description', 'STANDARD',
                'General', 'Brand A', 'WT00000001', '0.5', '12',
                '1', '1',
                '10', '5', '0', '0',
                '10.00', '100.00',
                '15.00', '150.00',
            ]);

            fclose($handle);
        }, 200, $headers);
    }

    public function import(Request $request)
    {
        $this->checkStaffPermission('products', 'create');

        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:5120',
        ]);

        $handle   = fopen($request->file('csv_file')->getRealPath(), 'r');
        $headers  = fgetcsv($handle); // skip header row
        $imported = 0;
        $skipped  = [];
        $rowNum   = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $rowNum++;

            if (count($row) < 4) {
                $skipped[] = "Row {$rowNum}: too few columns.";
                continue;
            }

            $data = array_combine([
                'sku', 'name', 'description', 'product_type',
                'category', 'brand', 'barcode', 'weight', 'pack_size_qty',
                'status', 'inventory_control',
                'min_stock_qty', 'reorder_qty', 'stock_qty_pcs', 'stock_qty_box',
                'cost_price_item', 'cost_price_box',
                'sales_price_item', 'sales_price_box',
            ], array_pad($row, 19, null));

            if (empty(trim($data['sku'] ?? ''))) {
                $skipped[] = "Row {$rowNum}: SKU is required.";
                continue;
            }
            if (empty(trim($data['name'] ?? ''))) {
                $skipped[] = "Row {$rowNum}: name is required.";
                continue;
            }
            if (!in_array($data['product_type'] ?? '', ['STANDARD', 'VARIANT', 'COMPOSITE'])) {
                $skipped[] = "Row {$rowNum}: product_type must be STANDARD, VARIANT, or COMPOSITE.";
                continue;
            }
            if (Product::withoutGlobalScope('subscriber')->where('sku', trim($data['sku']))->exists()) {
                $skipped[] = "Row {$rowNum}: SKU '{$data['sku']}' already exists.";
                continue;
            }

            // Resolve category and brand by name
            $categoryId = null;
            if (!empty(trim($data['category'] ?? ''))) {
                $cat = ItemCategory::withoutGlobalScope('subscriber')
                    ->where('name', trim($data['category']))->first();
                $categoryId = $cat?->id;
            }

            $brandId = null;
            if (!empty(trim($data['brand'] ?? ''))) {
                $brand = ProductBrand::withoutGlobalScope('subscriber')
                    ->where('name', trim($data['brand']))->first();
                $brandId = $brand?->id;
            }

            DB::beginTransaction();
            try {
                $product = Product::create([
                    'sku'               => trim($data['sku']),
                    'name'              => trim($data['name']),
                    'description'       => trim($data['description'] ?? '') ?: null,
                    'product_type'      => $data['product_type'],
                    'category_id'       => $categoryId,
                    'brand_id'          => $brandId,
                    'barcode'           => trim($data['barcode'] ?? '') ?: null,
                    'weight'            => is_numeric($data['weight'] ?? null) ? $data['weight'] : null,
                    'pack_size_qty'     => is_numeric($data['pack_size_qty'] ?? null) ? $data['pack_size_qty'] : 1,
                    'status'            => in_array(trim($data['status'] ?? '1'), ['1', 'true', 'yes', 'active'], true),
                    'inventory_control' => in_array(trim($data['inventory_control'] ?? '1'), ['1', 'true', 'yes'], true),
                    'min_stock_qty'     => is_numeric($data['min_stock_qty'] ?? null) ? (int)$data['min_stock_qty'] : 0,
                    'reorder_qty'       => is_numeric($data['reorder_qty'] ?? null) ? (int)$data['reorder_qty'] : 0,
                    'stock_qty_pcs'     => is_numeric($data['stock_qty_pcs'] ?? null) ? $data['stock_qty_pcs'] : 0,
                    'stock_qty_box'     => is_numeric($data['stock_qty_box'] ?? null) ? $data['stock_qty_box'] : 0,
                ]);

                if (empty($product->barcode)) {
                    $product->update(['barcode' => 'WT' . str_pad($product->id, 8, '0', STR_PAD_LEFT)]);
                }

                $product->price()->create([
                    'cost_price_item'  => is_numeric($data['cost_price_item'] ?? null) ? $data['cost_price_item'] : 0,
                    'cost_price_box'   => is_numeric($data['cost_price_box'] ?? null) ? $data['cost_price_box'] : 0,
                    'sales_price_item' => is_numeric($data['sales_price_item'] ?? null) ? $data['sales_price_item'] : 0,
                    'sales_price_box'  => is_numeric($data['sales_price_box'] ?? null) ? $data['sales_price_box'] : 0,
                    'margin_type'      => 'percentage',
                    'margin'           => 0,
                    'tax_rate'         => 0,
                ]);

                $product->inventory()->create([
                    'have_batch'    => false,
                    'have_serial'   => false,
                    'stock_in_hand' => 0,
                ]);

                DB::commit();
                $imported++;
            } catch (\Exception $e) {
                DB::rollBack();
                $skipped[] = "Row {$rowNum}: " . $e->getMessage();
            }
        }

        fclose($handle);

        $message = "{$imported} product(s) imported successfully.";
        if ($skipped) {
            $message .= ' Skipped: ' . implode(' | ', $skipped);
            return redirect()->route('products.index')->with('error', $message);
        }

        return redirect()->route('products.index')->with('success', $message);
    }

    public function toggleStatus($id)
    {
        $this->checkStaffPermission('products', 'edit');
        $product = Product::withoutGlobalScope('subscriber')->findOrFail($id);
        $newStatus = !$product->status;
        $product->update(['status' => $newStatus]);

        // Sync all aliases to match the product's new status
        ProductAlias::withoutGlobalScope('subscriber')
            ->where('product_id', $product->id)
            ->update(['status' => $newStatus]);

        $label = $newStatus ? 'activated' : 'deactivated';
        return redirect()->back()->with('success', 'Product "' . $product->name . '" ' . $label . ' successfully.');
    }

    public function destroy($id)
    {
        $this->checkStaffPermission('products', 'delete');
        $product = Product::withoutGlobalScope('subscriber')->findOrFail($id);

        $hasTransactions =
            \App\Models\SalesInvoiceItem::where('product_id', $product->id)->exists()   ||
            \App\Models\PurchaseOrderItem::where('product_id', $product->id)->exists()  ||
            \App\Models\QuotationItem::where('product_id', $product->id)->exists()      ||
            \App\Models\SupplierInvoiceItem::where('product_id', $product->id)->exists() ||
            \DB::table('delivery_order_items')->where('product_id', $product->id)->exists();

        if ($hasTransactions) {
            return redirect()->route('products.index')
                ->with('error', 'Cannot delete "' . $product->name . '" — it is referenced by existing transactions. Deactivate it instead.');
        }

        try {
            $product->delete();
            return redirect()->route('products.index')->with('success', 'Product "' . $product->name . '" deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->route('products.index')
                ->with('error', 'Could not delete "' . $product->name . '": ' . $e->getMessage());
        }
    }

    public function destroyImage(Product $product, ProductImage $image)
    {
        $this->checkStaffPermission('products', 'edit');

        if ($image->product_id !== $product->id) {
            abort(403);
        }

        Storage::disk('public_uploads')->delete($image->image_path);
        $wasPrimary = $image->is_primary;
        $image->delete();

        if ($wasPrimary) {
            $next = $product->images()->first();
            $next?->update(['is_primary' => true]);
        }

        return response()->json(['success' => true]);
    }

    public function setPrimaryImage(Product $product, ProductImage $image)
    {
        $this->checkStaffPermission('products', 'edit');

        if ($image->product_id !== $product->id) {
            abort(403);
        }

        $product->images()->update(['is_primary' => false]);
        $image->update(['is_primary' => true]);

        return response()->json(['success' => true]);
    }

    public function storeAlias(Request $request, $productId)
    {
        $this->checkStaffPermission('products', 'edit');
        $product = Product::withoutGlobalScope('subscriber')->findOrFail($productId);

        $request->validate([
            'alias_name' => 'required|string|max:255',
            'alias_sku'  => 'nullable|string|max:255|unique:product_aliases,alias_sku',
            'description' => 'nullable|string|max:1000',
        ]);

        if ($product->aliases()->withoutGlobalScope('subscriber')->where('alias_name', $request->alias_name)->exists()) {
            return redirect()->route('products.edit', ['product' => $productId, 'tab' => 'aliases', 'return_url' => $request->input('return_url')])
                ->withErrors(['alias_name' => 'This alias name already exists for this product.'])->withInput();
        }

        $product->aliases()->create([
            'alias_name'  => $request->alias_name,
            'alias_sku'   => $request->alias_sku ?: null,
            'description' => $request->description ?: null,
            'status'      => $request->has('alias_status'),
        ]);

        $returnUrl = $request->input('return_url');
        return redirect()->route('products.edit', ['product' => $productId, 'tab' => 'aliases', 'return_url' => $returnUrl])
            ->with('success', 'Alias "' . $request->alias_name . '" added successfully.');
    }

    public function updateAlias(Request $request, $productId, $aliasId)
    {
        $this->checkStaffPermission('products', 'edit');
        $alias = ProductAlias::withoutGlobalScope('subscriber')
            ->where('product_id', $productId)->findOrFail($aliasId);

        $request->validate([
            'alias_name' => 'required|string|max:255',
            'alias_sku'  => 'nullable|string|max:255|unique:product_aliases,alias_sku,' . $aliasId,
            'description' => 'nullable|string|max:1000',
        ]);

        $duplicate = ProductAlias::withoutGlobalScope('subscriber')
            ->where('product_id', $productId)
            ->where('alias_name', $request->alias_name)
            ->where('id', '!=', $aliasId)
            ->exists();

        if ($duplicate) {
            return back()->withErrors(['alias_name' => 'This alias name already exists for this product.'])->withInput();
        }

        $alias->update([
            'alias_name'  => $request->alias_name,
            'alias_sku'   => $request->alias_sku ?: null,
            'description' => $request->description ?: null,
            'status'      => $request->has('alias_status'),
        ]);

        $returnUrl = $request->input('return_url');
        return redirect()->route('products.edit', ['product' => $productId, 'tab' => 'aliases', 'return_url' => $returnUrl])
            ->with('success', 'Alias updated successfully.');
    }

    public function destroyAlias(Request $request, $productId, $aliasId)
    {
        $this->checkStaffPermission('products', 'edit');
        $alias = ProductAlias::withoutGlobalScope('subscriber')
            ->where('product_id', $productId)->findOrFail($aliasId);
        $alias->delete();

        $returnUrl = $request->input('return_url');
        return redirect()->route('products.edit', ['product' => $productId, 'tab' => 'aliases', 'return_url' => $returnUrl])
            ->with('success', 'Alias deleted successfully.');
    }
}
