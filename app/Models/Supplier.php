<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    protected $fillable = [
        'supplier_id',
        'company_name',
        'contact_person',
        'email',
        'phone',
        'address',
    ];

    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function stockPurchases()
    {
        return $this->hasMany(StockPurchase::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($supplier) {
            $lastSupplier = self::latest('id')->first();

            $number = $lastSupplier
                ? (int) substr($lastSupplier->supplier_id, 3) + 1
                : 1;

            $supplier->supplier_id = 'SUP'.str_pad($number, 4, '0', STR_PAD_LEFT);
        });
    }
}
