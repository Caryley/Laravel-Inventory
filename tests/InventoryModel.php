<?php

namespace Caryley\LaravelInventory\Tests;

use Caryley\LaravelInventory\HasInventory;
use Illuminate\Database\Eloquent\Model;

class InventoryModel extends Model
{
    use HasInventory;

    protected $guarded = [];

    protected $table = 'inventory_model';
}
