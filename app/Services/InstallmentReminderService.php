<?php

namespace App\Services;

use App\Models\Installment;
use App\Models\Student;
use App\Mail\NotificationMail;
use App\Helpers\MailHelper;
use App\Enums\InstallmentStatusEnum;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class InstallmentReminderService
{
    /**
     * Send installment reminders to students based on remaining days
     *
     * @return int
     */
    public function sendReminders(): int
    {
        $remindersSent = 0;
        $reminderDays = [7, 5, 3, 2, 1];

        foreach ($reminderDays as $days) {
            $installments = $this->getInstallmentsDueInDays($days);

            foreach ($installments as $installment) {
                if ($this->shouldSendReminder($installment, $days)) {
                    $this->sendReminder($installment, $days);
                    $remindersSent++;
                }
            }
        }

        return $remindersSent;
    }

    /**
     * Get installments due in specific number of days
     *
     * @param int $days
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private function getInstallmentsDueInDays(int $days)
    {
        $targetDate = Carbon::now()->addDays($days)->startOfDay();

        return Installment::with(['student'])
            ->where('status', InstallmentStatusEnum::Pending->value)
            ->whereDate('due_date', $targetDate)
            ->get();
    }

    /**
     * Check if reminder should be sent for this installment
     *
     * @param Installment $installment
     * @param int $days
     * @return bool
     */
    private function shouldSendReminder(Installment $installment, int $days): bool
    {
        // Check if reminder was already sent for this installment and day
        $reminderKey = "installment_reminder_{$installment->id}_{$days}";

        // For now, we'll always send reminders. In production, you might want to track this
        // to avoid sending duplicate reminders
        return true;
    }

    /**
     * Send reminder email for an installment
     *
     * @param Installment $installment
     * @param int $days
     * @return void
     */
    private function sendReminder(Installment $installment, int $days): void
    {
        try {
            $student = $installment->student;

            if (!$student || !$student->email) {
                Log::warning("Cannot send reminder: Student or email not found for installment {$installment->id}");
                return;
            }

            $subject = $this->getReminderSubject($days);
            $body = $this->getReminderBody($installment, $days);

            // Use the MailHelper to send the email
            MailHelper::sendInstallmentReminder($student->email, $subject, $body, $installment);

            Log::info("Installment reminder sent to {$student->email} for installment {$installment->id} due in {$days} days");
        } catch (\Exception $e) {
            Log::error("Failed to send installment reminder: " . $e->getMessage(), [
                'installment_id' => $installment->id,
                'student_id' => $installment->student_id,
                'days' => $days
            ]);
        }
    }

    /**
     * Get the subject line for the reminder email
     *
     * @param int $days
     * @return string
     */
    private function getReminderSubject(int $days): string
    {
        if ($days === 1) {
            return 'Urgent: Installment Payment Due Tomorrow';
        }

        return "Reminder: Installment Payment Due in {$days} Days";
    }

    /**
     * Get the body content for the reminder email
     *
     * @param Installment $installment
     * @param int $days
     * @return string
     */
    private function getReminderBody(Installment $installment, int $days): string
    {
        $student = $installment->student;
        $dueDate = $installment->due_date->format('d/m/Y');
        $amount = number_format($installment->amount, 2);

        $urgencyText = $days === 1 ? 'URGENT' : 'Important';

        return view('mail.notification.installment.reminder', [
            'student' => $student,
            'installment' => $installment,
            'days' => $days,
            'dueDate' => $dueDate,
            'amount' => $amount,
            'urgencyText' => $urgencyText
        ])->render();
    }
}
