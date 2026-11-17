<?php

namespace Cable8mm\LaravelViteS3;

use Cable8mm\LaravelViteS3\Commands\CopyViteBuild;
use Illuminate\Support\ServiceProvider;

class ViteS3ServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/vite-s3.php',
            'vite-s3'
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/vite-s3.php' => config_path('vite-s3.php'),
        ], 'config');

        if ($this->app->runningInConsole()) {
            $this->commands([
                CopyViteBuild::class,
            ]);
        }
    }
}
