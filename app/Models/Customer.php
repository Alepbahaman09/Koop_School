<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = ['student_id', 'parent_name', 'student_name', 'email', 'phone', 'class', 'address', 'latitude', 'longitude', 'is_active'];

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function getTotalOrdersAttribute()
    {
        return $this->attributes['orders_count'] ?? ($this->relationLoaded('orders') ? $this->orders->count() : 0);
    }

    public function getTotalSpentAttribute()
    {
        return $this->attributes['total_spent'] ?? ($this->relationLoaded('orders') ? $this->orders->where('payment_status', 'Paid')->sum('total_amount') : 0);
    }

    public function getLastOrderDateAttribute()
    {
        return $this->relationLoaded('orders') ? $this->orders->sortByDesc('created_at')->first()?->created_at : null;
    }
}
