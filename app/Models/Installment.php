<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;
use App\Enums\InstallmentStatusEnum;
use App\Helpers\MailHelper;
use Illuminate\Support\Facades\Log;

class Installment extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'amount',
        'status',
        'paid_date',
        'paid_amount',
        'notes',
    ];

    protected $casts = [
        'paid_date' => 'date',
        'amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'status' => InstallmentStatusEnum::class,
    ];

    /**
     * Get the student that owns the payment.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Scope to get only pending payments.
     */
    public function scopePending($query)
    {
        return $query->where('status', InstallmentStatusEnum::Pending);
    }

    /**
     * Scope to get only paid payments.
     */
    public function scopePaid($query)
    {
        return $query->where('status', InstallmentStatusEnum::Paid);
    }

    /**
     * Mark payment as paid.
     */
    public function markAsPaid(float $paidAmount = null, string $notes = null): void
    {
        $currentPaymentAmount = $paidAmount ?? $this->amount;

        $this->update([
            'status' => InstallmentStatusEnum::Paid,
            'paid_date' => now(),
            'paid_amount' => $currentPaymentAmount,
            'notes' => $notes,
        ]);

        // Send payment notification email
        $this->sendPaymentNotification($currentPaymentAmount);
    }

    /**
     * Get remaining amount for this payment.
     */
    public function getRemainingAmount(): float
    {
        $paidAmount = $this->paid_amount ?? 0;
        return max(0, $this->amount - $paidAmount);
    }

    /**
     * Check if payment is fully paid.
     */
    public function isFullyPaid(): bool
    {
        return $this->status->isPaid();
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
     * Check if payment is paid.
     */
    public function isPaid(): bool
    {
        return $this->status->isPaid();
    }

    /**
     * Check if payment is pending.
     */
    public function isPending(): bool
    {
        return $this->status->isPending();
    }

    /**
     * Send payment notification email
     */
    private function sendPaymentNotification(float $currentPaymentAmount): void
    {
        try {
            $student = $this->student;

            // Calculate total paid from all payments + down payment
            $totalPaidFromPayments = $student->installments->sum('paid_amount');
            $downPayment = $student->down_payment ?? 0;
            $totalPaid = $totalPaidFromPayments + $downPayment;

            // Use course_fees as total fees
            $totalFees = $student->course_fees;
            $balanceAmount = max(0, $totalFees - $totalPaid);

            // Prepare data for the email template
            $data = [
                'student' => $student,
                'amount' => $currentPaymentAmount,
                'amount_in_words' => numberToWords($currentPaymentAmount),
                'payment_type' => 'full',
                'total_fees' => $totalFees,
                'previous_paid' => $totalPaid - $currentPaymentAmount,
                'current_payment' => $currentPaymentAmount,
                'total_paid_after' => $totalPaid,
                'balance_amount' => $balanceAmount,
            ];

            // Generate email body using the payment receipt template
            $body = view('mail.notification.installment.payment', $data)->render();
            $subject = 'Payment Receipt - ' . $student->tiitvt_reg_no;

            // Use existing MailHelper
            MailHelper::sendNotification($student->email, $subject, $body);
        } catch (\Exception $e) {
            Log::channel('mail')->error("Failed to send payment notification for payment {$this->id}: " . $e->getMessage());
        }
    }
}
