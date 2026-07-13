<?php

namespace App\Models;

use App\Models\Concerns\UsesUtcDatabaseTimestamps;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use UsesUtcDatabaseTimestamps;

    protected $fillable = ['student_id', 'parent_name', 'student_name', 'email', 'phone', 'class', 'address', 'latitude', 'longitude', 'is_active'];

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function getTotalOrdersAttribute()
    {
        return $this->orders()->count();
    }

    public function getTotalSpentAttribute()
    {
        return $this->orders()->where('payment_status', 'Paid')->sum('total_amount');
    }

    public function getLastOrderDateAttribute()
    {
        return $this->orders()->latest()->first()?->created_at;
    }
}
