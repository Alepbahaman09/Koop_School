<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    protected $fillable = ['category_id', 'sku', 'name', 'description', 'price', 'cost_price', 'unit', 'purchase_unit', 'units_per_carton', 'stock_quantity', 'min_stock_level', 'image'];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'cost_price' => 'decimal:2',
            'stock_quantity' => 'integer',
            'min_stock_level' => 'integer',
        ];
    }


    public function getImageUrlAttribute(): ?string
    {
        if (! $this->image) {
            return null;
        }

        if (str_starts_with($this->image, 'http://') || str_starts_with($this->image, 'https://')) {
            return $this->image;
        }

        return Storage::disk('public')->url(ltrim(str_replace('storage/', '', $this->image), '/'));
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function inventoryTransactions()
    {
        return $this->hasMany(InventoryTransaction::class);
    }

    public function stockPurchaseItems()
    {
        return $this->hasMany(StockPurchaseItem::class);
    }

    public function sizes()
    {
        return $this->hasMany(ProductSize::class)->orderBy('id');
    }
}
