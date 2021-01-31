<?php

namespace Caryley\LaravelInventory\Tests;

use Caryley\LaravelInventory\Inventory;
use Caryley\LaravelInventory\LaravelInventoryServiceProvider;
use Caryley\LaravelInventory\Tests\InventoryModel;
use Illuminate\Database\Schema\Blueprint;
use Orchestra\Testbench\TestCase as BaseTest;

abstract class TestCase extends BaseTest
{
    protected $inventoryModel;

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    protected function useSqliteConnection($app)
    {
        $app->config->set('database.default', 'sqlite');
    }

    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpDatabase($this->app);

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->inventoryModel = InventoryModel::first();
    }

    protected function setUpDatabase($app)
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
     * @param  \Illuminate\Foundation\Application $app
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            LaravelInventoryServiceProvider::class,
        ];
    }

    /**
     * Get package aliases.
     *
     * @param  \Illuminate\Foundation\Application $app
     * @return array
     */
    protected function getPackageAliases($app)
    {
        return [
            //
        ];
    }
}
