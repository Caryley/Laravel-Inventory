<?php

namespace Caryley\LaravelInventory\Tests;

use Caryley\LaravelInventory\HasInventory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class InventoryModel extends Model
{
    use HasInventory;

    protected $guarded = [];

    protected $table = 'inventory_model';
}
