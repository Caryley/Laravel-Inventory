<?php

namespace Caryley\LaravelInventory\Exeptions;

use Exception;

class InvalidInventoryModel extends Exception
{
    public static function create(string $model): self
    {
        return new self("The model `{$model}` is invalid. A valid model must extend the model Caryley\LaravelInventory\Inventory.");
    }
}
