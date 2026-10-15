<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\PriceUpdateLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class PriceUpdateController extends Controller
{
    public function index()
    {
        return view('price_updates.index');
    }

    public function getProducts(Request $request)
    {
        $products = Product::with(['price', 'category:id,name', 'brand:id,name'])
            ->where('status', true)
            ->get()
            ->map(function ($product) {
                return [
                    'productId' => $product->id,
                    'itemCode' => $product->sku,
                    'name' => $product->name,
                    'category' => $product->category->name ?? '',
                    'brand' => $product->brand->name ?? '',
                    'oldPrices' => [
                        'unit_price' => $product->price?->sales_price_item ?? 0,
                        'box_price' => $product->price?->sales_price_box ?? 0,
                        'pos_price' => $product->price?->retail_price_item ?? 0,
                    ],
                    'newPrices' => [
                        'unit_price' => $product->price?->sales_price_item ?? 0,
                        'box_price' => $product->price?->sales_price_box ?? 0,
                        'pos_price' => $product->price?->retail_price_item ?? 0,
                    ]
                ];
            });

        return response()->json($products);
    }

    public function updatePrices(Request $request)
    {
        $this->checkStaffPermission('price-updates', 'edit');
        $request->validate([
            'prices' => 'required|array',
            'prices.*.productId' => 'required|exists:products,id',
            'prices.*.newPrices.unit_price' => 'required|numeric|min:0',
            'prices.*.newPrices.box_price' => 'required|numeric|min:0',
            'prices.*.newPrices.pos_price' => 'required|numeric|min:0',
        ]);

        $userId = Auth::id() ?? 1;

        DB::beginTransaction();
        try {
            foreach ($request->prices as $p) {
                $productPrice = ProductPrice::firstOrNew(['product_id' => $p['productId']]);
                
                // Capture old values for log
                $oldUnit = $productPrice->sales_price_item ?? 0;
                $oldBox = $productPrice->sales_price_box ?? 0;
                $oldPos = $productPrice->retail_price_item ?? 0;

                $newUnit = $p['newPrices']['unit_price'];
                $newBox = $p['newPrices']['box_price'];
                $newPos = $p['newPrices']['pos_price'];

                // Update
                $productPrice->sales_price_item = $newUnit;
                $productPrice->sales_price_box = $newBox;
                $productPrice->retail_price_item = $newPos;
                $productPrice->save();

                // Log
                PriceUpdateLog::create([
                    'product_id' => $p['productId'],
                    'old_unit_price' => $oldUnit,
                    'old_box_price' => $oldBox,
                    'old_pos_price' => $oldPos,
                    'new_unit_price' => $newUnit,
                    'new_box_price' => $newBox,
                    'new_pos_price' => $newPos,
                    'updated_by' => $userId,
                    'updated_at' => now(),
                ]);
            }
            DB::commit();
            return response()->json(['message' => 'Price updates saved successfully.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
