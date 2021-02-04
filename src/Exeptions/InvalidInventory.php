<?php

namespace Caryley\LaravelInventory\Exeptions;

use Exception;

class InvalidInventory extends Exception
{
    /**
     * Invalid inventory exception for an invalid value.
     *
     * @param  string $quantity
     * @return self
     */
    public static function value($quantity)
    {
        return new self("Inventory `{$quantity}` is an invalid quantity.");
    }

    /**
     * Invalid inventory exception for an invalid subtraction action.
     *
     * @param  string $quantity
     * @return self
     */
    public static function subtract($quantity)
    {
        return new self("The inventory quantity is `0` and unable to set quantity negative by the amount of: `{$quantity}`.");
    }

    /**
     * Invalid inventory exception for an invalid quantity that results in a negative number.
     *
     * @param  string $quantity
     * @return self
     */
    public static function negative($quantity)
    {
        return new self("The inventory quantity is less than `0`, unable to set quantity negative by the amount of: `{$quantity}`.");
    }
}
