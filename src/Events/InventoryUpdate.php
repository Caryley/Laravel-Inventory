<?php

namespace Caryley\LaravelInventory\Events;

use Caryley\LaravelInventory\Inventory;
use Illuminate\Database\Eloquent\Model;

class InventoryUpdate
{
    /**
     * Old inventory instance before changes have made.
     *
     *  @var \Caryley\LaravelInventory\Inventory|null
     */
    public $oldInventory = null;

    /**
     * New inventory instance that has been persisted to the storage.
     *
     * @var \Caryley\LaravelInventory\Inventory
     */
    public $newInventory;

    /**
     * The model instance with respect to the inventoriable class.
     *
     *  @var \Illuminate\Database\Eloquent\Model
     */
    public $model;

    /**
     * Create a new InventoryUpdate instance.
     *
     * @param  \Caryley\LaravelInventory\Inventory|null $oldInventory
     * @param  \Caryley\LaravelInventory\Inventory $newInventory
     * @param  \Illuminate\Database\Eloquent\Model $model
     * @return void
     */
    public function __construct($oldInventory, Inventory $newInventory, Model $model)
    {
        $this->oldInventory = $oldInventory;

        $this->newInventory = $newInventory;

        $this->model = $model;
    }
}
