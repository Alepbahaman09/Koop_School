<?php

namespace App\Models;

use App\Models\Concerns\UsesUtcDatabaseTimestamps;
use Illuminate\Database\Eloquent\Model;

class TerminalPayment extends Model
{
    use UsesUtcDatabaseTimestamps;

    protected $table = 'terminal_payments';

    protected $fillable = ['order_id', 'user_id', 'card_id', 'payment_reference', 'payment_method', 'amount', 'status', 'paid_at', 'notes'];

    protected $casts = ['paid_at' => 'datetime'];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function card()
    {
        return $this->belongsTo(Card::class);
    }
}
