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
     * @return Inventory|null
     */
    public function currentInventory(): Inventory|null
    {
        return $this->relationLoaded('inventories') ? $this->inventories->first() : $this->latestInventory;
    }

    /**
     * @return Inventory|null
     */
    public function inventory(): Inventory|null
    {
        return $this->currentInventory();
    }

    /**
     * @return bool
     */
    public function hasValidInventory(): bool
    {
        return  (bool) $this->currentInventory();
    }

    /**
     * @param  int  $quantity
     * @return bool
     */
    public function inInventory(?int $quantity = 1): bool
    {
        if ($this->notInInventory()) {
            return false;
        }

        if ($this->currentInventory()->quantity < $quantity) {
            return false;
        }

        return true;
    }

    /**
     * @return bool
     */
    public function notInInventory(): bool
    {
        if (! $this->hasValidInventory()) {
            return true;
        }

        return $this->currentInventory()->quantity <= 0;
    }

    /**
     * Create or update model inventory.
     *
     * @param  int  $quantity
     * @param  string  $description
     * @return Inventory
     */
    public function setInventory(int $quantity, ?string $description = null): Inventory
    {
        $this->isValidInventory($quantity, $description);

        return $this->createInventory($quantity, $description);
    }

    /**
     * @param  int  $quantity
     * @param  string  $description
     * @return bool
     */
    public function incrementInventory(int $quantity = 1, ?string $description = null): Inventory
    {
        return $this->addInventory($quantity, $description);
    }

    /**
     * Add or create an inventory.
     *
     * @param  int  $addQuantity
     * @param  string  $description
     * @return Inventory
     */
    public function addInventory(int $addQuantity = 1, ?string $description = null): Inventory
    {
        $this->isValidInventory($addQuantity, $description);

        if ($this->notInInventory()) {
            return $this->createInventory($addQuantity, $description);
        }

        $newQuantity = $this->currentInventory()->quantity + $addQuantity;

        return $this->createInventory($newQuantity, $description);
    }

    /**
     * @param  int  $quantity
     * @param  string  $description
     * @return bool
     */
    public function decrementInventory(int $quantity = 1, ?string $description = null): Inventory
    {
        return $this->subtractInventory($quantity, $description);
    }

    /**
     * Subtract a given amount from the model inventory.
     *
     * @param  int  $subtractQuantity
     * @param  string  $description
     * @return Inventory
     */
    public function subtractInventory(int $subtractQuantity = 1, ?string $description = null): Inventory
    {
        $subtractQuantity = abs($subtractQuantity);

        $this->isValidInventory($subtractQuantity, $description);

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
     * @param  int|null  $newStock
     * @return Inventory|bool
     */
    public function clearInventory(?int $newStock = -1): Inventory|bool
    {
        $this->inventories()->delete();

        // Will return new Inventory instance of new inventory has been set
        return $newStock >= 0 ? $this->setInventory($newStock) : true;
    }

    /**
     * @throws InvalidInventory
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
    public function scopeInventoryIs(Builder $builder, $quantity = 0, string $operator = '=', array ...$inventoriableId): void
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
    public function scopeInventoryIsNot(Builder $builder, int $quantity = 0, array ...$inventoriableId): void
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
     * @return string
     */
    protected function getInventoryModelType(): string
    {
        return array_search(static::class, Relation::morphMap()) ?: static::class;
    }

    /**
     * @return string
     */
    protected function getModelKeyColumnName(): string
    {
        return config('laravel-inventory.model_primary_field_attribute') ?? 'inventoriable_id';
    }

    /**
     * @return string
     */
    protected function getInventoryModelClassName(): string
    {
        return config('laravel-inventory.inventory_model');
    }
}
