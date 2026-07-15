<?php

namespace App\Models;

use App\Models\Concerns\UsesUtcDatabaseTimestamps;
use Illuminate\Database\Eloquent\Model;

class TerminalPayment extends Model
{
    use UsesUtcDatabaseTimestamps;

    protected $table = 'terminal_payments';

    protected $fillable = ['order_id', 'payment_reference', 'payment_method', 'amount', 'status', 'paid_at', 'notes'];

    protected $casts = ['paid_at' => 'datetime'];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
