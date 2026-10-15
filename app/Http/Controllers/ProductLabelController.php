<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ProductLabel;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class ProductLabelController extends Controller
{
    public function index()
    {
        $labels = ProductLabel::latest()->paginate(10);
        return view('product_labels.index', compact('labels'));
    }

    public function create()
    {
        return view('product_labels.create');
    }

    public function store(Request $request)
    {
        $this->checkStaffPermission('product-labels', 'create');
        $request->validate([
            'label_name' => 'required|string|unique:product_labels,label_name',
            'height' => 'required|numeric|min:0.1',
            'width' => 'required|numeric|min:0.1',
            'unit' => 'required|in:mm,cm,inch',
        ]);

        $data = $request->all();
        // Checkboxes
        $toggles = ['status', 'expiry_required', 'show_product_name', 'show_sku', 'show_barcode', 'show_batch_no', 'show_mrp', 'show_logo'];
        foreach ($toggles as $toggle) {
            $data[$toggle] = $request->has($toggle);
        }

        ProductLabel::create($data);

        return redirect()->route('product-labels.index')->with('success', 'Label created successfully.');
    }

    public function edit(ProductLabel $productLabel)
    {
        return view('product_labels.edit', compact('productLabel'));
    }

    public function update(Request $request, ProductLabel $productLabel)
    {
        $this->checkStaffPermission('product-labels', 'edit');
        $request->validate([
            'label_name' => 'required|string|unique:product_labels,label_name,' . $productLabel->id,
            'height' => 'required|numeric|min:0.1',
            'width' => 'required|numeric|min:0.1',
            'unit' => 'required|in:mm,cm,inch',
        ]);

        $data = $request->all();
        // Checkboxes
        $toggles = ['status', 'expiry_required', 'show_product_name', 'show_sku', 'show_barcode', 'show_batch_no', 'show_mrp', 'show_logo'];
        foreach ($toggles as $toggle) {
             // If toggle is not in request, it's false. This is handled by boolean casting in update if we pass explicit false
             // But checkbox handling: if unchecked, it's missing from request.
             $data[$toggle] = $request->has($toggle);
        }

        $productLabel->update($data);

        return redirect()->route('product-labels.index')->with('success', 'Label updated successfully.');
    }

    public function destroy(ProductLabel $productLabel)
    {
        $this->checkStaffPermission('product-labels', 'delete');
        $productLabel->delete();
        return redirect()->route('product-labels.index')->with('success', 'Label deleted successfully.');
    }
}
