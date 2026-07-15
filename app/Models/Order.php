<?php

namespace App\Models;

use App\Models\Concerns\UsesUtcDatabaseTimestamps;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use UsesUtcDatabaseTimestamps;

    public const STATUS_PROCESSING = 'Processing';

    public const STATUS_READY = 'Ready';

    public const STATUS_COMPLETED = 'Completed';

    public const STATUS_CANCELLED = 'Cancelled';

    public const DEFAULT_STATUS = self::STATUS_PROCESSING;

    public const STATUSES = [
        self::STATUS_PROCESSING,
        self::STATUS_READY,
        self::STATUS_COMPLETED,
        self::STATUS_CANCELLED,
    ];

    protected $fillable = ['order_number', 'mobile_reference', 'customer_id', 'user_id', 'student_id', 'status', 'subtotal', 'tax', 'discount', 'total_amount', 'payment_status', 'notes'];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
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
