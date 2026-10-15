<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\ItemCategory;
use App\Models\ProductBrand;
use App\Models\CostPriceUpdateLog;

class CostPriceUpdateController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with([
                'category' => fn($q) => $q->withoutGlobalScopes(),
                'brand'    => fn($q) => $q->withoutGlobalScopes(),
                'price'    => fn($q) => $q->withoutGlobalScopes(),
            ])
            ->withoutGlobalScope('subscriber')
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

        return view('cost-price-updates.index', compact('products', 'categories', 'brands'));
    }

    public function update(Request $request)
    {
        $this->checkStaffPermission('cost-price-updates', 'edit');
        $request->validate([
            'prices'                   => 'required|array',
            'prices.*.cost_price_item' => 'required|numeric|min:0',
        ]);

        $user        = Auth::user();
        $subscriberId = $user->subscriber_id;
        $updatedCount = 0;

        DB::beginTransaction();
        try {
            foreach ($request->prices as $productId => $values) {
                // Ownership check — product must belong to this subscriber
                $product = Product::withoutGlobalScope('subscriber')
                    ->where('id', $productId)
                    ->where('subscriber_id', $subscriberId)
                    ->first();

                if (!$product) {
                    continue;
                }

                $newCostItem = round((float) $values['cost_price_item'], 4);

                $price = ProductPrice::withoutGlobalScopes()
                    ->firstOrNew(['product_id' => $productId]);

                // Skip rows where nothing actually changed
                $oldCostItem = round((float) ($price->cost_price_item ?? 0), 4);

                if ($oldCostItem === $newCostItem) {
                    continue;
                }

                // Ensure subscriber_id is set for new price records
                if (!$price->exists) {
                    $price->subscriber_id = $subscriberId;
                    $price->product_id    = $productId;
                }

                $price->cost_price_item = $newCostItem;
                $price->save();

                CostPriceUpdateLog::create([
                    'product_id'    => $productId,
                    'old_cost_item' => $oldCostItem,
                    'old_cost_box'  => $price->cost_price_box ?? 0,
                    'new_cost_item' => $newCostItem,
                    'new_cost_box'  => $price->cost_price_box ?? 0,
                    'updated_by'    => $user->id,
                    'updated_at'    => now(),
                ]);

                $updatedCount++;
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->withErrors(['error' => 'Update failed: ' . $e->getMessage()]);
        }

        return redirect()
            ->route('cost-price-updates.index', request()->only(['category_id', 'brand_id', 'search', 'page']))
            ->with('success', $updatedCount . ' product(s) cost price updated successfully.');
    }
}
