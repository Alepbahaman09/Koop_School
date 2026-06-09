<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    protected $fillable = ['po_number', 'supplier_id', 'user_id', 'status', 'total_amount', 'order_date', 'expected_delivery_date', 'received_date', 'notes'];

    protected $casts = ['order_date' => 'date', 'expected_delivery_date' => 'date', 'received_date' => 'date'];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }
}
