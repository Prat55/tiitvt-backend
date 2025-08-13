<?php

namespace App\Services;

use App\Models\Installment;
use App\Models\Student;
use App\Helpers\MailHelper;
use App\Enums\InstallmentStatusEnum;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class OverdueInstallmentService
{
    /**
     * Handle overdue installments: update status and send reminders
     *
     * @return array
     */
    public function handleOverdueInstallments(): array
    {
        $statusUpdates = 0;
        $remindersSent = 0;

        // First, update status of overdue installments
        $statusUpdates = $this->updateOverdueStatuses();

        // Then, send overdue reminders
        $remindersSent = $this->sendOverdueReminders();

        return [
            'status_updates' => $statusUpdates,
            'reminders_sent' => $remindersSent
        ];
    }

    /**
     * Update status of overdue installments
     *
     * @return int
     */
    private function updateOverdueStatuses(): int
    {
        $overdueInstallments = Installment::where('status', InstallmentStatusEnum::Pending->value)
            ->where('due_date', '<', Carbon::now()->startOfDay())
            ->get();

        $updatedCount = 0;

        foreach ($overdueInstallments as $installment) {
            try {
                $installment->markAsOverdue();
                $updatedCount++;

                Log::info("Installment {$installment->id} marked as overdue", [
                    'installment_id' => $installment->id,
                    'student_id' => $installment->student_id,
                    'due_date' => $installment->due_date->format('Y-m-d')
                ]);
            } catch (\Exception $e) {
                Log::error("Failed to mark installment {$installment->id} as overdue", [
                    'installment_id' => $installment->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $updatedCount;
    }

    /**
     * Send overdue reminders for installments
     *
     * @return int
     */
    private function sendOverdueReminders(): int
    {
        $remindersSent = 0;
        $overdueReminderDays = [0, 3, 5, 7, 15]; // 0 = same day overdue, 3, 5, 7, 15 days after

        foreach ($overdueReminderDays as $daysAfterOverdue) {
            $installments = $this->getOverdueInstallmentsForReminder($daysAfterOverdue);

            foreach ($installments as $installment) {
                if ($this->shouldSendOverdueReminder($installment, $daysAfterOverdue)) {
                    $this->sendOverdueReminder($installment, $daysAfterOverdue);
                    $remindersSent++;
                }
            }
        }

        return $remindersSent;
    }

    /**
     * Get overdue installments that need reminders
     *
     * @param int $daysAfterOverdue
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private function getOverdueInstallmentsForReminder(int $daysAfterOverdue)
    {
        $targetDate = Carbon::now()->subDays($daysAfterOverdue)->startOfDay();

        return Installment::with(['student'])
            ->where('status', InstallmentStatusEnum::Overdue->value)
            ->whereDate('due_date', $targetDate)
            ->get();
    }

    /**
     * Check if overdue reminder should be sent
     *
     * @param Installment $installment
     * @param int $daysAfterOverdue
     * @return bool
     */
    private function shouldSendOverdueReminder(Installment $installment, int $daysAfterOverdue): bool
    {
        // Check if reminder was already sent for this installment and day
        $reminderKey = "overdue_reminder_{$installment->id}_{$daysAfterOverdue}";

        // For now, we'll always send reminders. In production, you might want to track this
        // to avoid sending duplicate reminders
        return true;
    }

    /**
     * Send overdue reminder email
     *
     * @param Installment $installment
     * @param int $daysAfterOverdue
     * @return void
     */
    private function sendOverdueReminder(Installment $installment, int $daysAfterOverdue): void
    {
        try {
            $student = $installment->student;

            if (!$student || !$student->email) {
                Log::warning("Cannot send overdue reminder: Student or email not found for installment {$installment->id}");
                return;
            }

            $subject = $this->getOverdueReminderSubject($daysAfterOverdue);
            $body = $this->getOverdueReminderBody($installment, $daysAfterOverdue);

            // Use the MailHelper to send the email
            MailHelper::sendOverdueReminder($student->email, $subject, $body, $installment);

            Log::info("Overdue reminder sent to {$student->email} for installment {$installment->id} ({$daysAfterOverdue} days after due date)");
        } catch (\Exception $e) {
            Log::error("Failed to send overdue reminder: " . $e->getMessage(), [
                'installment_id' => $installment->id,
                'student_id' => $installment->student_id,
                'days_after_overdue' => $daysAfterOverdue
            ]);
        }
    }

    /**
     * Get the subject line for the overdue reminder email
     *
     * @param int $daysAfterOverdue
     * @return string
     */
    private function getOverdueReminderSubject(int $daysAfterOverdue): string
    {
        if ($daysAfterOverdue === 0) {
            return 'URGENT: Installment Payment Overdue - Immediate Action Required';
        }

        return "URGENT: Installment Payment Overdue - {$daysAfterOverdue} Day(s) Past Due";
    }

    /**
     * Get the body content for the overdue reminder email
     *
     * @param Installment $installment
     * @param int $daysAfterOverdue
     * @return string
     */
    private function getOverdueReminderBody(Installment $installment, int $daysAfterOverdue): string
    {
        $student = $installment->student;
        $dueDate = $installment->due_date->format('d/m/Y');
        $amount = number_format($installment->amount, 2);
        $daysOverdue = Carbon::now()->diffInDays($installment->due_date);

        $urgencyText = $daysAfterOverdue === 0 ? 'CRITICAL' : 'URGENT';

        return view('mail.notification.installment.overdue', [
            'student' => $student,
            'installment' => $installment,
            'daysAfterOverdue' => $daysAfterOverdue,
            'daysOverdue' => $daysOverdue,
            'dueDate' => $dueDate,
            'amount' => $amount,
            'urgencyText' => $urgencyText
        ])->render();
    }
}
