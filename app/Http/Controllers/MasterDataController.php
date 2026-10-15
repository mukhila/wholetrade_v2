<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class MasterDataController extends Controller
{
    private $models = [
        'currencies' => \App\Models\Currency::class,
        'timezones' => \App\Models\Timezone::class,
        'taxes' => \App\Models\Tax::class,
        'terms' => \App\Models\Term::class,
        'incoterms' => \App\Models\Incoterm::class,
        'paymodes' => \App\Models\Paymode::class,
        'banks' => \App\Models\Bank::class,
        'product-brands' => \App\Models\ProductBrand::class,
        'item-categories' => \App\Models\ItemCategory::class,
        'uoms' => \App\Models\UnitOfMeasure::class,
        'colors' => \App\Models\Color::class,
        'sizes' => \App\Models\Size::class,
    ];

    public function index($type)
    {
        if (!array_key_exists($type, $this->models)) {
            abort(404);
        }

        $model = $this->models[$type];
        $data = $model::all();

        return view('master_data.index', compact('data', 'type'));
    }

    public function create($type)
    {
         if (!array_key_exists($type, $this->models)) {
            abort(404);
        }
        return view('master_data.create', compact('type'));
    }

    public function store(Request $request, $type)
    {
        $this->checkStaffPermission('master-data', 'create');
        if (!array_key_exists($type, $this->models)) {
            abort(404);
        }

        $model = $this->models[$type];
        
        // Dynamic validation
        $rules = ['name' => 'required|string|max:255'];
        if ($type === 'currencies') {
            $rules['code'] = 'required|string|max:3|unique:currencies,code';
        }
        if ($type === 'timezones') {
             $rules = ['timezone' => 'required|string|max:255'];
        }
        if ($type === 'taxes') {
             $rules['percentage'] = 'required|numeric';
             $rules['type'] = 'required|string';
        }
        if ($type === 'terms' || $type === 'incoterms') {
             $rules['no_of_days'] = 'required|integer';
        }

        $request->validate($rules);

        // BUG-017 FIX: exclude _token, _method, and tenant-isolation fields from mass assignment
        // to prevent a malicious POST from writing into another tenant's namespace.
        $data = $request->except(['_token', '_method', 'subscriber_id']);

        if ($request->has('is_default')) {
            if ($request->is_default) {
                $model::query()->update(['is_default' => false]);
            }
        }
        if ($request->has('status')) {
            $data['status'] = (bool) $request->status;
        }

        $model::create($data);

        return redirect()->route('master-data.index', $type)->with('success', 'Record created successfully.');
    }

    public function edit($type, $id)
    {
         if (!array_key_exists($type, $this->models)) {
            abort(404);
        }
        $model = $this->models[$type];
        $item = $model::findOrFail($id);
        
        return view('master_data.edit', compact('item', 'type'));
    }

    public function update(Request $request, $type, $id)
    {
        $this->checkStaffPermission('master-data', 'edit');
        if (!array_key_exists($type, $this->models)) {
            abort(404);
        }

        $model = $this->models[$type];
        $item = $model::findOrFail($id);

        // Prevent tenants from editing global data
        if ($item->subscriber_id === null && !auth()->user()->isSuperAdmin()) {
            abort(403, 'You cannot edit global master data.');
        }

         // Dynamic validation
        $rules = ['name' => 'required|string|max:255'];
        if ($type === 'currencies') {
            $rules['code'] = 'required|string|max:3|unique:currencies,code,' . $id;
        }
          if ($type === 'timezones') {
             $rules = ['timezone' => 'required|string|max:255'];
        }
        if ($type === 'taxes') {
             $rules['percentage'] = 'required|numeric';
             $rules['type'] = 'required|string';
        }
        if ($type === 'terms' || $type === 'incoterms') {
             $rules['no_of_days'] = 'required|integer';
        }

        $request->validate($rules);

        // BUG-017 FIX: strip sensitive fields before update
        $data = $request->except(['_token', '_method', 'subscriber_id']);

        if ($request->has('is_default')) {
            if ($request->is_default) {
                $model::query()->where('id', '!=', $id)->update(['is_default' => false]);
            }
        }
        if ($request->has('status')) {
            $data['status'] = (bool) $request->status;
        } else {
            if (in_array('status', $item->getFillable())) {
                $data['status'] = false;
            }
        }

        $item->update($data);

        return redirect()->route('master-data.index', $type)->with('success', 'Record updated successfully.');
    }

    public function destroy($type, $id)
    {
        $this->checkStaffPermission('master-data', 'delete');
        if (!array_key_exists($type, $this->models)) {
            abort(404);
        }
        $model = $this->models[$type];
        $item = $model::findOrFail($id);

        // Prevent tenants from deleting global data
        if ($item->subscriber_id === null && !auth()->user()->isSuperAdmin()) {
            abort(403, 'You cannot delete global master data.');
        }
        $item->delete();

        return redirect()->route('master-data.index', $type)->with('success', 'Record deleted successfully.');
    }
}
