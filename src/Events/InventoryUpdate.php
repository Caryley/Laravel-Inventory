<?php

namespace Caryley\LaravelInventory\Events;

use Caryley\LaravelInventory\Inventory;
use Illuminate\Database\Eloquent\Model;

class InventoryUpdate
{
    /**
     *  @var \Caryley\LaravelInventory\Inventory|null
     */
    public $oldInventory;

    /**
     * @var \Caryley\LaravelInventory\Inventory
     */
    public $newInventory;

    /**
     *  @var \Illuminate\Database\Eloquent\Model
     */
    public $model;

    public function __construct(?Inventory $oldInventory, Inventory $newInventory, Model $model)
    {
        $this->oldInventory = $oldInventory;

        $this->newInventory = $newInventory;

        $this->model = $model;
    }
}
