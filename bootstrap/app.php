<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role'              => \App\Http\Middleware\CheckRole::class,
            'subscription'      => \App\Http\Middleware\CheckSubscription::class,
            'super_admin'       => \App\Http\Middleware\SuperAdmin::class,
            'staff_gate'        => \App\Http\Middleware\StaffGate::class,
            'module_permission' => \App\Http\Middleware\CheckModulePermission::class,
        ]);
          $middleware->validateCsrfTokens(except: [
            'print-bridge/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
