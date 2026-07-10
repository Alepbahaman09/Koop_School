<?php

namespace App\Models;

use App\Models\Concerns\UsesUtcDatabaseTimestamps;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use UsesUtcDatabaseTimestamps;

    protected $fillable = ['order_number', 'mobile_reference', 'customer_id', 'user_id', 'status', 'subtotal', 'tax', 'discount', 'total_amount', 'payment_status', 'notes'];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function statusHistory()
    {
        return $this->hasMany(OrderStatusHistory::class);
    }
}
