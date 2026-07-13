<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HomeBanner extends Model
{
    public const IMAGE_BUCKET = 'home-banner-images';

    protected $fillable = [
        'title',
        'message',
        'label',
        'tone',
        'image_url',
        'is_active',
        'expires_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'expires_at' => 'datetime',
    ];
}
