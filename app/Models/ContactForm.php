<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactForm extends Model
{
    protected $fillable = [
        'name',
        'email',
        'phone',
        'message',
        'status',
        'ip_address',
        'marked_as_read',
        'read_at',
        'read_by',
    ];

    protected $casts = [
        'status' => 'string',
        'marked_as_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    public function readBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'read_by');
    }
}
