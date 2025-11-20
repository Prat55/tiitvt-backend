<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccessControlTrigger extends Model
{
    use HasFactory;

    protected $fillable = [
        'url',
        'ip_address',
        'parameters',
        'access_value',
    ];

    protected $casts = [
        'parameters' => 'array',
        'access_value' => 'boolean',
    ];
}
