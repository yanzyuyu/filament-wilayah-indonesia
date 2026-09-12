<?php

namespace Yanzyuyu\FilamentWilayah;

use Illuminate\Support\ServiceProvider;
use Yanzyuyu\FilamentWilayah\Commands\InstallWilayahCommand;

class FilamentWilayahServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/filament-wilayah.php',
            'filament-wilayah'
        );
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/filament-wilayah.php' => config_path('filament-wilayah.php'),
            ], 'filament-wilayah-config');

            $this->publishes([
                __DIR__ . '/../database/migrations/create_wilayah_indonesia_tables.php.stub' => $this->getMigrationFileName(),
            ], 'filament-wilayah-migrations');

            $this->commands([
                InstallWilayahCommand::class,
            ]);
        }
    }

    protected function getMigrationFileName(): string
    {
        $timestamp = date('Y_m_d_His');
        return database_path("migrations/{$timestamp}_create_wilayah_indonesia_tables.php");
    }
}
