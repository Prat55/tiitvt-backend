<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class Installment extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'installment_no',
        'amount',
        'due_date',
        'status',
        'paid_date',
        'paid_amount',
        'notes',
    ];

    protected $casts = [
        'due_date' => 'date',
        'paid_date' => 'date',
        'amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
    ];

    /**
     * Get the student that owns the installment.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Scope to get only pending installments.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to get only paid installments.
     */
    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    /**
     * Scope to get only overdue installments.
     */
    public function scopeOverdue($query)
    {
        return $query->where('status', 'overdue');
    }

    /**
     * Check if installment is overdue.
     */
    public function isOverdue(): bool
    {
        return $this->status === 'pending' && $this->due_date->isPast();
    }

    /**
     * Mark installment as paid.
     */
    public function markAsPaid(float $paidAmount = null, string $notes = null): void
    {
        $this->update([
            'status' => 'paid',
            'paid_date' => now(),
            'paid_amount' => $paidAmount ?? $this->amount,
            'notes' => $notes,
        ]);
    }

    /**
     * Mark installment as overdue.
     */
    public function markAsOverdue(): void
    {
        if ($this->status === 'pending') {
            $this->update(['status' => 'overdue']);
        }
    }

    /**
     * Get the formatted due date.
     */
    public function getFormattedDueDateAttribute(): string
    {
        return $this->due_date->format('d/m/Y');
    }

    /**
     * Get the formatted paid date.
     */
    public function getFormattedPaidDateAttribute(): string
    {
        return $this->paid_date ? $this->paid_date->format('d/m/Y') : 'Not paid';
    }

    /**
     * Get the status badge class.
     */
    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            'paid' => 'badge-success',
            'overdue' => 'badge-error',
            'pending' => 'badge-warning',
            default => 'badge-neutral',
        };
    }
}
