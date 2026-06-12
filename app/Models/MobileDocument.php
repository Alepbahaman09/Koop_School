<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MobileDocument extends Model
{
    protected $fillable = ['path', 'collection_path', 'document_id', 'data'];

    protected function casts(): array
    {
        return ['data' => 'array'];
    }
}
