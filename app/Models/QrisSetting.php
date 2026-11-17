<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QrisSetting extends Model
{
    protected $fillable = [
        'qris_image',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}