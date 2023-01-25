<?php

use Caryley\LaravelInventory\Events\InventoryUpdate;
use Caryley\LaravelInventory\Inventory;
use Caryley\LaravelInventory\Tests\InventoryModel;
use Illuminate\Support\Facades\Event;

it('return true when inventory is missing', function () {
    expect($this->inventoryModel->currentInventory()->quantity)->toBe(0);
    expect($this->inventoryModel->notInInventory())->toBeTrue();
});

it('can set inventory', function () {
    expect($this->inventoryModel->currentInventory()->quantity)->toBe(0);

    $this->inventoryModel->setInventory(10);
    $this->inventoryModel->refresh();

    expect($this->inventoryModel->currentInventory()->quantity)->toBe(10);
});

it('can set inventory on a model without any inventory', function () {
    expect($this->secondInventoryModel->notInInventory())->toBeTrue();

    $this->secondInventoryModel->setInventory(10);
    $this->secondInventoryModel->refresh();

    expect($this->secondInventoryModel->currentInventory()->quantity)->toBe(10);
});

it('return true when inventory is existing and quantity match', function () {
    expect($this->inventoryModel->inInventory())->toBeFalse();
    $this->inventoryModel->setInventory(1);

    expect($this->inventoryModel->refresh()->currentInventory()->quantity)->toBe(1);
    expect($this->inventoryModel->inInventory())->toBeTrue();

    $this->inventoryModel->setInventory(3);
    $this->inventoryModel->refresh();

    expect($this->inventoryModel->inInventory(4))->toBeFalse();
    expect($this->inventoryModel->inInventory(3))->toBeTrue();
});

it('return false when inventory does not exist and checking inIventory', function () {
    expect($this->secondInventoryModel->inInventory())->toBeFalse();
});

it('return false when calling hasValidInventory on a model without inventory', function () {
    expect($this->secondInventoryModel->hasValidInventory())->toBeFalse();
});

it('return true when calling hasValidInventory on a model with inventory', function () {
    expect($this->inventoryModel->hasValidInventory())->toBeTrue();
});

it('prevent inventory from being set to a negative number', function () {
    $this->inventoryModel->setInventory(-1);

    expect($this->inventoryModel->currentInventory()->quantity)->toBe(0);
})->expectExceptionMessage('-1 is an invalid quantity for an inventory.');

it('inventory can be positive number', function () {
    $this->inventoryModel->setInventory(1);
    expect($this->inventoryModel->refresh()->currentInventory()->quantity)->toBe(1);
});

it('inventory can be incremented', function () {
    expect($this->inventoryModel->currentInventory()->quantity)->toBe(0);

    $this->inventoryModel->addInventory();

    $this->inventoryModel->refresh();
    expect($this->inventoryModel->currentInventory()->quantity)->toBe(1);

    $this->inventoryModel->addInventory();

    $this->inventoryModel->refresh();
    expect($this->inventoryModel->currentInventory()->quantity)->toBe(2);
});

it('inventory can be incremented by positive number', function () {
    expect($this->inventoryModel->currentInventory()->quantity)->toBe(0);

    $this->inventoryModel->addInventory(10);

    $this->inventoryModel->refresh();
    expect($this->inventoryModel->currentInventory()->quantity)->toBe(10);
});

it('increment inventory by using incrementInventory', function () {
    expect($this->inventoryModel->currentInventory()->quantity)->toBe(0);

    $this->inventoryModel->incrementInventory();

    $this->inventoryModel->refresh();
    expect($this->inventoryModel->currentInventory()->quantity)->toBe(1);
});

it('add to a non existing inventory', function () {
    $this->inventoryModel->clearInventory();
    expect($this->inventoryModel->inventory())->toBeNull();

    $this->inventoryModel->addInventory(5);
    $this->inventoryModel->refresh();

    expect($this->inventoryModel->currentInventory()->quantity)->toBe(5);
});

it('inventory can be decremented', function () {
    $this->inventoryModel->setInventory(1);
    expect($this->inventoryModel->inventories->first()->quantity)->toBe(1);

    $this->inventoryModel->subtractInventory();

    $this->inventoryModel->refresh();
    expect($this->inventoryModel->inventories->first()->quantity)->toBe(0);
});

it('decrement inventory by using decrementInventory', function () {
    $this->inventoryModel->setInventory(1);
    expect($this->inventoryModel->refresh()->currentInventory()->quantity)->toBe(1);

    $this->inventoryModel->decrementInventory();

    $this->inventoryModel->refresh();
    expect($this->inventoryModel->currentInventory()->quantity)->toBe(0);
});

it('converts inventory subtraction to absolute numbers', function () {
    $this->inventoryModel->setInventory(5);
    expect($this->inventoryModel->refresh()->currentInventory()->quantity)->toBe(5);

    $this->inventoryModel->subtractInventory(-4);
    $this->inventoryModel->refresh();
    expect($this->inventoryModel->refresh()->currentInventory()->quantity)->toBe(1);
});

test('inventory subtraction can not be negative', function () {
    $this->inventoryModel->setInventory(1);
    expect($this->inventoryModel->refresh()->currentInventory()->quantity)->toBe(1);

    $this->inventoryModel->subtractInventory(-2);

    $this->inventoryModel->refresh();
    expect($this->inventoryModel->currentInventory()->quantity)->toBe(1);
})->expectExceptionMessage('The inventory quantity is less than 0, unable to set quantity negative by the amount of: 2.');

it('return current inventory', function () {
    expect($this->inventoryModel->inventories)
        ->count()->toBe(1)
        ->first()->toBeInstanceOf(Inventory::class);

    $this->inventoryModel->setInventory(20);
    $this->inventoryModel->refresh();

    expect($this->inventoryModel)
        ->currentInventory()->quantity->toBe(20)
        ->inventories->count()->toBe(2)
        ->fresh()->currentInventory()->quantity->toBe(20);
});

test('scope to find where inventory match the parameters passed to the scope', function () {
    expect($this->inventoryModel->currentInventory()->quantity)->toBe(0);
    expect($this->inventoryModel->id)->toBe(InventoryModel::InventoryIs(0)->get()->first()->id);

    $this->inventoryModel->setInventory(10);
    $this->inventoryModel->refresh();

    expect($this->inventoryModel)
        ->currentInventory()->quantity->toBe(10)
        ->id->toBe(InventoryModel::InventoryIs(10)->get()->first()->id)
        ->id->toBe(InventoryModel::InventoryIs(10, '>=')->get()->first()->id)
        ->id->toBe(InventoryModel::InventoryIs(9, '>')->get()->first()->id)
        ->id->toBe(InventoryModel::InventoryIs(9, '>=')->get()->first()->id)
        ->id->toBe(InventoryModel::InventoryIs(10, '<=')->get()->first()->id);

    expect(InventoryModel::InventoryIs(9, '<')->get()->first())->toBeNull();
    expect(InventoryModel::InventoryIs(9, '<=')->get()->first())->toBeNull();
});

test('scope to find where inventory is the parameters passed to the scope in multiple models', function () {
    $this->secondInventoryModel->setInventory(20);
    $this->secondInventoryModel->refresh();

    expect($this->inventoryModel->currentInventory()->quantity)->toBe(0);
    expect(InventoryModel::InventoryIs(0, '=', [1, 2])->get())
        ->first()->id->toBe($this->inventoryModel->id)
        ->count()->toBe(1);

    expect($this->secondInventoryModel->currentInventory()->quantity)->toBe(20);
    expect(InventoryModel::InventoryIs(20, '=', [1, 2])->get())
        ->first()->id->toBe($this->secondInventoryModel->id)
        ->count()->toBe(1);
});

test('scope to find where inventory is the opposite then parameters passed to the scope', function () {
    expect($this->inventoryModel->currentInventory()->quantity)->toBe(0);

    expect($this->inventoryModel->id)
        ->toBe(InventoryModel::InventoryIs(0)->get()->first()->id)
        ->toBe(InventoryModel::InventoryIsNot(1)->get()->first()->id);
});

it('clear inventory and destroy all inventory records', function () {
    expect($this->inventoryModel->inventories)->count()->toBe(1);

    $this->inventoryModel->clearInventory();
    $this->inventoryModel->refresh();

    expect($this->inventoryModel)
        ->currentInventory()->toBeNull()
        ->inventories->count()->toBe(0);
});

it('clear inventory and set new inventory at the same time', function () {
    $this->inventoryModel->clearInventory(10);
    $this->inventoryModel->refresh();

    expect($this->inventoryModel)
        ->inventories->count()->toBe(1)
        ->currentInventory()->quantity->toBe(10);
});

it('dispatch an event when inventory is maniuplated', function () {
    Event::fake();

    $this->inventoryModel->setInventory(2);

    Event::assertDispatched(InventoryUpdate::class);
});
