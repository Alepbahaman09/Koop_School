<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    protected $fillable = [
    'company_name', 
    'contact_person',
    'email', 
    'phone', 
    'address',
    'notes',
    'status',
    ];

    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function stockPurchases()
    {
        return $this->hasMany(StockPurchase::class);
    }
}
