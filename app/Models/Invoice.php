<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'amount',
        'status',
        'issued_at',
        'paid_at',
        'invoice_number',
        'description',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'status' => 'string',
        'issued_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    /**
     * Get the student that owns the invoice.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Scope to get only unpaid invoices.
     */
    public function scopeUnpaid($query)
    {
        return $query->where('status', 'unpaid');
    }

    /**
     * Scope to get only paid invoices.
     */
    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }
}
