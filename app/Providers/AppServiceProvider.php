<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Singleton so the per-request permission cache is shared across the whole request
        $this->app->singleton(\App\Services\PermissionService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
         Paginator::useBootstrapFive();

         // Share Company Settings with all views (including unauthenticated login page)
         \Illuminate\Support\Facades\View::composer('*', function ($view) {
             $view->with('companySettings', \App\Models\CompanySetting::first());
         });

         // Share staff permission flags with all views
         \Illuminate\Support\Facades\View::composer('*', \App\Http\ViewComposers\StaffPermissionComposer::class);
    }
}
