<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Inventory Class
    |--------------------------------------------------------------------------
    /
    / The class of inventory model that holds all inventories. The model
    / must be or extend `Caryley\LaravelInventory\Inventory::class`
    / for the inventory package to work properly.
    /
    */
    'inventory_model' => Caryley\LaravelInventory\Inventory::class,

    /*
    |--------------------------------------------------------------------------
    | Default field attribute
    |--------------------------------------------------------------------------
    /
    / The name of the column which holds the key for the relationship with the model related to the inventory.
    / You can change this value if you have set a different name in the migration for the inventories
    / table. You might decide to go with the SKU field instead of the ID field.
    /
    */
    'model_primary_field_attribute' => 'inventoriable_id',
];
