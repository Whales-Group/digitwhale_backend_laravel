<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('admin/v1')
                ->namespace($this->namespace)
                ->group(base_path('routes/admin/v1/api.php'));

            Route::middleware('api')
                ->prefix('app/v1')
                ->namespace($this->namespace)
                ->group(base_path('routes/api/v1/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }
}
