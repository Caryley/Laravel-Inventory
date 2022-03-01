<?php

declare(strict_types=1);

namespace Caryley\LaravelInventory\Events;

use Caryley\LaravelInventory\Inventory;
use Illuminate\Database\Eloquent\Model;

class InventoryUpdate
{
    /**
     * Create a new InventoryUpdate instance.
     *
     * @param  \Caryley\LaravelInventory\Inventory|null  $oldInventory
     * @param  \Caryley\LaravelInventory\Inventory  $newInventory
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return void
     */
    public function __construct(public $oldInventory, public Inventory $newInventory, public Model $model)
    {
    }
}
