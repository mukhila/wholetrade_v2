<?php

namespace App\Http\Controllers;

abstract class Controller
{
    protected function checkStaffPermission(string $module, string $action): void
    {
        $user = auth()->user();

        if ($user && $user->hasRole('staff') && ! app(
            \App\Services\PermissionService::class
        )->forUser($user)->check($module, $action)) {
            abort(403, 'You do not have permission to perform this action.');
        }
    }
}
