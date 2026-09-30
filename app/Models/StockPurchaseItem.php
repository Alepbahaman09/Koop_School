<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class StockPurchaseItem extends Model
{
    protected $fillable = [
        'stock_purchase_id',
        'product_id',
        'item_name',
        'item_sku',
        'item_image',
        'quantity',
        'purchase_price',
        'selling_price',
        'subtotal',
        'purchase_unit',
        'units_per_purchase',
    ];

    protected $casts = [
        'quantity'            => 'integer',
        'purchase_price'      => 'decimal:2',
        'selling_price'       => 'decimal:2',
        'subtotal'            => 'decimal:2',
        'units_per_purchase'  => 'integer',
    ];

    public function getImageUrlAttribute(): ?string
    {
        $image = $this->item_image ?: $this->product?->image;
        if (! $image) {
            return null;
        }

        if (str_starts_with($image, 'http://') || str_starts_with($image, 'https://')) {
            return $image;
        }

        return Storage::disk('public')->url(ltrim(str_replace('storage/', '', $image), '/'));
    }

    public function stockPurchase()
    {
        return $this->belongsTo(StockPurchase::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
