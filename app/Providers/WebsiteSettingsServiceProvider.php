<?php

namespace App\Providers;

use App\Services\WebsiteSettingsService;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;

class WebsiteSettingsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(WebsiteSettingsService::class, function ($app) {
            return new WebsiteSettingsService();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        View::composer('*', function ($view) {
            $websiteSettings = app(WebsiteSettingsService::class);
            $view->with(compact('websiteSettings'));
        });
    }
}
