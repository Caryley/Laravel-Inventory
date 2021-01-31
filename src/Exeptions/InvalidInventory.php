<?php

namespace Caryley\LaravelInventory\Exeptions;

use Exception;

class InvalidInventory extends Exception
{
    public static function value(string $quantity): self
    {
        return new self("Inventory `{$quantity}` is an invalid quantity.");
    }

    public static function subtract(string $quantity): self
    {
        return new self("The inventory quantity is `0` and unable to set quantity negative by the amount of: `{$quantity}`.");
    }

    public static function negative(string $quantity): self
    {
        return new self("The inventory quantity is less than `0`, unable to set quantity negative by the amount of: `{$quantity}`.");
    }
}
