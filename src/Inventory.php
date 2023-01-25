<?php

namespace Caryley\LaravelInventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Inventory extends Model
{
    protected $guarded = [];

    protected $table = 'inventories';

    protected $casts = [
        'quantity' => 'integer',
    ];

    /**
     * Relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    public function __toString()
    {
        return $this->name;
    }
}
