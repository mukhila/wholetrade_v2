<?php

namespace App\Http\ViewComposers;

use App\Services\PermissionService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;

/**
 * Injects permission flags into every view so Blade templates can hide/show
 * buttons without duplicating permission logic.
 *
 * Variables shared:
 *   $canView   — may see the module index / detail pages
 *   $canCreate — may use the create form and store action
 *   $canEdit   — may use the edit form and update action
 *   $canDelete — may delete records
 *   $canExport — may export data (CSV / Excel / PDF)
 *   $canImport — may import data (CSV / Excel)
 */
class StaffPermissionComposer
{
    public function __construct(private PermissionService $permissions) {}

    public function compose(View $view): void
    {
        $user = Auth::user();

        if (! $user || $user->isSuperAdmin() || $user->hasRole('admin')) {
            $this->all($view, true);
            return;
        }

        if (! $user->hasRole('staff')) {
            $this->all($view, true);
            return;
        }

        $module = $this->permissions->detectModule(Route::currentRouteName() ?? '', request());

        if (! $module) {
            $this->all($view, true);
            return;
        }

        $permissions = $this->permissions->forUser($user)->allFor($module);

        $view->with([
            'canView'   => $permissions['view'],
            'canCreate' => $permissions['create'],
            'canEdit'   => $permissions['edit'],
            'canDelete' => $permissions['delete'],
            'canExport' => $permissions['export'],
            'canImport' => $permissions['import'],
        ]);
    }

    private function all(View $view, bool $val): void
    {
        $view->with([
            'canView'   => $val,
            'canCreate' => $val,
            'canEdit'   => $val,
            'canDelete' => $val,
            'canExport' => $val,
            'canImport' => $val,
        ]);
    }

}
