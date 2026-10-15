<?php

namespace App\Http\Controllers;

use App\Http\Controllers\StaffController;
use App\Models\StaffRole;
use App\Models\StaffRolePermission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StaffRoleController extends Controller
{
    public function index()
    {
        $roles = StaffRole::where('subscriber_id', auth()->user()->subscriber_id)
            ->withCount('users')
            ->latest()
            ->paginate(15);

        return view('staff.roles.index', compact('roles'));
    }

    public function create()
    {
        return view('staff.roles.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
        ]);

        StaffRole::create([
            'subscriber_id' => auth()->user()->subscriber_id,
            'name'          => $request->name,
            'description'   => $request->description,
        ]);

        return redirect()->route('staff-roles.index')
            ->with('success', 'Staff role created successfully.');
    }

    public function show(StaffRole $staffRole)
    {
        $this->authorizeRole($staffRole);
        $staffRole->load(['users', 'modulePermissions']);
        $modules     = StaffController::MODULES;
        $permissions = $staffRole->modulePermissions->keyBy('module');
        return view('staff.roles.show', compact('staffRole', 'modules', 'permissions'));
    }

    public function permissions(StaffRole $staffRole)
    {
        $this->authorizeRole($staffRole);
        $staffRole->load('modulePermissions');
        $modules     = StaffController::MODULES;
        $permissions = $staffRole->modulePermissions->keyBy('module');
        return view('staff.roles.permissions', compact('staffRole', 'modules', 'permissions'));
    }

    public function updatePermissions(Request $request, StaffRole $staffRole)
    {
        $this->authorizeRole($staffRole);

        DB::transaction(function () use ($request, $staffRole) {
            foreach (array_keys(StaffController::MODULES) as $module) {
                $perms = $request->input("modules.{$module}", []);

                StaffRolePermission::updateOrCreate(
                    ['staff_role_id' => $staffRole->id, 'module' => $module],
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

        return redirect()->route('staff-roles.show', $staffRole->id)
            ->with('success', 'Role permissions updated. All staff with this role now inherit these permissions.');
    }

    public function edit(StaffRole $staffRole)
    {
        $this->authorizeRole($staffRole);
        return view('staff.roles.edit', compact('staffRole'));
    }

    public function update(Request $request, StaffRole $staffRole)
    {
        $this->authorizeRole($staffRole);

        $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
        ]);

        $staffRole->update([
            'name'        => $request->name,
            'description' => $request->description,
        ]);

        return redirect()->route('staff-roles.index')
            ->with('success', 'Staff role updated successfully.');
    }

    public function destroy(StaffRole $staffRole)
    {
        $this->authorizeRole($staffRole);

        if ($staffRole->users()->exists()) {
            return redirect()->route('staff-roles.index')
                ->with('error', 'Cannot delete a role that is assigned to staff members. Reassign them first.');
        }

        $staffRole->delete();

        return redirect()->route('staff-roles.index')
            ->with('success', 'Staff role deleted successfully.');
    }

    private function authorizeRole(StaffRole $role): void
    {
        if ($role->subscriber_id !== auth()->user()->subscriber_id) {
            abort(403);
        }
    }
}
