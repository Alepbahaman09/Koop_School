<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Card extends Model
{
    protected $fillable = ['user_id', 'card_uid', 'owner', 'balance', 'is_frozen', 'last_used_at'];

    protected function casts(): array
    {
        return ['balance' => 'decimal:2', 'is_frozen' => 'boolean', 'last_used_at' => 'datetime'];
    }
}
