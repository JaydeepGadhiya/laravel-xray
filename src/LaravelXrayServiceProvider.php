<?php

namespace Larapack\Xray;

use Illuminate\Support\ServiceProvider;
use Larapack\Xray\Commands\ScanCommand;
use Larapack\Xray\Commands\ArchitectureCommand;
use Larapack\Xray\Commands\DeadCodeCommand;
use Larapack\Xray\Commands\ReportCommand;

class LaravelXrayServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                ScanCommand::class,
                ArchitectureCommand::class,
                DeadCodeCommand::class,
                ReportCommand::class,
            ]);

            $this->publishes([
                __DIR__ . '/../config/xray.php' => config_path('xray.php'),
            ], 'xray-config');
        }
    }

    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/xray.php', 'xray'
        );
    }
}
