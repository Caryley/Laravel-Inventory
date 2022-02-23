# Laravel Inventory

![GitHub Workflow Status][link-tests]
[![Latest Version on Packagist][ico-version]][link-packagist]
[![Total Downloads][ico-downloads]][link-downloads]
[![StyleCI][ico-styleci]][link-styleci]

The Laravel Inventory package helps track an inventory on any model with Laravel. <br/>
The package offers the following function:

-   Set a new inventory
-   Return the current inventory
-   Add to an inventory
-   Subtract from an inventory
-   Clear an inventory
-   Return boolean if the model is in inventory
-   Return boolean if the model is not in inventory
-   Return boolean if model has valid inventory
-   Scopes

## Installation

You can install the package via composer:

```bash
composer require caryley/laravel-inventory
```

Must publish the migration with:

```bash
php artisan vendor:publish --provider="Caryley\LaravelInventory\LaravelInventoryServiceProvider" --tag="migrations"
```

Or optionaly publish togther with `config` file:

```bash
php artisan vendor:publish --provider="Caryley\LaravelInventory\LaravelInventoryServiceProvider"
```

Migrate `inventories` table:

```bash
php artisan migrate
```

## Usage

Add the `HasInventory` to the Model, the trait will enable inventory tracking.

```php
...
use Caryley\LaravelInventory\HasInventory;

class Product extends Model
{
    use HasInventory;

    ...
}
```

### Functions

```php
$product = Product::first();

$product->hasValidInventory() //Return false

$product->setInventory(10); // $product->currentInventory()->quantity; (Will result in 10)

$product->hasValidInventory() //Return true

$product->currentInventory() //Return inventory instance if one exists


$product->addInventory(5); // $product->currentInventory()->quantity; (Will result in 15)

$product->subtractInventory(5); // $product->currentInventory()->quantity; (Will result in 10)

$product->inInventory(); // Return true

$product->clearInventory(); // $product->currentInventory(); (return null)

$product->notInInventory(); // Return true

--- Scopes ---

Product::InventoryIs(10)->get(); // Return all products with inventory of 10

Product::InventoryIs(10, '>=')->get(); // Return all products with inventory of 10 or greater

Product::InventoryIs(10, '<=')->get(); // Return all products with inventory of 10 or less

Product::InventoryIs(10, '>=', [1,2,3])->get(); // Return all products with inventory of 10 or greater where product id is [1,2,3]

Proudct::InventoryIsNot(10)->get(); // Return all products where inventory is not 10

Proudct::InventoryIsNot(10, [1,2,3])->get(); // Return all products where inventory is not 10 where product id is 1,2,3

```

#### hasValidInventory()

```php
$product->hasValidInventory(); // Check if the model has a valid inventory and return a boolean
```

#### SetInventory()

```php
$product->setInventory(10); // $product->currentInventory()->quantity; (Will result in 10) | Not allowed to use negative numbers
```

#### currentInventory()

```php
$product->currentInventory() //Return inventory instance if one exists, if not it will return null
```

#### addInventory()

```php
$product->addInventory(); // Will increment inventory by 1

$product->addInventory(10); // Will increment inventory by 10
```

#### subtractInventory()

```php
$product->subtractInventory(5); // Will subtract 5 from current inventory

$product->subtractInventory(-5); // Will subtract 5 from current inventory
```

#### inInventory()

```php
$product->inInventory(); // Will return a boolean if model inventory greater than 0

$product->inInventory(10); // Will return a boolean if model inventory greater than 10
```

#### notInInventory()

```php
$product->notInInventory(); // Will return a boolean if model inventory is less than 0
```

#### clearInventory()

```php
$product->clearInventory(); // Will clear all inventory for the model **Will delete all records, not only last record

$product->clearInventory(10); // Will clear all inventory for the model and will set new inventory of 10
```

#### InventoryIs() Scope

-   The scope accepts the first argument as quantity, the second argument as the operator, and the third argument as a model id or array of ids

```php
Product::InventoryIs(10)->get(); // Return all products with inventory of 10

Product::InventoryIs(10, '<=')->get(); // Return all products with inventory of 10 or less

Product::InventoryIs(10, '>=', [1,2,3])->get(); // Return all products with inventory of 10 or greater where product id is 1,2,3
```

#### InventoryIsNot() Scope

-   The scope accepts the first argument as quantity and the second argument as a model id or array of ids

```php
Proudct::InventoryIsNot(10)->get(); // Return all products where inventory is not 10

Proudct::InventoryIsNot(10, [1,2,3])->get(); // Return all products where inventory is not 10 where product id is 1,2,3
```

## Change log

Please see the [changelog](changelog.md) for more information on what has changed recently.

## Testing

```bash
composer test
```

## Contributing

Please see [contributing.md](contributing.md) for details and a todolist.

## Security

If you discover any security related issues, please email author email instead of using the issue tracker.

## Credits

-   [Tal Elmishali][link-author]
-   [All Contributors][link-contributors]

## Acknowledgements

Laravel-Inventory draws inspiration from spatie/laravel-model-status & appstract/laravel-stock (even though it doesn't rely on any of them).

## License

license. Please see the [license file](license.md) for more information.

[ico-version]: https://img.shields.io/packagist/v/caryley/laravel-inventory.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/caryley/laravel-inventory.svg?style=flat-square
[ico-styleci]: https://github.styleci.io/repos/334772924/shield?branch=master
[link-packagist]: https://packagist.org/packages/caryley/laravel-inventory
[link-downloads]: https://packagist.org/packages/caryley/laravel-inventory
[link-tests]: https://github.com/Caryley/Laravel-Inventory/workflows/Laravel-Inventory%20Test/badge.svg
[link-styleci]: https://github.styleci.io/repos/334772924?branch=master
[link-author]: https://github.com/talelmishali
[link-contributors]: ../../contributors
