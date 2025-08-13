<?php

namespace App\Helpers;

use App\Mail\NotificationMail;
use App\Models\Installment;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class MailHelper
{
    /**
     * Send installment reminder email
     *
     * @param string $email
     * @param string $subject
     * @param string $body
     * @param Installment $installment
     * @return bool
     */
    public static function sendInstallmentReminder(string $email, string $subject, string $body, Installment $installment): bool
    {
        try {
            Mail::to($email)
                ->cc(config('app.mail.to.address'), 'tech@acsinsights.com')
                ->bcc(config('app.mail.backup.address'))
                ->send(new NotificationMail($subject, $body));

            Log::info("Installment reminder email sent successfully", [
                'email' => $email,
                'subject' => $subject,
                'installment_id' => $installment->id,
                'student_id' => $installment->student_id
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error("Failed to send installment reminder email", [
                'email' => $email,
                'subject' => $subject,
                'installment_id' => $installment->id,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Send general notification email
     *
     * @param string $email
     * @param string $subject
     * @param string $body
     * @param array $cc
     * @param array $bcc
     * @return bool
     */
    public static function sendNotification(string $email, string $subject, string $body, array $cc = [], array $bcc = []): bool
    {
        try {
            $mail = Mail::to($email);

            // Add CC recipients
            if (!empty($cc)) {
                $mail->cc($cc);
            }

            // Add BCC recipients
            if (!empty($bcc)) {
                $mail->bcc($bcc);
            }

            $mail->send(new NotificationMail($subject, $body));

            Log::info("Notification email sent successfully", [
                'email' => $email,
                'subject' => $subject
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error("Failed to send notification email", [
                'email' => $email,
                'subject' => $subject,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Send ticket creation notification
     *
     * @param string $email
     * @param string $subject
     * @param object $ticket
     * @return bool
     */
    public static function sendTicketNotification(string $email, string $subject, object $ticket): bool
    {
        try {
            $body = view('mail.notification.ticket.create', [
                'subject' => $subject,
                'ticket' => $ticket
            ])->render();

            return self::sendNotification($email, $subject, $body);
        } catch (\Exception $e) {
            Log::error("Failed to send ticket notification", [
                'email' => $email,
                'ticket_id' => $ticket->id ?? 'unknown',
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Send overdue installment reminder email
     *
     * @param string $email
     * @param string $subject
     * @param string $body
     * @param Installment $installment
     * @return bool
     */
    public static function sendOverdueReminder(string $email, string $subject, string $body, Installment $installment): bool
    {
        try {
            Mail::to($email)
                ->cc(config('app.mail.to.address'), 'tech@acsinsights.com')
                ->bcc(config('app.mail.backup.address'))
                ->send(new NotificationMail($subject, $body));

            Log::info("Overdue installment reminder email sent successfully", [
                'email' => $email,
                'subject' => $subject,
                'installment_id' => $installment->id,
                'student_id' => $installment->student_id
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error("Failed to send overdue installment reminder email", [
                'email' => $email,
                'subject' => $subject,
                'installment_id' => $installment->id,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }
}
