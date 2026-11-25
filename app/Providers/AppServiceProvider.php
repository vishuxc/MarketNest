<?php

namespace App\Providers;

use App\Models\City;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::defaultView('components.market-pagination');

        View::composer('components.layouts.marketplace', function ($view) {
            $view->with('navigationCities', City::orderBy('name')->get(['id', 'name', 'slug']));
        });
    }
}
