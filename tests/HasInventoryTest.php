<?php

namespace Caryley\LaravelInventory\Tests;

use Caryley\LaravelInventory\Events\InventoryUpdate;
use Caryley\LaravelInventory\Inventory;
use Illuminate\Support\Facades\Event;

class HasInventoryTest extends TestCase
{
    /** @test */
    public function return_true_when_inventory_is_missing()
    {
        $this->assertEquals(0, $this->inventoryModel->currentInventory()->quantity);
        $this->assertTrue($this->inventoryModel->notInInventory());
    }

    /** @test */
    public function can_set_inventory()
    {
        $this->assertEquals(0, $this->inventoryModel->currentInventory()->quantity);

        $this->inventoryModel->setInventory(10);
        $this->inventoryModel->refresh();

        $this->assertEquals(10, $this->inventoryModel->currentInventory()->quantity);
    }

    /** @test */
    public function can_set_inventory_on_a_model_without_any_inventory()
    {
        $this->assertTrue($this->secondInventoryModel->notInInventory());

        $this->secondInventoryModel->setInventory(10);
        $this->secondInventoryModel->refresh();

        $this->assertEquals(10, $this->secondInventoryModel->currentInventory()->quantity);
    }

    /** @test */
    public function return_true_when_inventory_is_existing_and_quantity_match()
    {
        $this->assertFalse($this->inventoryModel->inInventory());

        $this->inventoryModel->setInventory(1);
        $this->assertEquals(1, $this->inventoryModel->currentInventory()->quantity);
        $this->assertTrue($this->inventoryModel->inInventory());

        $this->inventoryModel->setInventory(3);
        $this->inventoryModel->refresh();

        $this->assertFalse($this->inventoryModel->inInventory(4));
        $this->assertTrue($this->inventoryModel->inInventory(3));
    }

    /** @test */
    public function return_false_when_inventory_does_not_exist_and_checking_inIventory()
    {
        $this->assertFalse($this->secondInventoryModel->inInventory());
    }

    /** @test */
    public function return_false_when_calling_hasValidInventory_on_a_model_without_inventory()
    {
        $this->assertFalse($this->secondInventoryModel->hasValidInventory());
    }

    /** @test */
    public function return_true_when_calling_hasValidInventory_on_a_model_with_inventory()
    {
        $this->assertTrue($this->inventoryModel->hasValidInventory());
    }

    /** @test */
    public function inventory_can_not_have_negative_quantity()
    {
        $this->expectExceptionMessage('-1 is an invalid quantity for an inventory.');
        $this->inventoryModel->setInventory(-1);

        $this->assertEquals(0, $this->inventoryModel->currentInventory()->quantity);
    }

    /** @test */
    public function inventory_can_be_positive_number()
    {
        $this->inventoryModel->setInventory(1);
        $this->assertEquals(1, $this->inventoryModel->currentInventory()->quantity);
    }

    /** @test */
    public function inventory_can_be_incremented()
    {
        $this->assertEquals(0, $this->inventoryModel->currentInventory()->quantity);

        // Default addition is 1.
        $this->inventoryModel->addInventory();

        $this->inventoryModel->refresh();
        $this->assertEquals(1, $this->inventoryModel->currentInventory()->quantity);

        $this->inventoryModel->addInventory();

        $this->inventoryModel->refresh();
        $this->assertEquals(2, $this->inventoryModel->currentInventory()->quantity);
    }

    /** @test */
    public function add_to_a_non_existing_inventory()
    {
        $this->inventoryModel->clearInventory();
        $this->assertNull($this->inventoryModel->inventory());

        $this->inventoryModel->addInventory(5);
        $this->inventoryModel->refresh();

        $this->assertEquals(5, $this->inventoryModel->currentInventory()->quantity);
    }

    /** @test */
    public function inventory_can_be_incremented_by_positive_number()
    {
        $this->assertEquals(0, $this->inventoryModel->currentInventory()->quantity);
        $this->inventoryModel->addInventory(10);

        $this->inventoryModel->refresh();
        $this->assertEquals(10, $this->inventoryModel->currentInventory()->quantity);
    }

    /** @test */
    public function inventory_can_be_subtracted()
    {
        $this->inventoryModel->setInventory(1);
        $this->assertEquals(1, $this->inventoryModel->inventories->first()->quantity);

        // Default subtract is 1.
        $this->inventoryModel->subtractInventory();
        $this->inventoryModel->refresh();
        $this->assertEquals(0, $this->inventoryModel->currentInventory()->quantity);
    }

    /** @test */
    public function inventory_subtraction_convert_to_absolute_numbers()
    {
        $this->inventoryModel->setInventory(5);
        $this->assertEquals(5, $this->inventoryModel->currentInventory()->quantity);

        $this->inventoryModel->subtractInventory(-4);
        $this->inventoryModel->refresh();
        $this->assertEquals(1, $this->inventoryModel->currentInventory()->quantity);
    }

    /** @test */
    public function inventory_can_not_be_subtracted_be_negative_value()
    {
        $this->inventoryModel->setInventory(1);
        $this->assertEquals(1, $this->inventoryModel->currentInventory()->quantity);

        $this->expectExceptionMessage('The inventory quantity is less than 0, unable to set quantity negative by the amount of: 2.');

        $this->inventoryModel->subtractInventory(-2);

        $this->inventoryModel->refresh();
        $this->assertEquals(1, $this->inventoryModel->currentInventory()->quantity);
    }

    /** @test */
    public function return_current_inventory()
    {
        $this->assertCount(1, $this->inventoryModel->inventories);
        $this->assertInstanceOf(Inventory::class, $this->inventoryModel->inventories->first());

        $this->inventoryModel->setInventory(10);
        $this->inventoryModel->refresh();
        $this->assertEquals(10, $this->inventoryModel->currentInventory()->quantity);

        $this->assertCount(2, $this->inventoryModel->inventories);

        $this->assertEquals(10, $this->inventoryModel->fresh()->currentInventory()->quantity);
    }

    /** @test */
    public function scope_to_find_where_inventory_match_the_parameters_passed_to_the_scope()
    {
        $this->assertEquals(0, $this->inventoryModel->currentInventory()->quantity);
        $this->assertEquals($this->inventoryModel, InventoryModel::InventoryIs(0)->get()->first());

        $this->inventoryModel->setInventory(10);
        $this->inventoryModel->refresh();

        $this->assertEquals(10, $this->inventoryModel->currentInventory()->quantity);
        $this->assertEquals($this->inventoryModel, InventoryModel::InventoryIs(9, '>')->get()->first());
        $this->assertEquals($this->inventoryModel, InventoryModel::InventoryIs(9, '>=')->get()->first());

        $this->assertNull(InventoryModel::InventoryIs(9, '<')->get()->first());
        $this->assertNull(InventoryModel::InventoryIs(9, '<=')->get()->first());
    }

    /** @test */
    public function scope_to_find_where_inventory_is_the_parameters_passed_to_the_scope_in_multiple_models()
    {
        $this->secondInventoryModel->setInventory(20);

        $this->assertEquals(0, $this->inventoryModel->currentInventory()->quantity);
        $this->assertEquals(20, $this->secondInventoryModel->currentInventory()->quantity);

        $this->assertEquals($this->inventoryModel, InventoryModel::InventoryIs(0, '=', [1, 2])->get()->first());
        $this->assertCount(1, InventoryModel::InventoryIs(0, '=', [1, 2])->get());

        $this->assertEquals(20, $this->secondInventoryModel->currentInventory()->quantity);
        $this->assertEquals($this->secondInventoryModel, InventoryModel::InventoryIs(20, '=', [1, 2])->get()->first());

        $this->assertCount(1, InventoryModel::InventoryIs(20, '=', [1, 2])->get());
    }

    /** @test */
    public function scope_to_find_where_inventory_is_the_opposite_then_parameters_passed_to_the_scope()
    {
        $this->assertEquals(0, $this->inventoryModel->currentInventory()->quantity);

        $this->assertEquals($this->inventoryModel, InventoryModel::InventoryIs(0)->get()->first());
        $this->assertEquals($this->inventoryModel, InventoryModel::InventoryIsNot(1)->get()->first());
    }

    /** @test */
    public function inventory_can_be_clear_and_destroyed()
    {
        $this->assertCount(1, $this->inventoryModel->inventories);

        $this->inventoryModel->clearInventory();
        $this->inventoryModel->refresh();

        $this->assertNull($this->inventoryModel->currentInventory());
        $this->assertCount(0, $this->inventoryModel->inventories);
    }

    /** @test */
    public function when_clearing_an_inventory_you_can_set_new_inventory_at_the_same_time()
    {
        $this->inventoryModel->clearInventory(10);
        $this->inventoryModel->refresh();

        $this->assertCount(1, $this->inventoryModel->inventories);
        $this->assertEquals(10, $this->inventoryModel->currentInventory()->quantity);
    }

    /** @test */
    public function an_event_is_dispatched_when_a_new_inventory_is_created()
    {
        Event::fake();

        $this->inventoryModel->setInventory(2);

        Event::assertDispatched(InventoryUpdate::class);
    }
}
