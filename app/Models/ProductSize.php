<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductSize extends Model
{
    public const AVAILABLE_SIZES = [
        'XS', 'S', 'M', 'L', 'XL', '2XL', '3XL', '4XL',
    ];

    protected $fillable = ['product_id', 'size', 'stock_quantity'];

    protected function casts(): array
    {
        return [
            'stock_quantity' => 'integer',
        ];
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
