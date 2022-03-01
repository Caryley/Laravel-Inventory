<?php

declare(strict_types=1);

namespace Caryley\LaravelInventory\Exeptions;

use Exception;

class InvalidInventory extends Exception
{
    /**
     * Invalid inventory exception for an invalid value.
     *
     * @param  int  $quantity
     * @return self
     */
    public static function value(int $quantity): self
    {
        return new self("{$quantity} is an invalid quantity for an inventory.");
    }

    /**
     * Invalid inventory exception for an invalid subtraction action.
     *
     * @param  int  $quantity
     * @return self
     */
    public static function subtract(int $quantity): self
    {
        return new self("The inventory quantity is 0 and unable to set quantity negative by the amount of: {$quantity}.");
    }

    /**
     * Invalid inventory exception for an invalid quantity that results in a negative number.
     *
     * @param  int  $quantity
     * @return self
     */
    public static function negative(int $quantity): self
    {
        return new self("The inventory quantity is less than 0, unable to set quantity negative by the amount of: {$quantity}.");
    }
}
