# Laravel Inventory

![GitHub Workflow Status][link-tests]
[![Latest Version on Packagist][ico-version]][link-packagist]
[![Total Downloads][ico-downloads]][link-downloads]
[![StyleCI][ico-styleci]][link-styleci]

The Laravel Inventory package helps track an inventory on any Laravel model.

<br/>

The package offers the following functionality:

-   Create and set a new inventory
-   Retrieve the current inventory
-   Manage inventory quantity
-   Clear an inventory
-   Determine if the model is in inventory or not.
-   Determine if the model has a valid inventory
-   Query scopes for inventoriable model

## Installation

```bash
composer require caryley/laravel-inventory
```

Publish the migration with:

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

Add the `HasInventory` trait to the model.

```php
...
use Caryley\LaravelInventory\HasInventory;

class Product extends Model
{
    use HasInventory;

    ...
}
```

#### hasValidInventory()

```php
$product->hasValidInventory(); // Determine if the model has a valid inventory.
```

#### setInventory()

```php
$product->setInventory(10); // $product->currentInventory()->quantity; (Will result in 10) | Not allowed to use negative numbers.
```

#### currentInventory()

```php
$product->currentInventory() //Return inventory instance if one exists, if not it will return null.
```

#### addInventory()

```php
$product->addInventory(); // Will increment inventory by 1.

$product->addInventory(10); // Will increment inventory by 10.
```

#### incrementInventory()

```php
$product->incrementInventory(10); // Will increment inventory by 10.
```

#### subtractInventory()

```php
$product->subtractInventory(5); // Will subtract 5 from current inventory.

$product->subtractInventory(-5); // Will subtract 5 from current inventory.
```

#### decrementInventory()

```php
$product->decrementInventory(5); // Will subtract 5 from current inventory.

$product->decrementInventory(-5); // Will subtract 5 from current inventory.
```

#### inInventory()

```php
$product->inInventory(); // Will return a boolean if model inventory greater than 0.

$product->inInventory(10); // Will return a boolean if model inventory greater than 10.
```

#### notInInventory()

```php
$product->notInInventory(); // Determine if model inventory is less than 0.
```

#### clearInventory()

```php
$product->clearInventory(); // Will clear all inventory for the model **Will delete all records, not only last record.

$product->clearInventory(10); // Will clear the inventory for the model and will set new inventory of 10.
```

### Scopes

#### InventoryIs()

-   The scope accepts the first argument as quantity, the second argument as the operator, and the third argument as a model id or array of ids.

```php
Product::InventoryIs(10)->get(); // Return all products with inventory of 10.

Product::InventoryIs(10, '<=')->get(); // Return all products with inventory of 10 or less.

Product::InventoryIs(10, '>=', [1,2,3])->get(); // Return all products with inventory of 10 or greater where product id is 1,2,3
```

#### InventoryIsNot()

-   The scope accepts a first argument of a quantity and a second argument of a model id or array of ids

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
