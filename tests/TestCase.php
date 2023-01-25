<?php

declare(strict_types=1);

namespace Caryley\LaravelInventory\Tests;

use Caryley\LaravelInventory\Inventory;
use Caryley\LaravelInventory\LaravelInventoryServiceProvider;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Application;
use Orchestra\Testbench\TestCase as BaseTest;

abstract class TestCase extends BaseTest
{
    protected $inventoryModel;

    protected $secondInventoryModel;

    /**
     * Define environment setup.
     *
     * @param  $app
     * @return void
     */
    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    protected function useSqliteConnection($app): void
    {
        $app->config->set('database.default', 'sqlite');
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpDatabase($this->app);

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->inventoryModel = InventoryModel::first();

        $this->secondInventoryModel = InventoryModel::find(2);
    }

    protected function setUpDatabase($app): void
    {
        $builder = $app['db']->connection()->getSchemaBuilder();

        $builder->create('inventory_model', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
        });

        $builder->create('inventories', function (Blueprint $table) {
            $table->id();
            $table->integer('quantity')->default(0);
            $table->text('description')->nullable();
            $table->string('inventoriable_type');
            $table->unsignedBigInteger('inventoriable_id');
            $table->index(['inventoriable_type', 'inventoriable_id']);
            $table->timestamps();
        });

        InventoryModel::create([
            'name' => 'InventoryModel',
        ]);

        InventoryModel::create([
            'name' => 'SecondInventoryModel',
        ]);

        Inventory::create([
            'quantity' => '0',
            'description' => 'Inventory description',
            'inventoriable_type' => 'Caryley\LaravelInventory\Tests\InventoryModel',
            'inventoriable_id' => '1',
        ]);
    }

    /**
     * Get package providers.
     *
     * @param  Application  $app
     * @return array
     */
    protected function getPackageProviders($app): array
    {
        return [
            LaravelInventoryServiceProvider::class,
        ];
    }
}