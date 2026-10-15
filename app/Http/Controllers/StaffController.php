<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\StaffPermission;
use App\Models\StaffRole;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class StaffController extends Controller
{
    /** Module slug → human-readable label mapping used in permissions UI */
    public const MODULES = [
        'products'                => 'Products',
        'associates'              => 'Customers / Suppliers',
        'associate-groups'        => 'Associate Groups',
        'quotations'              => 'Sales Quotations (Admin)',
        'sales-invoices'          => 'Sales Invoices',
        'sales-receipts'          => 'Sales Receipts',
        'sales-credit-notes'      => 'Sales Credit Notes',
        'sales-debit-notes'       => 'Sales Debit Notes',
        'delivery-orders'         => 'Delivery Orders',
        'proforma-invoices'       => 'Proforma Invoices',
        'purchase-orders'         => 'Purchase Orders',
        'purchase-delivery-notes' => 'Purchase Delivery Notes',
        'supplier-invoices'       => 'Supplier Invoices',
        'supplier-purchases'      => 'Supplier Purchases',
        'supplier-payments'       => 'Supplier Payments',
        'credit-notes'            => 'Supplier Credit Notes',
        'debit-notes'             => 'Supplier Debit Notes',
        'supplier-reports'        => 'Supplier Reports',
        'reports'                 => 'Reports',
        'stock-list'              => 'Stock List',
        'price-settings'          => 'Price Settings',
        'price-updates'           => 'Price Updates',
        'cost-price-updates'      => 'Cost Price Updates',
        'product-labels'          => 'Product Labels',
        'company-settings'        => 'Company Settings',
        'master-data'             => 'Master Data',
        'old-sales-invoices'      => 'Old Sales Invoices',
        'admin-invoices'          => 'Admin Invoices (Legacy)',
    ];

    public function index(Request $request)
    {
        $staffRoleId = Role::where('slug', 'staff')->value('id');

        $query = User::whereHas('roles', fn($q) => $q->where('slug', 'staff'))
            ->with(['staffPermissions', 'staffRole']);

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn($q) => $q
                ->where('name', 'like', "%{$s}%")
                ->orWhere('username', 'like', "%{$s}%")
                ->orWhere('email', 'like', "%{$s}%")
                ->orWhere('phone', 'like', "%{$s}%"));
        }

        $staff = $query->latest()->paginate(15);

        // Surface any users who were created without a role (failed mid-creation) so admin can clean them up
        $orphaned = [];
        if ($staffRoleId && !$request->filled('search')) {
            $orphaned = User::where('id', '>', 1)
                ->whereNotNull('phone')
                ->whereDoesntHave('roles')
                ->get(['id', 'name', 'email', 'created_at']);
        }

        return view('staff.index', compact('staff', 'orphaned'));
    }

    public function create()
    {
        $staffRoles = StaffRole::where('subscriber_id', auth()->user()->subscriber_id)
            ->orderBy('name')->get();
        return view('staff.create', compact('staffRoles'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'username'      => 'required|string|max:100|unique:users,username|regex:/^[a-zA-Z0-9_.\-]+$/',
            'email'         => 'nullable|email|max:255|unique:users,email',
            'phone'         => 'required|string|max:20',
            'password'      => 'required|string|min:8|confirmed',
            'staff_role_id' => [
                'bail',
                'required',
                'integer',
                Rule::exists('staff_roles', 'id')->where(fn ($query) => $query->where('subscriber_id', auth()->user()->subscriber_id)),
            ],
        ], [
            'staff_role_id.required' => 'Please select a Staff Role.',
            'staff_role_id.integer'  => 'Please select a valid Staff Role.',
            'staff_role_id.exists'   => 'The selected Staff Role does not exist or does not belong to your account.',
        ]);

        $staffRole = Role::firstOrCreate(['slug' => 'staff'], ['name' => 'Staff']);

        $userId = null;
        DB::transaction(function () use ($validated, $staffRole, &$userId) {
            $user = User::create([
                'name'          => $validated['name'],
                'username'      => $validated['username'],
                'email'         => $validated['email'] ?? null,
                'phone'         => $validated['phone'],
                'password'      => Hash::make($validated['password']),
                'subscriber_id' => auth()->user()->subscriber_id,
                'is_active'     => true,
                'staff_role_id' => $validated['staff_role_id'],
            ]);

            $user->roles()->attach($staffRole->id);
            $userId = $user->id;
        });

        return redirect()->route('staff.permissions', $userId)
            ->with('success', 'Staff member created. Now assign their module permissions.');
    }

    public function show(User $staff)
    {
        $this->authorizeStaff($staff);
        $staff->load(['staffPermissions', 'staffRole.modulePermissions']);
        $modules     = self::MODULES;
        $permissions = $staff->staffPermissions->keyBy('module');
        return view('staff.show', compact('staff', 'modules', 'permissions'));
    }

    public function edit(User $staff)
    {
        $this->authorizeStaff($staff);
        $staffRoles = StaffRole::where('subscriber_id', auth()->user()->subscriber_id)
            ->orderBy('name')->get();
        return view('staff.edit', compact('staff', 'staffRoles'));
    }

    public function update(Request $request, User $staff)
    {
        $this->authorizeStaff($staff);

        $request->validate([
            'name'          => 'required|string|max:255',
            'username'      => 'required|string|max:100|unique:users,username,' . $staff->id . '|regex:/^[a-zA-Z0-9_.\-]+$/',
            'email'         => 'nullable|email|unique:users,email,' . $staff->id,
            'phone'         => 'required|string|max:20',
            'password'      => 'nullable|string|min:8|confirmed',
            'staff_role_id' => [
                'required',
                'integer',
                Rule::exists('staff_roles', 'id')->where(fn ($query) => $query->where('subscriber_id', auth()->user()->subscriber_id)),
            ],
        ]);

        $data = [
            'name'          => $request->name,
            'username'      => $request->username,
            'email'         => $request->filled('email') ? $request->email : null,
            'phone'         => $request->phone,
            'is_active'     => $request->boolean('is_active'),
            'staff_role_id' => $request->staff_role_id,
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $staff->update($data);

        return redirect()->route('staff.index')->with('success', 'Staff member updated successfully.');
    }

    public function destroy(User $staff)
    {
        $this->authorizeStaff($staff);
        DB::transaction(function () use ($staff) {
            $staff->roles()->detach();
            StaffPermission::where('user_id', $staff->id)->delete();
            $staff->delete();
        });
        return redirect()->route('staff.index')->with('success', 'Staff member deleted successfully.');
    }

    public function permissions(User $staff)
    {
        $this->authorizeStaff($staff);
        $staff->load(['staffPermissions', 'staffRole.modulePermissions']);
        $modules      = self::MODULES;
        $permissions  = $staff->staffPermissions->keyBy('module');
        $rolePerms    = $staff->staffRole?->modulePermissions->keyBy('module') ?? collect();
        return view('staff.permissions', compact('staff', 'modules', 'permissions', 'rolePerms'));
    }

    public function updatePermissions(Request $request, User $staff)
    {
        $this->authorizeStaff($staff);

        DB::transaction(function () use ($request, $staff) {
            foreach (array_keys(self::MODULES) as $module) {
                $perms = $request->input("modules.{$module}", []);

                StaffPermission::updateOrCreate(
                    ['user_id' => $staff->id, 'module' => $module],
                    [
                        'can_view'   => in_array('view',   $perms),
                        'can_create' => in_array('create', $perms),
                        'can_edit'   => in_array('edit',   $perms),
                        'can_delete' => in_array('delete', $perms),
                        'can_export' => in_array('export', $perms),
                        'can_import' => in_array('import', $perms),
                    ]
                );
            }
        });

        return redirect()->route('staff.show', $staff->id)
            ->with('success', 'Permissions updated successfully.');
    }

    /**
     * Uses a direct DB query so it works on freshly-created users
     * without relying on cached relationship data.
     */
    private function authorizeStaff(User $staff): void
    {
        $isStaff = $staff->roles()->where('slug', 'staff')->exists();
        if (! $isStaff) {
            abort(404, 'Staff member not found.');
        }
    }
}
