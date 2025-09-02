<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;
use App\Enums\InstallmentStatusEnum;

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
        'status' => InstallmentStatusEnum::class,
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
        return $query->where('status', InstallmentStatusEnum::Pending);
    }

    /**
     * Scope to get only paid installments.
     */
    public function scopePaid($query)
    {
        return $query->where('status', InstallmentStatusEnum::Paid);
    }

    /**
     * Scope to get only overdue installments.
     */
    public function scopeOverdue($query)
    {
        return $query->where('status', InstallmentStatusEnum::Overdue);
    }



    /**
     * Check if installment should be marked as overdue (for display purposes).
     */
    public function shouldBeOverdue(): bool
    {
        // Check if installment is overdue by date regardless of status
        return $this->due_date->isPast() && !$this->status->isPaid();
    }

    /**
     * Get overdue amount for this installment.
     */
    public function getOverdueAmount(): float
    {
        if ($this->shouldBeOverdue()) {
            return $this->status->isPaid() ? 0 : $this->amount;
        }
        return 0;
    }

    /**
     * Mark installment as paid.
     */
    public function markAsPaid(float $paidAmount = null, string $notes = null): void
    {
        $this->update([
            'status' => InstallmentStatusEnum::Paid,
            'paid_date' => now(),
            'paid_amount' => $paidAmount ?? $this->amount,
            'notes' => $notes,
        ]);
    }

    /**
     * Add partial payment to installment.
     */
    public function addPartialPayment(float $partialAmount, string $notes = null): void
    {
        $currentPaidAmount = $this->paid_amount ?? 0;
        $newPaidAmount = $currentPaidAmount + $partialAmount;

        // If the new paid amount equals or exceeds the installment amount, mark as fully paid
        if ($newPaidAmount >= $this->amount) {
            $this->markAsPaid($newPaidAmount, $notes);
        } else {
            // Otherwise, mark as partial
            $this->update([
                'status' => InstallmentStatusEnum::Partial,
                'paid_date' => now(),
                'paid_amount' => $newPaidAmount,
                'notes' => $notes,
            ]);
        }
    }

    /**
     * Get remaining amount for this installment.
     */
    public function getRemainingAmount(): float
    {
        $paidAmount = $this->paid_amount ?? 0;
        return max(0, $this->amount - $paidAmount);
    }

    /**
     * Check if installment is fully paid.
     */
    public function isFullyPaid(): bool
    {
        return $this->status->isPaid() && ($this->paid_amount ?? 0) >= $this->amount;
    }

    /**
     * Mark installment as overdue.
     */
    public function markAsOverdue(): void
    {
        if ($this->status->isPending()) {
            $this->update(['status' => InstallmentStatusEnum::Overdue]);
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
        return $this->status->badgeClass();
    }

    /**
     * Get the status label.
     */
    public function getStatusLabelAttribute(): string
    {
        return $this->status->label();
    }

    /**
     * Check if installment is paid.
     */
    public function isPaid(): bool
    {
        return $this->status->isPaid();
    }

    /**
     * Check if installment is pending.
     */
    public function isPending(): bool
    {
        return $this->status->isPending();
    }

    /**
     * Check if installment is overdue.
     */
    public function isOverdue(): bool
    {
        return $this->status->isOverdue();
    }
}
