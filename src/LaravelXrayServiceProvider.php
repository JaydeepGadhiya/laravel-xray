<?php

namespace Jaydeep\Xray;

use Illuminate\Support\ServiceProvider;
use Jaydeep\Xray\Commands\ScanCommand;
use Jaydeep\Xray\Commands\ArchitectureCommand;
use Jaydeep\Xray\Commands\DeadCodeCommand;
use Jaydeep\Xray\Commands\ReportCommand;

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
