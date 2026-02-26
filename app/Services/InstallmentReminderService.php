<?php

namespace App\Services;

use App\Models\Student;
use App\Helpers\EmailNotificationHelper;
use App\Enums\InstallmentStatusEnum;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class InstallmentReminderService
{
    /**
     * Send payment reminders to students with outstanding balances.
     * Uses enrollment_date to determine reminder schedule.
     *
     * @return int
     */
    public function sendReminders(): int
    {
        $remindersSent = 0;

        // Find students with outstanding balances
        $students = $this->getStudentsWithOutstandingBalance();

        foreach ($students as $student) {
            $daysSinceEnrollment = $this->getDaysSinceEnrollment($student);

            if ($daysSinceEnrollment !== null && $this->shouldSendReminder($student, $daysSinceEnrollment)) {
                $this->sendReminder($student, $daysSinceEnrollment);
                $remindersSent++;
            }
        }

        return $remindersSent;
    }

    /**
     * Get students with outstanding balance (course_fees > total paid).
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private function getStudentsWithOutstandingBalance()
    {
        return Student::with(['installments'])
            ->whereNotNull('enrollment_date')
            ->whereNotNull('course_fees')
            ->where('course_fees', '>', 0)
            ->get()
            ->filter(function ($student) {
                $totalPaid = ($student->down_payment ?? 0) +
                    $student->installments->where('status', InstallmentStatusEnum::Paid)->sum('paid_amount');
                return $totalPaid < $student->course_fees;
            });
    }

    /**
     * Get days since enrollment for a student.
     *
     * @param Student $student
     * @return int|null
     */
    private function getDaysSinceEnrollment(Student $student): ?int
    {
        if (!$student->enrollment_date) {
            return null;
        }

        return Carbon::parse($student->enrollment_date)->diffInDays(Carbon::now());
    }

    /**
     * Check if reminder should be sent for this student based on enrollment date.
     * Sends reminder every month when today's day-of-month matches enrollment day.
     *
     * @param Student $student
     * @param int $daysSinceEnrollment
     * @return bool
     */
    private function shouldSendReminder(Student $student, int $daysSinceEnrollment): bool
    {
        if (!$student->enrollment_date) {
            return false;
        }

        $today = Carbon::now();
        $enrollmentDate = Carbon::parse($student->enrollment_date);

        // Do not send reminder before actual enrollment date.
        if ($enrollmentDate->gt($today)) {
            return false;
        }

        return $enrollmentDate->day === $today->day;
    }

    /**
     * Send reminder email for outstanding balance.
     *
     * @param Student $student
     * @param int $daysSinceEnrollment
     * @return void
     */
    private function sendReminder(Student $student, int $daysSinceEnrollment): void
    {
        try {
            if (!$student->email) {
                Log::warning("Cannot send reminder: Email not found for student {$student->id}");
                return;
            }

            $totalPaid = ($student->down_payment ?? 0) +
                $student->installments->where('status', InstallmentStatusEnum::Paid)->sum('paid_amount');
            $remainingBalance = max(0, $student->course_fees - $totalPaid);

            $data = [
                'student' => $student,
                'daysSinceEnrollment' => $daysSinceEnrollment,
                'enrollmentDate' => $student->enrollment_date->format('d/m/Y'),
                'totalFees' => number_format($student->course_fees, 2),
                'totalPaid' => number_format($totalPaid, 2),
                'remainingBalance' => number_format($remainingBalance, 2),
                'urgencyText' => $daysSinceEnrollment >= 90 ? 'URGENT' : 'Important',
            ];

            $options = [
                'queue' => true,
                'subject_prefix' => $daysSinceEnrollment >= 90 ? 'URGENT: ' : 'Reminder: ',
            ];

            $result = EmailNotificationHelper::sendNotificationByType(
                'installment_reminder',
                $student->email,
                $data,
                $options
            );

            if ($result) {
                Log::info("Payment reminder sent to {$student->email} ({$daysSinceEnrollment} days since enrollment, balance: ₹{$remainingBalance})");
            } else {
                Log::warning("Failed to send payment reminder to {$student->email}");
            }
        } catch (\Exception $e) {
            Log::error("Failed to send payment reminder: " . $e->getMessage(), [
                'student_id' => $student->id,
                'days_since_enrollment' => $daysSinceEnrollment,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
