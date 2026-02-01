<?php

namespace Vendor\NovaHtmlField;

use Illuminate\Support\ServiceProvider;
use Laravel\Nova\Events\ServingNova;
use Laravel\Nova\Nova;

class FieldServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            // Publish compiled assets to public (for custom serving if needed)
            $this->publishes([
                __DIR__.'/../dist' => public_path('vendor/nova-html-field'),
            ], ['nova-assets', 'laravel-assets']);

            // Publish source files for recompilation
            $this->publishes([
                __DIR__.'/../resources/js' => resource_path('vendor/nova-html-field/js'),
                __DIR__.'/../vite.config.js' => resource_path('vendor/nova-html-field/vite.config.js'),
                __DIR__.'/../package.json' => resource_path('vendor/nova-html-field/package.json'),
            ], 'nova-html-field-source');
        }

        Nova::serving(function (ServingNova $event) {
            Nova::script('html-field', __DIR__.'/../dist/js/field.js');
        });
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }
}
