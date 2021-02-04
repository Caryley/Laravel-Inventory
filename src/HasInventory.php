<?php

namespace Caryley\LaravelInventory;

use Caryley\LaravelInventory\Events\InventoryUpdate;
use Caryley\LaravelInventory\Exeptions\InvalidInventory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

trait HasInventory
{
    /**
     * Get inventory of the model.
     *
     * @return  \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function inventories()
    {
        return $this->morphMany($this->getInventoryModelClassName(), 'inventoriable')->latest('id');
    }

    /**
     * Return the last inventory instance of the model.
     *
     * @return  \Illuminate\Database\Eloquent\Relations\Relation
     */
    public function inventory()
    {
        return $this->currentInventory();
    }

    /**
     * Determine if a given quantity is in inventory on the model.
     *
     * @param  string $quantity
     * @return bool
     */
    public function inInventory($quantity = 1)
    {
        return $this->inventories->first()->quantity > 0 && $this->inventories->first()->quantity >= $quantity;
    }

    /**
     * Determine if the model is not in inventory.
     *
     * @return bool
     */
    public function notInInventory()
    {
        return $this->inventories->first()->quantity <= 0;
    }

    /**
     * Create or update model inventory.
     *
     * @param  int $quantity
     * @param  string $description
     * @return \Caryley\LaravelInventory\Inventory
     */
    public function setInventory(int $quantity, ?string $description = null)
    {
        if (! $this->isValidQuantity($quantity, $description)) {
            throw InvalidInventory::value($quantity);
        }

        return $this->createInventory($quantity, $description);
    }

    /**
     * Add or create an inventory.
     *
     * @param  int $addQuantity
     * @param  string $description
     * @return \Caryley\LaravelInventory\Inventory
     */
    public function addInventory(int $addQuantity = 1, ?string $description = null)
    {
        if (! $this->isValidQuantity($addQuantity, $description)) {
            throw InvalidInventory::value($quantity);
        }

        if (! isset($this->inventories->first()->quantity)) {
            return $this->createInventory($addQuantity, $description);
        }

        $newQuantity = $this->inventories->first()->quantity + $addQuantity;

        return $this->createInventory($newQuantity, $description);
    }

    /**
     * Subtract a given amount from the model inventory.
     *
     * @param  int $subtractQuantity
     * @param  string $description
     * @return \Caryley\LaravelInventory\Inventory
     */
    public function subtractInventory(int $subtractQuantity = 1, ?string $description = null)
    {
        $subtractQuantity = abs($subtractQuantity);

        if (! $this->isValidQuantity($subtractQuantity, $description)) {
            throw InvalidInventory::value($quantity);
        }

        if ($this->notInInventory()) {
            throw InvalidInventory::subtract($subtractQuantity);
        }

        $newQuantity = $this->inventories->first()->quantity - abs($subtractQuantity);

        if ($newQuantity < 0) {
            throw InvalidInventory::negative($subtractQuantity);
        }

        return $this->createInventory($newQuantity, $description);
    }

    /**
     * Check if given quantity is a valid int and description is a valid string.
     *
     * @param  int $quantity
     * @param  string $description
     * @return bool
     */
    public function isValidQuantity(int $quantity, ?string $description = null)
    {
        if (gmp_sign($quantity) == -1) {
            throw InvalidInventory::value($quantity);
        }

        return true;
    }

    /**
     * Create a new inventory.
     *
     * @param  int $quantity
     * @param  string $description
     * @return \Caryley\LaravelInventory\Inventory
     */
    public function createInventory(int $quantity, ?string $description = null)
    {
        $oldInventory = $this->currentInventory();

        $newInventory = $this->inventories()->create([
            'quantity' => abs($quantity),
            'description' => $description,
        ]);

        event(new InventoryUpdate($oldInventory, $newInventory, $this));

        return $newInventory;
    }

    /**
     * Return the current inventory on the model.
     *
     * @return \Caryley\LaravelInventory\Inventory
     */
    public function currentInventory()
    {
        $inventories = $this->relationLoaded('inventories') ? $this->inventories : $this->inventories();

        return $inventories->first();
    }

    /**
     * Delete the inventory from the model.
     *
     * @param  int|null $newStock (optional passing an int to delete all inventory and create new one)
     * @return \Caryley\LaravelInventory\Inventory (if new inventory has been created upon receiving new quantity)
     */
    public function clearInventory($newStock = -1)
    {
        $this->inventories()->delete();

        return $newStock >= 0 ? $this->setInventory($newStock) : true;
    }

    /**
     * Scope inventory model for a givin quantity and operatior.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $builder
     * @param  int $quantity
     * @param  string $operator (<,>,<=,>=,=,<>)
     * @param  array $inventoriableId
     * @return \Illuminate\Database\Query\Builder
     */
    public function scopeInventoryIs(Builder $builder, $quantity = 0, $operator = '=', ...$inventoriableId)
    {
        $inventoriableId = is_array($inventoriableId) ? Arr::flatten($inventoriableId) : func_get_args();

        $builder->whereHas('inventories', function (Builder $query) use ($operator, $quantity, $inventoriableId) {
            $query->when($inventoriableId, function ($query, $inventoriableId) {
                return $query->whereIn('inventoriable_id', $inventoriableId);
            })->where('quantity', $operator, $quantity)->whereIn('id', function (QueryBuilder $query) {
                $query->select(DB::raw('max(id)'))
                        ->from($this->getInventoryTableName())
                        ->where('inventoriable_type', $this->getInventoryModelType())
                        ->whereColumn($this->getModelKeyColumnName(), $this->getQualifiedKeyName());
            });
        });
    }

    /**
     * Scope inventory model to everything other than given quantity.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @param  int $quantity
     * @param  array $inventoriableId
     * @return \Illuminate\Database\Query\Builder
     */
    public function scopeInventoryIsNot(Builder $builder, $quantity = 0, ...$inventoriableId)
    {
        $inventoriableId = is_array($inventoriableId) ? Arr::flatten($inventoriableId) : func_get_args();

        $builder->whereHas('inventories', function (Builder $query) use ($quantity, $inventoriableId) {
            $query->when($inventoriableId, function ($query, $inventoriableId) {
                return $query->whereIn('inventoriable_id', $inventoriableId);
            })->where('quantity', '<>', $quantity)->whereIn('id', function (QueryBuilder $query) {
                $query->select(DB::raw('max(id)'))
                        ->from($this->getInventoryTableName())
                        ->where('inventoriable_type', $this->getInventoryModelType())
                        ->whereColumn($this->getModelKeyColumnName(), $this->getQualifiedKeyName());
            });
        });
    }

    /**
     * Return the table name for the inventory model.
     *
     * @return string
     */
    protected function getInventoryTableName()
    {
        $modelClass = $this->getInventoryModelClassName();

        return (new $modelClass)->getTable();
    }

    /**
     * Return the inventory model uses the trait.
     *
     * @return string
     */
    protected function getInventoryModelType()
    {
        return array_search(static::class, Relation::morphMap()) ?: static::class;
    }

    /**
     * Return the model key column name set on config file.
     *
     * @return string
     */
    protected function getModelKeyColumnName()
    {
        return config('laravel-inventory.model_primary_field_attribute') ?? 'inventoriable_id';
    }

    /**
     * Return the model class name set on config file, uses to verify model is extending to \Caryley\LaravelInventory\Inventory class.
     *
     * @return void
     */
    protected function getInventoryModelClassName()
    {
        return config('laravel-inventory.inventory_model');
    }
}
