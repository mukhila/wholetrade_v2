<?php

namespace App\Http\Middleware;

use App\Services\PermissionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applied to the admin+staff route group.
 *
 * Admins and super-admins always pass through.
 * Staff users are checked against their module + action permissions.
 * Other roles (salesperson, store_person, etc.) pass through unchanged.
 */
class StaffGate
{
    public function __construct(private PermissionService $permissions) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401);
        }

        // Admins and super admins always pass
        if ($user->isSuperAdmin() || $user->hasRole('admin')) {
            return $next($request);
        }

        // Staff users: enforce module-level permissions
        if ($user->hasRole('staff')) {
               if (! $user->is_active) {
                abort(403, 'Your account has been deactivated.');
            }

            $routeName = $request->route()?->getName() ?? '';
            $module    = $this->permissions->detectModule($routeName, $request);
            $action    = $this->permissions->detectAction($routeName, $request);

            if ($module && $this->permissions->forUser($user)->check($module, $action)) {
                return $next($request);
            }

            // No module detected means the route is not permission-gated
            if (! $module) {
                return $next($request);
            }

            if ($request->expectsJson()) {
                return response()->json(['error' => 'You do not have permission to perform this action.'], 403);
            }

            $label = ucwords(str_replace('-', ' ', $module));
            return redirect()->route('dashboard')
                ->with('warning', "You do not have permission to {$action} {$label}.");
        }

        // Other roles (salesperson, store_person, etc.) pass through
        return $next($request);
    }

}
