<?php

namespace App\Http\Middleware;

use App\Services\PermissionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Explicit per-route permission middleware.
 *
 * Apply to individual routes as:
 *   ->middleware('module_permission:products,create')
 *   ->middleware('module_permission:stock-list,export')
 *
 * Admins and super-admins always pass through.
 * Staff users are checked against their module permissions.
 */
class CheckModulePermission
{
    public function __construct(private PermissionService $permissions) {}

    public function handle(Request $request, Closure $next, string $module, string $action = 'view'): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401);
        }

        // Super admins and admins always pass
        if ($user->isSuperAdmin() || $user->hasRole('admin')) {
            return $next($request);
        }

        // Only enforce on staff users; other roles (salesperson, store) pass through
        if (! $user->hasRole('staff')) {
            return $next($request);
        }

        if (! $user->is_active) {
            abort(403, 'Your account has been deactivated.');
        }

        if ($this->permissions->forUser($user)->check($module, $action)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'error' => "You do not have permission to {$action} " . ucwords(str_replace('-', ' ', $module)) . '.',
            ], 403);
        }

        $label = ucwords(str_replace('-', ' ', $module));
        return redirect()->route('dashboard')
            ->with('warning', "You do not have permission to {$action} {$label}.");
    }
}
