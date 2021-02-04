<?php

namespace Caryley\LaravelInventory\Exeptions;

use Exception;

class InvalidInventoryModel extends Exception
{
    /**
     * Invalid inventory model exception for an invalid model passed which does not extends \Caryley\LaravelInventory\Inventory.
     *
     * @param  string $model
     * @return self
     */
    public static function create($model)
    {
        return new self("The model `{$model}` is invalid. A valid model must extend the model Caryley\LaravelInventory\Inventory.");
    }
}
