<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;

class CartController extends Controller
{
    public function index()
    {
        $cart = session()->get('cart', []);
        return view('cart.index', compact('cart'));
    }

    public function addToCart(Request $request, $id)
    {
        $product = Product::withoutGlobalScope('subscriber')->with('price')->findOrFail($id);
        $cart = session()->get('cart', []);

        // Inputs from React or Blade form
        $itemQty = $request->input('item_qty', 0);
        $boxQty = $request->input('box_qty', 0);

        // Validate at least one quantity
        if ($itemQty <= 0 && $boxQty <= 0) {
            // Default to 1 item if nothing specified (for simple buttons)
            $itemQty = 1;
        }

        // Prices
        $unitPrice = $product->price?->sales_price_item ?? 0;
        $boxPrice = $product->price?->sales_price_box ?? 0;
        
        // If box price is 0/missing, maybe calculate from unit price * pack_size? 
        // Spec says: "Box price must exist if box qty > 0". We will assume it exists or fallback.
        if ($boxQty > 0 && $boxPrice <= 0) {
            $boxPrice = $unitPrice * ($product->pack_size_qty ?? 1); 
        }

        if(isset($cart[$id])) {
            $cart[$id]['item_qty'] += $itemQty;
            $cart[$id]['box_qty'] += $boxQty;
        } else {
            $cart[$id] = [
                "id" => $product->id,
                "name" => $product->name,
                "item_qty" => $itemQty,
                "box_qty" => $boxQty,
                "unit_price" => $unitPrice,
                "box_price" => $boxPrice,
                "sku" => $product->sku,
                "image" => $product->image,
                "pack_size_qty" => $product->pack_size_qty ?? 1
            ];
        }

        session()->put('cart', $cart);
        
        if($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Product added to cart!', 'cartCount' => count($cart)]);
        }
        return redirect()->back()->with('success', 'Product added to cart!');
    }

    public function update(Request $request)
    {
        if($request->id) {
            $cart = session()->get('cart');
            
            // Allow updating both
            if(isset($request->item_qty)) {
                $cart[$request->id]['item_qty'] = $request->item_qty;
            }
            if(isset($request->box_qty)) {
                $cart[$request->id]['box_qty'] = $request->box_qty;
            }
            
            session()->put('cart', $cart);

            // Calculate new totals
            $item = $cart[$request->id];
            $lineTotal = ($item['item_qty'] * $item['unit_price']) + ($item['box_qty'] * $item['box_price']);
            
            $grandTotal = 0;
            foreach($cart as $c) {
                 $grandTotal += ($c['item_qty'] * $c['unit_price']) + ($c['box_qty'] * $c['box_price']);
            }

            return response()->json([
                'success' => true, 
                'line_total' => number_format($lineTotal, 2), 
                'grand_total' => number_format($grandTotal, 2)
            ]);
        }
    }

    public function remove(Request $request)
    {
        if($request->id) {
            $cart = session()->get('cart');
            if(isset($cart[$request->id])) {
                unset($cart[$request->id]);
                session()->put('cart', $cart);
            }
            return response()->json(['success' => true]);
        }
    }

    public function clear()
    {
        session()->forget('cart');
        return redirect()->route('cart.index');
    }
}
