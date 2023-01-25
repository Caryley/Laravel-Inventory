<?php

declare(strict_types=1);

namespace Caryley\LaravelInventory;

use Caryley\LaravelInventory\Events\InventoryUpdate;
use Caryley\LaravelInventory\Exeptions\InvalidInventory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

trait HasInventory
{
    /**
     * Get inventory of the model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function inventories(): MorphMany
    {
        return $this->morphMany($this->getInventoryModelClassName(), 'inventoriable')->latest('id');
    }

    /**
     * Return the current inventory on the model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function latestInventory(): MorphOne
    {
        return $this->morphOne($this->getInventoryModelClassName(), 'inventoriable')->latestOfMany();
    }


    /**
     * Return the current inventory on the model.
     *
     * @return \Caryley\LaravelInventory\Inventory|null
     */
    public function currentInventory(): ?Inventory
    {
        return $this->relationLoaded('inventories') ? $this->inventories->first() : $this->latestInventory;
    }

    /**
     * Return the current inventory on the model.
     *
     * @return \Caryley\LaravelInventory\Inventory|null
     */
    public function inventory(): ?Inventory
    {
        return $this->currentInventory();
    }

    /**
     * Checks if a model has a valid Inventory.
     *
     * @return bool
     */
    public function hasValidInventory(): bool
    {
        return $this->inventories()->get()->isNotEmpty();
    }

    /**
     * Determine if a given quantity is in inventory on the model.
     *
     * @param  int  $quantity
     * @return bool
     */
    public function inInventory(?int $quantity = 1): bool
    {
        if (! $this->notInInventory() && $this->currentInventory()->quantity > 0 && $this->currentInventory()->quantity >= $quantity) {
            return true;
        }

        return false;
    }

    /**
     * Determine if the model is not in inventory.
     *
     * @return bool
     */
    public function notInInventory(): bool
    {
        if (! isset($this->currentInventory()->quantity)) {
            return true;
        }

        return $this->currentInventory()->quantity <= 0;
    }

    /**
     * Create or update model inventory.
     *
     * @param  int  $quantity
     * @param  string  $description
     * @return \Caryley\LaravelInventory\Inventory
     */
    public function setInventory(int $quantity, ?string $description = null): Inventory
    {
        if (! $this->isValidInventory($quantity, $description)) {
            throw InvalidInventory::value($quantity);
        }

        return $this->createInventory($quantity, $description);
    }

    /**
     * Add or create an inventory.
     *
     * @param  int  $addQuantity
     * @param  string  $description
     * @return \Caryley\LaravelInventory\Inventory
     */
    public function addInventory(int $addQuantity = 1, ?string $description = null): Inventory
    {
        if (! $this->isValidInventory($addQuantity, $description)) {
            throw InvalidInventory::value($quantity);
        }

        if ($this->notInInventory()) {
            return $this->createInventory($addQuantity, $description);
        }

        $newQuantity = $this->currentInventory()->quantity + $addQuantity;

        return $this->createInventory($newQuantity, $description);
    }

    /**
     * Subtract a given amount from the model inventory.
     *
     * @param  int  $subtractQuantity
     * @param  string  $description
     * @return \Caryley\LaravelInventory\Inventory
     */
    public function subtractInventory(int $subtractQuantity = 1, ?string $description = null): Inventory
    {
        $subtractQuantity = abs($subtractQuantity);

        if (! $this->isValidInventory($subtractQuantity, $description)) {
            throw InvalidInventory::value($quantity);
        }

        if ($this->notInInventory()) {
            throw InvalidInventory::subtract($subtractQuantity);
        }

        $newQuantity = $this->currentInventory()->quantity - abs($subtractQuantity);

        if ($newQuantity < 0) {
            throw InvalidInventory::negative($subtractQuantity);
        }

        return $this->createInventory($newQuantity, $description);
    }

    /**
     * Create a new inventory.
     *
     * @param  int  $quantity
     * @param  string  $description
     * @return \Caryley\LaravelInventory\Inventory
     */
    protected function createInventory(int $quantity, ?string $description = null): Inventory
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
     * Delete the inventory from the model.
     *
     * @param  int|null  $newStock  (optional passing an int to delete all inventory and create new one)
     * @return \Caryley\LaravelInventory\Inventory|bool (if new inventory has been created upon receiving new quantity)
     */
    public function clearInventory($newStock = -1): Inventory|bool
    {
        $this->inventories()->delete();

        return $newStock >= 0 ? $this->setInventory($newStock) : true;
    }

    /**
     * Check if given quantity is a valid int and description is a valid string.
     *
     * @param  int  $quantity
     * @param  string  $description
     * @return bool|InvalidInventory
     */
    protected function isValidInventory(int $quantity, ?string $description = null): ?bool
    {
        if (gmp_sign($quantity) === -1) {
            throw InvalidInventory::value($quantity);
        }

        return true;
    }

    /**
     * Scope inventory model for a givin quantity and operatior.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @param  int  $quantity
     * @param  string  $operator  (<,>,<=,>=,=,<>)
     * @param  array  $inventoriableId
     * @return void
     */
    public function scopeInventoryIs(Builder $builder, $quantity = 0, $operator = '=', ...$inventoriableId): void
    {
        $inventoriableId = is_array($inventoriableId) ? Arr::flatten($inventoriableId) : func_get_args();

        $builder->whereHas('inventories', function (Builder $query) use ($operator, $quantity, $inventoriableId) {
            $query->when($inventoriableId, function ($query, $inventoriableId) {
                return $query->whereIn($this->getModelKeyColumnName(), $inventoriableId);
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
     * @param  int  $quantity
     * @param  array  $inventoriableId
     * @return void
     */
    public function scopeInventoryIsNot(Builder $builder, $quantity = 0, ...$inventoriableId): void
    {
        $inventoriableId = is_array($inventoriableId) ? Arr::flatten($inventoriableId) : func_get_args();

        $builder->whereHas('inventories', function (Builder $query) use ($quantity, $inventoriableId) {
            $query->when($inventoriableId, function ($query, $inventoriableId) {
                return $query->whereIn($this->getModelKeyColumnName(), $inventoriableId);
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
    protected function getInventoryTableName(): string
    {
        $modelClass = $this->getInventoryModelClassName();

        return (new $modelClass)->getTable();
    }

    /**
     * Return the inventory model uses the trait.
     *
     * @return string
     */
    protected function getInventoryModelType(): string
    {
        return array_search(static::class, Relation::morphMap()) ?: static::class;
    }

    /**
     * Return the model key column name set on config file.
     *
     * @return string
     */
    protected function getModelKeyColumnName(): string
    {
        return config('laravel-inventory.model_primary_field_attribute') ?? 'inventoriable_id';
    }

    /**
     * Return the model class name set on config file, uses to verify model is extending to \Caryley\LaravelInventory\Inventory class.
     *
     * @return string
     */
    protected function getInventoryModelClassName(): string
    {
        return config('laravel-inventory.inventory_model');
    }
}