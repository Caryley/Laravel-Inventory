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
    public function inventories()
    {
        return $this->morphMany($this->getInventoryModelClassName(), 'inventoriable')->latest('id');
    }

    public function inventory()
    {
        return $this->currentInventory();
    }

    public function inInventory($quantity = 1)
    {
        return $this->inventories->first()->quantity > 0 && $this->inventories->first()->quantity >= $quantity;
    }

    public function notInInventory()
    {
        return $this->inventories->first()->quantity <= 0;
    }

    public function setInventory(int $quantity, ?string $description = null)
    {
        if (! $this->isValidQuantity($quantity, $description)) {
            throw InvalidInventory::value($quantity);
        }

        return $this->createInventory($quantity, $description);
    }

    public function addInventory(int $addQuantity = 1, ?string $description = null)
    {
        if (! $this->isValidQuantity($addQuantity, $description)) {
            throw InvalidInventory::value($quantity);
        }

        $newQuantity = $this->inventories->first()->quantity + $addQuantity;

        return $this->createInventory($newQuantity, $description);
    }

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

    public function isValidQuantity(int $quantity, ?string $description = null)
    {
        if (gmp_sign($quantity) == -1) {
            throw InvalidInventory::value($quantity);
        }

        return true;
    }

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

    public function currentInventory()
    {
        $inventories = $this->relationLoaded('inventories') ? $this->inventories : $this->inventories();

        return $inventories->first();
    }

    public function clearInventory($newStock = -1)
    {
        $this->inventories()->delete();

        return $newStock >= 0 ? $this->setInventory($newStock) : true;
    }

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

    protected function getInventoryTableName()
    {
        $modelClass = $this->getInventoryModelClassName();

        return (new $modelClass)->getTable();
    }

    protected function getInventoryModelType()
    {
        return array_search(static::class, Relation::morphMap()) ?: static::class;
    }

    protected function getModelKeyColumnName()
    {
        return config('laravel-inventory.model_primary_field_attribute') ?? 'inventoriable_id';
    }

    protected function getInventoryModelClassName()
    {
        return config('laravel-inventory.inventory_model');
    }
}
