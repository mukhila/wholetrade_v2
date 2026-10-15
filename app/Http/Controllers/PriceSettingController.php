<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Associate;
use App\Models\AssociateGroup;
use App\Models\ItemCategory;
use App\Models\ProductBrand;
use App\Models\AssociatePrice;
use App\Models\GroupPrice;
use App\Models\PromotionPrice;
use App\Models\ProductPrice;
use App\Models\PriceAuditLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class PriceSettingController extends Controller
{
    // Render the Blade View with Master Data
    public function index()
    {
        $categories = ItemCategory::withoutGlobalScope('subscriber')->where('status', true)->get(['id', 'name']);
        $brands = ProductBrand::withoutGlobalScope('subscriber')->where('status', true)->get(['id', 'name']);
        $associateGroups = AssociateGroup::withoutGlobalScope('subscriber')->where('status', true)->get(['id', 'name']);
        $associates = Associate::withoutGlobalScope('subscriber')->where('status', true)->where('type', 'Customer')->get(['id', 'name', 'code']);

        return view('price_settings.index', compact('categories', 'brands', 'associateGroups', 'associates'));
    }

    // API: Get initial data (Deprecated for Blade view but kept for reference if needed)
    public function getMasterData()
    {
        return response()->json([
            'categories' => ItemCategory::withoutGlobalScope('subscriber')->where('status', true)->get(['id', 'name']),
            'brands' => ProductBrand::withoutGlobalScope('subscriber')->where('status', true)->get(['id', 'name']),
            'associateGroups' => AssociateGroup::withoutGlobalScope('subscriber')->where('status', true)->get(['id', 'name']),
            'associates' => Associate::withoutGlobalScope('subscriber')->where('status', true)->where('type', 'Customer')->get(['id', 'name', 'code']),
        ]);
    }

    // API: Fetch products with prices based on filters & type
    public function getPrices(Request $request)
    {
        $priceType = $request->query('priceType', 'STANDARD'); // STANDARD, ASSOCIATE, GROUP, PROMOTION
        $categoryId = $request->query('categoryId');
        $brandId = $request->query('brandId');

        // Context IDs
        $associateId = $request->query('associateId');
        $groupId = $request->query('groupId');

        // Use withoutGlobalScope to bypass subscriber filtering and show all products
       /* $query = Product::withoutGlobalScope('subscriber')
            ->with(['price', 'category:id,name', 'brand:id,name'])
            ->where('status', true);*/

        $query = Product::withoutGlobalScope('subscriber')
            ->with([
                'price' => function ($q) {
                    $q->withoutGlobalScope('subscriber');
                },
                'category:id,name',
                'brand:id,name'
            ])
            ->where('status', true);


        if ($categoryId) $query->where('category_id', $categoryId);
        if ($brandId) $query->where('brand_id', $brandId);

        // We need to fetch specific prices based on type
        // However, we always fetch Standard Prices as "Old" reference if specific price doesn't exist

        // Fetch with pagination
        $products = $query->paginate(50);

        // Get product IDs for batch fetching (fixes N+1 query problem)
        $productIds = $products->getCollection()->pluck('id')->toArray();

        // Pre-fetch Associate/Group prices in bulk (single query each instead of N queries)
        $associatePrices = collect();
        $groupPrices = collect();

        if ($priceType === 'ASSOCIATE' && $associateId) {
            $associatePrices = AssociatePrice::withoutGlobalScope('subscriber')
                ->where('associate_id', $associateId)
                ->whereIn('product_id', $productIds)
                ->get()
                ->keyBy('product_id');
        } elseif ($priceType === 'GROUP' && $groupId) {
            $groupPrices = GroupPrice::withoutGlobalScope('subscriber')
                ->where('group_id', $groupId)
                ->whereIn('product_id', $productIds)
                ->get()
                ->keyBy('product_id');
        }

        // Transform the collection within the paginator
        $products->getCollection()->transform(function ($product) use ($priceType, $associateId, $groupId, $associatePrices, $groupPrices) {

            // Standard Prices (Base) - Use nullsafe operator to handle products without price records
            $basePrices = [
                'unit_price'      => $product->price?->sales_price_item ?? 0,
                'box_price'       => $product->price?->sales_price_box ?? 0,
                'price'           => $product->price?->sales_price_item ?? 0,
                'pos_price'       => $product->price?->retail_price_item ?? 0,
                'cost_price_item' => $product->price?->cost_price_item ?? 0,
                'cost_price_box'  => $product->price?->cost_price_box ?? 0,
                'tax_rate'        => $product->price?->tax_rate ?? 0, // BUG-019 FIX: include tax_rate
            ];

            // Determine "Old" and "New" values based on Type
            $row = [
                'productId' => $product->id,
                'itemCode' => $product->sku,
                'name' => $product->name,
                'category' => $product->category->name ?? '',
                'brand' => $product->brand->name ?? '',

                // Old Values (Displayed as read-only reference)
                'oldPrices' => array_merge($basePrices, []),

                // New Values (Editable) - Default to Old values initially
                'newPrices' => array_merge($basePrices, []),

                // Extra fields for Promotion
                'promotion' => [
                    'minQty' => 1,
                    'maxQty' => null,
                    'startDate' => null,
                    'endDate' => null,
                ]
            ];

            if ($priceType === 'ASSOCIATE' && $associateId) {
                // Use pre-fetched associate price (no additional query)
                $assocPrice = $associatePrices->get($product->id);
                if ($assocPrice) {
                    $row['oldPrices'] = [
                         'unit_price' => $assocPrice->unit_price,
                         'box_price' => $assocPrice->box_price,
                         'price' => $assocPrice->price,
                         'pos_price' => $assocPrice->pos_price,
                         'cost_price_item' => $product->price?->cost_price_item ?? 0,
                         'cost_price_box' => $product->price?->cost_price_box ?? 0,
                    ];
                    $row['newPrices'] = $row['oldPrices'];
                }
            } elseif ($priceType === 'GROUP' && $groupId) {
                // Use pre-fetched group price (no additional query)
                $groupPrice = $groupPrices->get($product->id);
                if ($groupPrice) {
                    $row['oldPrices'] = [
                         'unit_price' => $groupPrice->unit_price,
                         'box_price' => $groupPrice->box_price,
                         'price' => $groupPrice->price,
                         'pos_price' => $groupPrice->pos_price,
                         'cost_price_item' => $product->price?->cost_price_item ?? 0,
                         'cost_price_box' => $product->price?->cost_price_box ?? 0,
                    ];
                    $row['newPrices'] = $row['oldPrices'];
                }
            } elseif ($priceType === 'PROMOTION') {
                // Fetch active or latest promotion? The prompt implies creating new promotions.
                // So maybe we don't prefill with *existing* promotion unless we are editing one.
                // For simplicity, let's assume we are defining NEW promotions, originating from Standard Price.
            }

            return $row;
        });

        return response()->json($products);
    }

    // API: Save Bulk Prices
    public function savePrices(Request $request)
    {
        $this->checkStaffPermission('price-settings', 'edit');
        $request->validate([
            'priceType' => 'required|in:STANDARD,ASSOCIATE,GROUP,PROMOTION',
            'prices' => 'required|array',
            'associateId' => 'required_if:priceType,ASSOCIATE',
            'groupId' => 'required_if:priceType,GROUP',
        ]);

        $priceType = $request->priceType;
        $prices = $request->prices;
        $userId = Auth::id(); // BUG-012 FIX: removed ?? 1 fallback; routes are auth-protected

        DB::beginTransaction();
        try {
            foreach ($prices as $p) {
                $productId = $p['productId'];
                $new = $p['newPrices']; // array of unit_price, etc.
                $old = $p['oldPrices']; 
                
                // Skip if no change (except promotion which might be new)
                // But specifically for Standard/Associate/Group
                if ($priceType !== 'PROMOTION') {
                     // Check if modified? Frontend sends everything or only modified? 
                     // Assuming frontend sends only modified, or we check here. 
                     // Let's rely on frontend sending what needs saving.
                }

                if ($priceType === 'STANDARD') {
                    // BUG-019 FIX: persist tax_rate alongside sales prices
                    ProductPrice::updateOrCreate(
                        ['product_id' => $productId],
                        [
                            'sales_price_item'  => $new['unit_price'],
                            'sales_price_box'   => $new['box_price'],
                            'retail_price_item' => $new['pos_price'],
                            'tax_rate'          => $new['tax_rate'] ?? 0,
                        ]
                    );

                    $this->logAudit($priceType, null, $productId, 'unit_price', $old['unit_price'], $new['unit_price'], $userId);
                    
                } elseif ($priceType === 'ASSOCIATE') {
                    AssociatePrice::updateOrCreate(
                        ['associate_id' => $request->associateId, 'product_id' => $productId],
                        [
                            'unit_price' => $new['unit_price'],
                            'box_price' => $new['box_price'],
                            'price' => $new['price'], 
                            'pos_price' => $new['pos_price'],
                        ]
                    );
                    $this->logAudit($priceType, $request->associateId, $productId, 'unit_price', $old['unit_price'], $new['unit_price'], $userId);

                } elseif ($priceType === 'GROUP') {
                    GroupPrice::updateOrCreate(
                        ['group_id' => $request->groupId, 'product_id' => $productId],
                        [
                            'unit_price' => $new['unit_price'],
                            'box_price' => $new['box_price'],
                            'price' => $new['price'],
                            'pos_price' => $new['pos_price'],
                        ]
                    );
                    $this->logAudit($priceType, $request->groupId, $productId, 'unit_price', $old['unit_price'], $new['unit_price'], $userId);

                } elseif ($priceType === 'PROMOTION') {
                    $start = $p['promotion']['startDate'];
                    $end   = $p['promotion']['endDate'];

                    if ($start && $end) {
                        // BUG-002 FIX: reject overlapping promotions instead of silently creating them.
                        // Full overlap check: new range intersects an existing one in any direction.
                        $overlap = PromotionPrice::where('product_id', $productId)
                            ->where('status', true)
                            ->where(function($q) use ($start, $end) {
                                $q->whereBetween('start_date', [$start, $end])
                                  ->orWhereBetween('end_date', [$start, $end])
                                  ->orWhere(function($q2) use ($start, $end) {
                                      // Existing range fully wraps the new one
                                      $q2->where('start_date', '<=', $start)
                                         ->where('end_date', '>=', $end);
                                  });
                            })->exists();

                        if ($overlap) {
                            DB::rollBack();
                            return response()->json([
                                'error' => "Product ID {$productId} already has an active promotion overlapping {$start} – {$end}. No prices were saved.",
                            ], 422);
                        }

                        PromotionPrice::create([
                            'product_id' => $productId,
                            'min_qty'    => $p['promotion']['minQty'],
                            'max_qty'    => $p['promotion']['maxQty'],
                            'unit_price' => $p['promotion']['unit'],
                            'box_price'  => $p['promotion']['box'],
                            'pos_price'  => $p['promotion']['pos'],
                            'start_date' => $start,
                            'end_date'   => $end,
                            'status'     => true,
                        ]);
                    }
                }
            }
            DB::commit();
            return response()->json(['message' => 'Prices saved successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    private function logAudit($type, $refId, $prodId, $field, $old, $new, $userId) {
        if ($old != $new) {
            PriceAuditLog::create([
                'price_type' => $type,
                'reference_id' => $refId,
                'product_id' => $prodId,
                'field_name' => $field,
                'old_value' => $old,
                'new_value' => $new,
                'changed_by' => $userId,
            ]);
        }
    }
}
