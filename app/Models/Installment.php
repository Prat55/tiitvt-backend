<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;
use App\Enums\InstallmentStatusEnum;
use App\Enums\PaymentMethodEnum;
use App\Helpers\MailHelper;
use Illuminate\Support\Facades\Log;

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
        'payment_method',
        'cheque_number',
        'withdrawn_date',
        'notes',
    ];

    protected $casts = [
        'due_date' => 'date',
        'paid_date' => 'date',
        'withdrawn_date' => 'date',
        'amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'status' => InstallmentStatusEnum::class,
        'payment_method' => PaymentMethodEnum::class,
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
    public function markAsPaid(float $paidAmount = null, string $notes = null, PaymentMethodEnum $paymentMethod = null, string $chequeNumber = null, $withdrawnDate = null): void
    {
        $this->update([
            'status' => InstallmentStatusEnum::Paid,
            'paid_date' => now(),
            'paid_amount' => $paidAmount ?? $this->amount,
            'payment_method' => $paymentMethod,
            'cheque_number' => $chequeNumber,
            'withdrawn_date' => $withdrawnDate,
            'notes' => $notes,
        ]);

        // Send payment notification email
        $this->sendPaymentNotification('full');
    }

    /**
     * Add partial payment to installment.
     */
    public function addPartialPayment(float $partialAmount, string $notes = null, PaymentMethodEnum $paymentMethod = null, string $chequeNumber = null, $withdrawnDate = null): void
    {
        $currentPaidAmount = $this->paid_amount ?? 0;
        $newPaidAmount = $currentPaidAmount + $partialAmount;

        // If the new paid amount equals or exceeds the installment amount, mark as fully paid
        if ($newPaidAmount >= $this->amount) {
            $this->markAsPaid($newPaidAmount, $notes, $paymentMethod, $chequeNumber, $withdrawnDate);
        } else {
            // Otherwise, mark as partial
            $this->update([
                'status' => InstallmentStatusEnum::Partial,
                'paid_date' => now(),
                'paid_amount' => $newPaidAmount,
                'payment_method' => $paymentMethod,
                'cheque_number' => $chequeNumber,
                'withdrawn_date' => $withdrawnDate,
                'notes' => $notes,
            ]);

            // Send partial payment notification email
            $this->sendPaymentNotification('partial');
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

    /**
     * Send payment notification email
     */
    private function sendPaymentNotification(string $paymentType = 'full'): void
    {
        try {
            $student = $this->student;

            // Prepare data for the email template
            $data = [
                'student' => $student,
                'amount' => $this->paid_amount ?? $this->amount,
                'amount_in_words' => numberToWords($this->paid_amount ?? $this->amount),
                'payment_type' => $paymentType,
                'payment_method' => $this->payment_method?->value ?? 'cash',
                'cheque_number' => $this->cheque_number,
                'withdrawn_date' => $this->withdrawn_date?->format('Y-m-d'),
                'total_fees' => $student->installments->sum('amount'),
                'previous_paid' => $this->calculatePreviousPaid($student),
                'balance_amount' => $this->calculateBalanceAmount($student),
            ];

            // Generate email body using the payment receipt template
            $body = view('mail.notification.installment.payment', $data)->render();
            $subject = 'Payment Receipt - ' . $student->tiitvt_reg_no;

            // Use existing MailHelper
            MailHelper::sendNotification($student->email, $subject, $body);
        } catch (\Exception $e) {
            Log::error("Failed to send payment notification for installment {$this->id}: " . $e->getMessage());
        }
    }

    /**
     * Calculate previous paid amount (excluding current installment)
     */
    private function calculatePreviousPaid(Student $student): float
    {
        return $student->installments
            ->where('id', '!=', $this->id)
            ->sum('paid_amount');
    }

    /**
     * Calculate balance amount after current payment
     */
    private function calculateBalanceAmount(Student $student): float
    {
        $totalFees = $student->installments->sum('amount');
        $totalPaid = $student->installments->sum('paid_amount');
        return max(0, $totalFees - $totalPaid);
    }
}
