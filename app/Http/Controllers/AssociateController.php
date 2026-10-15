<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Associate;
use App\Models\Tax;
use App\Models\Currency;
use App\Models\AssociateGroup;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\AssociateCreatedMail;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AssociateController extends Controller
{
    public function index(Request $request)
    {
        $query = Associate::with(['tax', 'currency', 'associateGroup', 'salesman']);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $associates = $query->latest()->paginate(10);
        return view('associates.index', compact('associates'));
    }

    /**
     * API for searching associates (for Select2)
     */
    public function searchApi(Request $request)
    {
        $search = $request->input('term');
        $type   = $request->input('type');

        // BUG-021 FIX: non-admin users may only search Customers, never Suppliers.
        if (!auth()->user()->hasRole('admin')) {
            $type = 'Customer';
        }

        $query = Associate::query();

        if ($type) {
            $query->where('type', $type);
        }

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $associates = $query->limit(20)->get(['id', 'name']);

        $results = $associates->map(function($associate) {
            return ['id' => $associate->id, 'text' => $associate->name];
        });

        return response()->json(['results' => $results]);
    }

    public function create()
    {
        $taxes = Tax::where('status', true)->get();
        // Currency does not have a status field
        $currencies = Currency::all();
        $groups = AssociateGroup::where('status', true)->get();
        
        // Try to filter users by role, fallback to all if no roles defined yet
        $users = User::whereHas('roles', function($q) {
            $q->whereIn('slug', ['salesman', 'storeperson']);
        })->get();

        if ($users->isEmpty()) {
            $users = User::all();
        }

        $defaultCurrency = Currency::where('is_default', true)->first();
        
        // Get potential parents (all active associates)
        $parents = Associate::where('status', true)->get();

        // Predict Code
        $lastId = Associate::max('id') ?? 0;
        $nextId = $lastId + 1;
        $code = 'ASC-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);

        return view('associates.create', compact('taxes', 'currencies', 'groups', 'users', 'defaultCurrency', 'code', 'parents'));
    }

    public function store(Request $request)
    {
        $this->checkStaffPermission('associates', 'create');
        // Validation
        $request->validate([
            'type' => 'required|in:Customer,Supplier',
            'name' => 'required|string|max:255',
            'username' => 'required|string|min:4|max:50|alpha_dash|unique:associates,username',
            'email' => 'nullable|email|unique:associates,email',
            'address_line_1' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'credit_limit' => 'nullable|numeric|min:0',
            'tax_id' => 'nullable|exists:taxes,id',
            'gst_option' => 'nullable|in:E,I,N',
            'currency_id' => 'nullable|exists:currencies,id',
            'associate_group_id' => 'nullable|exists:associate_groups,id',
            'salesman_id' => 'nullable|exists:users,id',
            'parent_associate_id' => 'nullable|exists:associates,id',
        ]);

        // Auto-generate logic
        $data = $request->all();
        
        // Ensure Code Uniqueness (Double Check)
        $lastId = Associate::max('id') ?? 0;
        $nextId = $lastId + 1;
        $data['code'] = 'ASC-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);

        // Password
        $plainPassword = Str::random(10);
        $data['password'] = Hash::make($plainPassword);

        // Default Currency
        if (empty($data['currency_id'])) {
            $default = Currency::where('is_default', true)->first();
            if ($default) {
                $data['currency_id'] = $default->id;
            } else {
                 return back()->withInput()->withErrors(['currency_id' => 'Please select a currency or set a default system currency.']);
            }
        }

        $data['status'] = $request->has('status'); // Checkbox handling

        $associate = Associate::create($data);

        // Send Email
        // Send Email if exists
        try {
            if ($associate->email) {
                Mail::to($associate->email)->send(new AssociateCreatedMail($associate, $plainPassword));
            }
        } catch (\Exception $e) {
            // Log error or ignore
        }

        return redirect()->route('associates.index')->with('success', 'Associate created successfully.');
    }

    public function show(Associate $associate)
    {
        return view('associates.show', compact('associate'));
    }

    public function edit(Associate $associate)
    {
        $taxes = Tax::where('status', true)->get();
        $currencies = Currency::all();
        $groups = AssociateGroup::where('status', true)->get();
        
        $users = User::whereHas('roles', function($q) {
            $q->whereIn('slug', ['salesman', 'storeperson']);
        })->get();

        if ($users->isEmpty()) {
            $users = User::all();
        }
        
        // Get parents, excluding self to prevent circle
        $parents = Associate::where('id', '!=', $associate->id)->where('status', true)->get();
        
        return view('associates.edit', compact('associate', 'taxes', 'currencies', 'groups', 'users', 'parents'));
    }

    public function update(Request $request, Associate $associate)
    {
        $this->checkStaffPermission('associates', 'edit');
        $request->validate([
            'type' => 'required|in:Customer,Supplier',
            'name' => 'required|string|max:255',
            'username' => 'required|string|min:4|max:50|alpha_dash|unique:associates,username,' . $associate->id,
            'email' => 'nullable|email|unique:associates,email,' . $associate->id,
            'address_line_1' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'credit_limit' => 'nullable|numeric|min:0',
            'tax_id' => 'nullable|exists:taxes,id',
            'gst_option' => 'nullable|in:E,I,N',
            'currency_id' => 'required|exists:currencies,id',
            'associate_group_id' => 'nullable|exists:associate_groups,id',
            'salesman_id' => 'nullable|exists:users,id',
            'parent_associate_id' => 'nullable|exists:associates,id',
        ]);
        
        $data = $request->all();
        $data['status'] = $request->has('status');

        // Prevent self-parenting logic
        if ($data['parent_associate_id'] == $associate->id) {
             return back()->withInput()->withErrors(['parent_associate_id' => 'An associate cannot be its own parent.']);
        }

        $associate->update($data);

        return redirect()->route('associates.index')->with('success', 'Associate updated successfully.');
    }

    public function export(): StreamedResponse
    {
        $associates = Associate::with(['associateGroup', 'salesman'])->latest()->get();

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="associates_' . date('Ymd_His') . '.csv"',
        ];

        return response()->stream(function () use ($associates) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'type', 'name', 'username', 'email', 'phone',
                'contact_person', 'alternate_phone',
                'address_line_1', 'address_line_2', 'postal_code',
                'website', 'credit_limit', 'status',
            ]);

            foreach ($associates as $a) {
                fputcsv($handle, [
                    $a->type,
                    $a->name,
                    $a->username,
                    $a->email,
                    $a->phone,
                    $a->contact_person,
                    $a->alternate_phone,
                    $a->address_line_1,
                    $a->address_line_2,
                    $a->postal_code,
                    $a->website,
                    $a->credit_limit,
                    $a->status ? 1 : 0,
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }

    public function importTemplate(): StreamedResponse
    {
        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="associates_import_template.csv"',
        ];

        return response()->stream(function () {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'type', 'name', 'username', 'email', 'phone',
                'contact_person', 'alternate_phone',
                'address_line_1', 'address_line_2', 'postal_code',
                'website', 'credit_limit', 'status',
            ]);

            // Sample row
            fputcsv($handle, [
                'Customer', 'John Doe Trading', 'johndoe', 'john@example.com', '0123456789',
                'John Doe', '0198765432',
                '123 Main Street', 'Unit 4', '50000',
                'www.johndoe.com', '5000.00', '1',
            ]);

            fclose($handle);
        }, 200, $headers);
    }

    public function import(Request $request)
    {
        $this->checkStaffPermission('associates', 'create');

        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $file = $request->file('csv_file');
        $handle = fopen($file->getRealPath(), 'r');

        $defaultCurrency = Currency::where('is_default', true)->first();
        if (!$defaultCurrency) {
            return back()->with('error', 'No default currency set. Please configure one before importing.');
        }

        $headers = fgetcsv($handle); // skip header row
        $imported = 0;
        $skipped  = [];
        $rowNum   = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $rowNum++;

            if (count($row) < 3) {
                $skipped[] = "Row {$rowNum}: too few columns.";
                continue;
            }

            $data = array_combine([
                'type', 'name', 'username', 'email', 'phone',
                'contact_person', 'alternate_phone',
                'address_line_1', 'address_line_2', 'postal_code',
                'website', 'credit_limit', 'status',
            ], array_pad($row, 13, null));

            // Validate required fields
            if (!in_array($data['type'], ['Customer', 'Supplier'])) {
                $skipped[] = "Row {$rowNum}: type must be Customer or Supplier (got '{$data['type']}').";
                continue;
            }
            if (empty(trim($data['name'] ?? ''))) {
                $skipped[] = "Row {$rowNum}: name is required.";
                continue;
            }
            if (empty(trim($data['username'] ?? ''))) {
                $skipped[] = "Row {$rowNum}: username is required.";
                continue;
            }
            if (empty(trim($data['address_line_1'] ?? ''))) {
                $skipped[] = "Row {$rowNum}: address_line_1 is required.";
                continue;
            }

            // Skip duplicate usernames
            if (Associate::where('username', $data['username'])->exists()) {
                $skipped[] = "Row {$rowNum}: username '{$data['username']}' already exists.";
                continue;
            }

            $lastId  = Associate::max('id') ?? 0;
            $code    = 'ASC-' . str_pad($lastId + 1, 4, '0', STR_PAD_LEFT);

            Associate::create([
                'type'             => $data['type'],
                'code'             => $code,
                'name'             => trim($data['name']),
                'username'         => trim($data['username']),
                'email'            => trim($data['email'] ?? '') ?: null,
                'phone'            => trim($data['phone'] ?? '') ?: null,
                'contact_person'   => trim($data['contact_person'] ?? '') ?: null,
                'alternate_phone'  => trim($data['alternate_phone'] ?? '') ?: null,
                'address_line_1'   => trim($data['address_line_1']),
                'address_line_2'   => trim($data['address_line_2'] ?? '') ?: null,
                'postal_code'      => trim($data['postal_code'] ?? '') ?: null,
                'website'          => trim($data['website'] ?? '') ?: null,
                'credit_limit'     => is_numeric($data['credit_limit'] ?? null) ? $data['credit_limit'] : 0,
                'status'           => in_array(trim($data['status'] ?? '1'), ['1', 'true', 'yes', 'active'], true),
                'currency_id'      => $defaultCurrency->id,
                'password'         => Hash::make(Str::random(10)),
            ]);

            $imported++;
        }

        fclose($handle);

        $message = "{$imported} associate(s) imported successfully.";
        if ($skipped) {
            $message .= ' Skipped rows: ' . implode(' | ', $skipped);
            return redirect()->route('associates.index')->with('error', $message);
        }

        return redirect()->route('associates.index')->with('success', $message);
    }

    public function toggleStatus(Associate $associate)
    {
        $this->checkStaffPermission('associates', 'edit');
        $associate->update(['status' => !$associate->status]);
        $label = $associate->status ? 'activated' : 'deactivated';
        return redirect()->back()->with('success', 'Associate "' . $associate->name . '" ' . $label . ' successfully.');
    }

    public function destroy(Associate $associate)
    {
        $this->checkStaffPermission('associates', 'delete');
        // BUG-015 FIX: guard against deleting associates that have linked transactions.
        $hasTransactions =
            \App\Models\SalesInvoice::where('customer_id', $associate->id)->exists() ||
            \App\Models\PurchaseOrder::where('supplier_id', $associate->id)->exists() ||
            \App\Models\Quotation::where('customer_id', $associate->id)->exists() ||
            \App\Models\SupplierInvoice::where('supplier_id', $associate->id)->exists();

        if ($hasTransactions) {
            return redirect()->route('associates.index')
                ->with('error', 'Cannot delete this associate — they have linked transactions. Deactivate them instead.');
        }

        $associate->delete();
        return redirect()->route('associates.index')->with('success', 'Associate deleted successfully.');
    }
}
