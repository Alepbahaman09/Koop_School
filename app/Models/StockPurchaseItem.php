<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockPurchaseItem extends Model
{
    protected $fillable = ['stock_purchase_id', 'product_id', 'quantity', 'purchase_price', 'selling_price', 'subtotal', 'purchase_unit', 'units_per_purchase'];

    protected $casts = [
        'quantity'       => 'integer',
        'purchase_price'    => 'decimal:2',
        'selling_price'     => 'decimal:2',
        'subtotal'          => 'decimal:2',
        'units_per_purchase'=> 'integer',
    ];

    public function stockPurchase()
    {
        return $this->belongsTo(StockPurchase::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
