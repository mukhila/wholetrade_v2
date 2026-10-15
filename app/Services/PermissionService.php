<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;

/**
 * Centralised permission helper with per-request caching.
 *
 * Usage (via service container):
 *   app(PermissionService::class)->canView('products')
 *
 * Admins and super-admins always return true for every action.
 * Staff users are checked against their role + individual permissions.
 */
class PermissionService
{
    private ?User $user = null;
    private array $cache = [];

    public function setUser(User $user): static
    {
        if ($this->user?->id !== $user->id) {
            $this->user  = $user;
            $this->cache = [];
        }
        return $this;
    }

    public function forUser(User $user): static
    {
        return $this->setUser($user);
    }

    // ── Convenience methods ──────────────────────────────────────────────────

    public function canView(string $module): bool
    {
        return $this->check($module, 'view');
    }

    public function canCreate(string $module): bool
    {
        return $this->check($module, 'create');
    }

    public function canEdit(string $module): bool
    {
        return $this->check($module, 'edit');
    }

    public function canDelete(string $module): bool
    {
        return $this->check($module, 'delete');
    }

    public function canExport(string $module): bool
    {
        return $this->check($module, 'export');
    }

    public function canImport(string $module): bool
    {
        return $this->check($module, 'import');
    }

    /**
     * Returns the full permission matrix for a module as an associative array.
     */
    public function allFor(string $module): array
    {
        return [
            'view'   => $this->canView($module),
            'create' => $this->canCreate($module),
            'edit'   => $this->canEdit($module),
            'delete' => $this->canDelete($module),
            'export' => $this->canExport($module),
            'import' => $this->canImport($module),
        ];
    }

    /**
     * Returns true if the user has any permission at all for a module
     * (i.e. they can at least view it).
     */
    public function hasAnyAccess(string $module): bool
    {
        return $this->canView($module);
    }

    // ── Core check (with memoisation) ────────────────────────────────────────

    public function check(string $module, string $action): bool
    {
        if (! $this->user) {
            return false;
        }

        $module = $this->normalizeModule($module);
        $key = "{$module}.{$action}";

        if (! array_key_exists($key, $this->cache)) {
            // Ensure relationships are loaded before the first check
            $this->user->loadMissing('staffPermissions', 'staffRole.modulePermissions');
            $this->cache[$key] = $this->user->hasStaffPermission($module, $action);
        }

        return $this->cache[$key];
    }
    public function normalizeModule(string $module): string
    {
        return match ($module) {
            'product-list'    => 'products',
            'product-aliases' => 'products',
            default => $module,
        };
    }

    public function detectModule(?string $routeName, ?Request $request = null): ?string
    {
        $routeName = $routeName ?? '';
        $path = $request ? trim($request->path(), '/') : '';

        if (str_starts_with($routeName, 'admin.credit-notes')) return 'credit-notes';
        if (str_starts_with($routeName, 'admin.debit-notes'))  return 'debit-notes';
        if (str_starts_with($routeName, 'admin.quotations'))   return 'quotations';
        if (str_starts_with($routeName, 'admin.invoices'))     return 'admin-invoices';
        if (str_starts_with($routeName, 'supplier-reports'))   return 'supplier-reports';

        if (str_starts_with($routeName, 'credit-notes')) return 'sales-credit-notes';
        if (str_starts_with($routeName, 'debit-notes'))  return 'sales-debit-notes';
        if (str_starts_with($routeName, 'product-list')) return 'products';

        if ($path) {
            if (str_starts_with($path, 'api/sales-credit-notes/')) return 'sales-credit-notes';
            if (str_starts_with($path, 'api/sales-debit-notes/'))  return 'sales-debit-notes';
            if (str_starts_with($path, 'api/delivery-orders/'))    return 'delivery-orders';
            if (str_starts_with($path, 'api/sales-invoices/'))     return 'sales-invoices';
            if (str_starts_with($path, 'api/sales-receipts/'))     return 'sales-receipts';
            if (str_starts_with($path, 'api/product-list/'))       return 'products';
            if (str_starts_with($path, 'api/price-settings/'))     return 'price-settings';
            if (str_starts_with($path, 'api/price-updates/'))      return 'price-updates';
            if (str_starts_with($path, 'api/reports/'))            return 'reports';
            if (str_starts_with($path, 'api/admin/quotations/'))   return 'quotations';
            if (str_starts_with($path, 'api/admin/invoices/'))     return 'admin-invoices';
        }

        if ($routeName === '') {
            return null;
        }

        $pos = strpos($routeName, '.');
        return $this->normalizeModule($pos !== false ? substr($routeName, 0, $pos) : $routeName);
    }

    public function detectAction(?string $routeName, ?Request $request = null): string
    {
        $routeName = $routeName ?? '';
        $path = $request?->path() ?? '';

        if (preg_match('/\.(export|csv|excel|download)$/', $routeName) || str_contains($path, '/export')) {
            return 'export';
        }

        if (preg_match('/\.(import|import\.form|import\.store)$/', $routeName) || str_contains($path, '/import')) {
            return 'import';
        }

        if (preg_match('/\.(create|store)$/', $routeName)) {
            return 'create';
        }

        if (preg_match('/\.(add-stock|add-stock\.form|add-stock\.store|receipt\.store|pay\.store)$/', $routeName)) {
            return 'create';
        }

        if (str_ends_with($routeName, '.destroy')) {
            return 'delete';
        }

        if (preg_match('/\.(edit|update|confirm|pay|giro-paid)$/', $routeName)) {
            return 'edit';
        }

        return 'view';
    }
}
