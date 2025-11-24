<?php

declare(strict_types=1);

namespace Entrolytics\Laravel;

use Entrolytics\Client;
use Illuminate\Support\ServiceProvider;

class EntrolyticsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/entrolytics.php', 'entrolytics');

        $this->app->singleton(Client::class, function ($app) {
            $config = $app['config']['entrolytics'];

            return new Client($config['api_key'], [
                'host' => $config['host'] ?? 'https://entrolytics.click',
                'timeout' => $config['timeout'] ?? 10.0,
            ]);
        });

        $this->app->alias(Client::class, 'entrolytics');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../../config/entrolytics.php' => config_path('entrolytics.php'),
            ], 'entrolytics-config');
        }

        // Register Blade directive
        $this->registerBladeDirective();
    }

    /**
     * Register the @entrolytics Blade directive.
     */
    protected function registerBladeDirective(): void
    {
        $this->app['blade.compiler']->directive('entrolytics', function () {
            $websiteId = config('entrolytics.website_id');
            $host = config('entrolytics.host', 'https://entrolytics.click');

            if (empty($websiteId)) {
                return '';
            }

            $host = rtrim($host, '/');

            return <<<HTML
<script src="{$host}/script.js" data-website-id="{$websiteId}" defer></script>
HTML;
        });
    }
}
