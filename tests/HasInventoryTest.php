<?php

namespace Caryley\LaravelInventory\Tests;

class hasInventoryTest extends TestCase
{
    /** @test */
    public function return_true_when_inventory_is_missing()
    {
        $this->assertEquals(0, $this->inventoryModel->inventories->first()->quantity);
        $this->assertTrue($this->inventoryModel->notInInventory());
    }

    /** @test */
    public function can_set_inventory()
    {
        $this->assertEquals(0, $this->inventoryModel->inventories->first()->quantity);

        $this->inventoryModel->setInventory(10);
        $this->inventoryModel->refresh();

        $this->assertEquals(10, $this->inventoryModel->inventories->first()->quantity);
    }

    /** @test */
    public function return_true_when_inventory_is_existing()
    {
        $this->inventoryModel->setInventory(1);
        $this->assertEquals(1, $this->inventoryModel->inventories->first()->quantity);
        $this->assertTrue($this->inventoryModel->inInventory());
    }

    /** @test */
    public function it_can_not_be_negative_quantity()
    {
        $this->expectExceptionMessage('Inventory `-1` is an invalid quantity.');
        $this->inventoryModel->setInventory(-1);

        $this->assertEquals(0, $this->inventoryModel->inventories->first()->quantity);
    }

    /** @test */
    public function inventory_can_be_positive_number()
    {
        $this->inventoryModel->setInventory(1);
        $this->assertEquals(1, $this->inventoryModel->inventories->first()->quantity);
    }

    /** @test */
    public function inventory_can_be_incremented()
    {
        $this->assertEquals(0, $this->inventoryModel->inventories->first()->quantity);
        /**
         * Default addition is 1.
         */
        $this->inventoryModel->addInventory();

        $this->inventoryModel->refresh();
        $this->assertEquals(1, $this->inventoryModel->inventories->first()->quantity);
    }

    /** @test */
    public function inventory_can_be_incremented_by_positive_number()
    {
        $this->assertEquals(0, $this->inventoryModel->inventories->first()->quantity);
        $this->inventoryModel->addInventory(10);

        $this->inventoryModel->refresh();
        $this->assertEquals(10, $this->inventoryModel->inventories->first()->quantity);
    }

    /** @test */
    public function inventory_can_be_subtracted()
    {
        $this->inventoryModel->setInventory(1);
        $this->assertEquals(1, $this->inventoryModel->inventories->first()->quantity);

        /**
         * Default subtract is 1.
         */
        $this->inventoryModel->subtractInventory();
        $this->inventoryModel->refresh();
        $this->assertEquals(0, $this->inventoryModel->inventories->first()->quantity);
    }

    /** @test */
    public function inventory_subtraction_convert_to_absolute_numbers()
    {
        $this->inventoryModel->setInventory(5);
        $this->assertEquals(5, $this->inventoryModel->inventories->first()->quantity);

        $this->inventoryModel->subtractInventory(-4);
        $this->inventoryModel->refresh();
        $this->assertEquals(1, $this->inventoryModel->inventories->first()->quantity);
    }

    /** @test */
    public function inventory_can_not_be_subtracted_to_negative()
    {
        $this->inventoryModel->setInventory(1);
        $this->assertEquals(1, $this->inventoryModel->inventories->first()->quantity);

        $this->expectExceptionMessage('The inventory quantity is less than `0`, unable to set quantity negative by the amount of: `2`.');

        $this->inventoryModel->subtractInventory(-2);

        $this->inventoryModel->refresh();
        $this->assertEquals(1, $this->inventoryModel->inventories->first()->quantity);
    }

    /** @test */
    public function return_current_inventory()
    {
        $this->assertCount(1, $this->inventoryModel->inventories);

        $this->inventoryModel->setInventory(10);
        $this->inventoryModel->refresh();
        $this->assertEquals(10, $this->inventoryModel->inventories->first()->quantity);

        $this->assertCount(2, $this->inventoryModel->inventories);

        $this->assertEquals(10, $this->inventoryModel->currentInventory()->quantity);
    }

    /** @test */
    public function scope_to_find_where_inventory_is_the_parameters_passed_to_the_scope()
    {
        $this->assertEquals(0, $this->inventoryModel->inventories->first()->quantity);
        $this->assertEquals($this->inventoryModel->id, InventoryModel::InventoryIs(0)->get()->first()->id);

        $this->inventoryModel->setInventory(10);
        $this->inventoryModel->refresh();

        $this->assertEquals(10, $this->inventoryModel->inventories->first()->quantity);
        $this->assertEquals($this->inventoryModel->id, InventoryModel::InventoryIs(9, '>')->get()->first()->id);

        $this->assertEquals($this->inventoryModel->id, InventoryModel::InventoryIs(9, '>=')->get()->first()->id);

        $this->assertNull(InventoryModel::InventoryIs(9, '<')->get()->first());
        $this->assertNull(InventoryModel::InventoryIs(9, '<=')->get()->first());
    }

    /** @test */
    public function scope_to_find_where_inventory_is_the_opposite_then_parameters_passed_to_the_scope()
    {
        $this->assertEquals(0, $this->inventoryModel->inventories->first()->quantity);

        $this->assertEquals($this->inventoryModel->id, InventoryModel::InventoryIs(0)->get()->first()->id);

        $this->assertEquals($this->inventoryModel->id, InventoryModel::InventoryIsNot(1)->get()->first()->id);
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
}
