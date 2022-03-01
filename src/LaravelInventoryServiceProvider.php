<?php

namespace Caryley\LaravelInventory;

use Caryley\LaravelInventory\Exeptions\InvalidInventoryModel;
use Illuminate\Support\ServiceProvider;

class LaravelInventoryServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/laravel-inventory.php', 'laravel-inventory');
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(): void
    {
        // Publishing is only necessary when using the CLI.
        if ($this->app->runningInConsole()) {
            $this->bootForConsole();
        }

        if (! class_exists('CreateInventoriesTable')) {
            $this->publishes([
                __DIR__.'/../database/migrations/create_inventories_table.php.stub' => database_path('migrations/2021_01_30_100000_create_inventories_table.php'),
            ], 'migrations');
        }

        // Publishing the configuration file.
        $this->publishes([
            __DIR__.'/../config/laravel-inventory.php' => config_path('laravel-inventory.php'),
        ], 'laravel-inventory.config');

        $this->guardAgainstInvalidInventoryModel();
    }

    /**
     * Console-specific booting.
     *
     * @return void
     */
    protected function bootForConsole(): void
    {
        // Publishing the configuration file.
        $this->publishes([
            __DIR__.'/../config/laravel-inventory.php' => config_path('laravel-inventory.php'),
        ], 'laravel-inventory.config');

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }

    /**
     * Guard against invalid inventory models.
     *
     * @return void
     */
    public function guardAgainstInvalidInventoryModel(): void
    {
        $modelClassName = config('laravel-inventory.inventory_model');

        if (! is_a($modelClassName, Inventory::class, true)) {
            throw InvalidInventoryModel::create($modelClassName);
        }
    }
}
