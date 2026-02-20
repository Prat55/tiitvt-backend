<?php

namespace App\Services;

use App\Models\Student;
use App\Helpers\EmailNotificationHelper;
use App\Enums\InstallmentStatusEnum;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class OverdueInstallmentService
{
    /**
     * Handle outstanding balance reminders for students enrolled more than 90 days ago.
     * Since we no longer track overdue status, this sends urgent reminders for
     * students with outstanding balances well past enrollment.
     *
     * @return array
     */
    public function handleOverdueInstallments(): array
    {
        $remindersSent = 0;

        // Find students with outstanding balances enrolled > 90 days ago
        $students = $this->getStudentsWithLongOutstandingBalance();

        foreach ($students as $student) {
            $this->sendOutstandingBalanceReminder($student);
            $remindersSent++;
        }

        return [
            'status_updates' => 0, // No more overdue status updates
            'reminders_sent' => $remindersSent,
        ];
    }

    /**
     * Get students with outstanding balances who enrolled more than 90 days ago.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private function getStudentsWithLongOutstandingBalance()
    {
        $cutoffDate = Carbon::now()->subDays(90)->startOfDay();

        return Student::with(['installments'])
            ->whereNotNull('enrollment_date')
            ->whereNotNull('course_fees')
            ->where('course_fees', '>', 0)
            ->where('enrollment_date', '<=', $cutoffDate)
            ->get()
            ->filter(function ($student) {
                $totalPaid = ($student->down_payment ?? 0) +
                    $student->installments->where('status', InstallmentStatusEnum::Paid)->sum('paid_amount');
                return $totalPaid < $student->course_fees;
            });
    }

    /**
     * Send outstanding balance reminder email.
     *
     * @param Student $student
     * @return void
     */
    private function sendOutstandingBalanceReminder(Student $student): void
    {
        try {
            if (!$student->email) {
                Log::warning("Cannot send outstanding balance reminder: Email not found for student {$student->id}");
                return;
            }

            $totalPaid = ($student->down_payment ?? 0) +
                $student->installments->where('status', InstallmentStatusEnum::Paid)->sum('paid_amount');
            $remainingBalance = max(0, $student->course_fees - $totalPaid);
            $daysSinceEnrollment = Carbon::parse($student->enrollment_date)->diffInDays(Carbon::now());

            $data = [
                'student' => $student,
                'daysSinceEnrollment' => $daysSinceEnrollment,
                'enrollmentDate' => $student->enrollment_date->format('d/m/Y'),
                'totalFees' => number_format($student->course_fees, 2),
                'totalPaid' => number_format($totalPaid, 2),
                'remainingBalance' => number_format($remainingBalance, 2),
                'urgencyText' => 'URGENT',
            ];

            $options = [
                'queue' => false, // Send immediately (high priority)
                'subject_prefix' => 'URGENT: ',
            ];

            $result = EmailNotificationHelper::sendNotificationByType(
                'overdue_notification',
                $student->email,
                $data,
                $options
            );

            if ($result) {
                Log::info("Outstanding balance reminder sent to {$student->email} ({$daysSinceEnrollment} days since enrollment, balance: ₹{$remainingBalance})");
            } else {
                Log::warning("Failed to send outstanding balance reminder to {$student->email}");
            }
        } catch (\Exception $e) {
            Log::error("Failed to send outstanding balance reminder: " . $e->getMessage(), [
                'student_id' => $student->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
