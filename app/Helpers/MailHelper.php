<?php

namespace App\Helpers;

use App\Mail\NotificationMail;
use App\Models\Installment;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Mail\Message;

class MailHelper
{
    /**
     * Default CC addresses for system notifications
     */
    private static array $defaultCc = [
        'demo@mail.com'
    ];

    /**
     * Default BCC addresses for system notifications
     */
    private static array $defaultBcc = [];

    /**
     * Send installment reminder email
     *
     * @param string $email
     * @param string $subject
     * @param string $body
     * @param Installment $installment
     * @param bool $queue
     * @return bool
     */
    public static function sendInstallmentReminder(string $email, string $subject, string $body, Installment $installment, bool $queue = true): bool
    {
        try {
            $mail = Mail::to($email)
                ->cc(array_merge([config('app.mail.to.address')], self::$defaultCc))
                ->bcc(config('app.mail.backup.address'));

            if ($queue) {
                $mail->queue(new NotificationMail($subject, $body));
            } else {
                $mail->send(new NotificationMail($subject, $body));
            }

            Log::info("Installment reminder email sent successfully", [
                'email' => $email,
                'subject' => $subject,
                'installment_id' => $installment->id,
                'student_id' => $installment->student_id,
                'queued' => $queue
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error("Failed to send installment reminder email", [
                'email' => $email,
                'subject' => $subject,
                'installment_id' => $installment->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
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
     * @param bool $queue
     * @return bool
     */
    public static function sendNotification(string $email, string $subject, string $body, array $cc = [], array $bcc = [], bool $queue = true): bool
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

            if ($queue) {
                $mail->queue(new NotificationMail($subject, $body));
            } else {
                $mail->send(new NotificationMail($subject, $body));
            }

            Log::info("Notification email sent successfully", [
                'email' => $email,
                'subject' => $subject,
                'queued' => $queue
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error("Failed to send notification email", [
                'email' => $email,
                'subject' => $subject,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
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
     * @param bool $queue
     * @return bool
     */
    public static function sendTicketNotification(string $email, string $subject, object $ticket, bool $queue = true): bool
    {
        try {
            $body = view('mail.notification.ticket.create', [
                'subject' => $subject,
                'ticket' => $ticket
            ])->render();

            return self::sendNotification($email, $subject, $body, [], [], $queue);
        } catch (\Exception $e) {
            Log::error("Failed to send ticket notification", [
                'email' => $email,
                'ticket_id' => $ticket->id ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
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
     * @param bool $queue
     * @return bool
     */
    public static function sendOverdueReminder(string $email, string $subject, string $body, Installment $installment, bool $queue = true): bool
    {
        try {
            $mail = Mail::to($email)
                ->cc(array_merge([config('app.mail.to.address')], self::$defaultCc))
                ->bcc(config('app.mail.backup.address'));

            if ($queue) {
                $mail->queue(new NotificationMail($subject, $body));
            } else {
                $mail->send(new NotificationMail($subject, $body));
            }

            Log::info("Overdue installment reminder email sent successfully", [
                'email' => $email,
                'subject' => $subject,
                'installment_id' => $installment->id,
                'student_id' => $installment->student_id,
                'queued' => $queue
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error("Failed to send overdue installment reminder email", [
                'email' => $email,
                'subject' => $subject,
                'installment_id' => $installment->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return false;
        }
    }

    /**
     * Send bulk emails with rate limiting
     *
     * @param array $emails
     * @param string $subject
     * @param string $body
     * @param array $cc
     * @param array $bcc
     * @param int $delay
     * @return array
     */
    public static function sendBulkNotification(array $emails, string $subject, string $body, array $cc = [], array $bcc = [], int $delay = 0): array
    {
        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => []
        ];

        foreach ($emails as $index => $email) {
            try {
                $mail = Mail::to($email);

                if (!empty($cc)) {
                    $mail->cc($cc);
                }

                if (!empty($bcc)) {
                    $mail->bcc($bcc);
                }

                // Add delay for rate limiting
                if ($delay > 0 && $index > 0) {
                    $mail->later(now()->addSeconds($delay * $index), new NotificationMail($subject, $body));
                } else {
                    $mail->queue(new NotificationMail($subject, $body));
                }

                $results['success']++;
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = [
                    'email' => $email,
                    'error' => $e->getMessage()
                ];

                Log::error("Failed to send bulk notification email", [
                    'email' => $email,
                    'subject' => $subject,
                    'error' => $e->getMessage()
                ]);
            }
        }

        Log::info("Bulk notification completed", [
            'total' => count($emails),
            'success' => $results['success'],
            'failed' => $results['failed']
        ]);

        return $results;
    }

    /**
     * Send welcome email to new students
     *
     * @param string $email
     * @param string $studentName
     * @param array $courseInfo
     * @param bool $queue
     * @return bool
     */
    public static function sendWelcomeEmail(string $email, string $studentName, array $courseInfo = [], bool $queue = true): bool
    {
        try {
            $subject = 'Welcome to TIITVT - Your Journey Begins Here!';
            $body = view('mail.notification.welcome', [
                'studentName' => $studentName,
                'courseInfo' => $courseInfo
            ])->render();

            return self::sendNotification($email, $subject, $body, [], [], $queue);
        } catch (\Exception $e) {
            Log::error("Failed to send welcome email", [
                'email' => $email,
                'student_name' => $studentName,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Send payment confirmation email
     *
     * @param string $email
     * @param string $studentName
     * @param array $paymentDetails
     * @param bool $queue
     * @return bool
     */
    public static function sendPaymentConfirmation(string $email, string $studentName, array $paymentDetails, bool $queue = true): bool
    {
        try {
            $subject = 'Payment Confirmation - Thank You!';
            $body = view('mail.notification.payment.confirmation', [
                'studentName' => $studentName,
                'paymentDetails' => $paymentDetails
            ])->render();

            return self::sendNotification($email, $subject, $body, [], [], $queue);
        } catch (\Exception $e) {
            Log::error("Failed to send payment confirmation email", [
                'email' => $email,
                'student_name' => $studentName,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Send course completion notification
     *
     * @param string $email
     * @param string $studentName
     * @param array $courseDetails
     * @param bool $queue
     * @return bool
     */
    public static function sendCourseCompletionNotification(string $email, string $studentName, array $courseDetails, bool $queue = true): bool
    {
        try {
            $subject = 'Congratulations! Course Completion Certificate';
            $body = view('mail.notification.course.completion', [
                'studentName' => $studentName,
                'courseDetails' => $courseDetails
            ])->render();

            return self::sendNotification($email, $subject, $body, [], [], $queue);
        } catch (\Exception $e) {
            Log::error("Failed to send course completion notification", [
                'email' => $email,
                'student_name' => $studentName,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Send system maintenance notification
     *
     * @param array $emails
     * @param string $maintenanceDetails
     * @param string $scheduledTime
     * @param bool $queue
     * @return array
     */
    public static function sendMaintenanceNotification(array $emails, string $maintenanceDetails, string $scheduledTime, bool $queue = true): array
    {
        try {
            $subject = 'System Maintenance Notification';
            $body = view('mail.notification.system.maintenance', [
                'maintenanceDetails' => $maintenanceDetails,
                'scheduledTime' => $scheduledTime
            ])->render();

            return self::sendBulkNotification($emails, $subject, $body, [], [], $queue ? 1 : 0);
        } catch (\Exception $e) {
            Log::error("Failed to send maintenance notification", [
                'emails_count' => count($emails),
                'error' => $e->getMessage()
            ]);

            return ['success' => 0, 'failed' => count($emails), 'errors' => []];
        }
    }

    /**
     * Test email configuration
     *
     * @param string $testEmail
     * @return array
     */
    public static function testEmailConfiguration(string $testEmail): array
    {
        try {
            $subject = 'Test Email - TIITVT System';
            $body = 'This is a test email to verify the email configuration is working correctly.';

            $result = self::sendNotification($testEmail, $subject, $body, [], [], false);

            return [
                'success' => $result,
                'message' => $result ? 'Test email sent successfully' : 'Failed to send test email'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get email statistics
     *
     * @return array
     */
    public static function getEmailStatistics(): array
    {
        // This would typically query a database table that tracks email statistics
        // For now, returning basic structure
        return [
            'total_sent' => 0,
            'total_failed' => 0,
            'success_rate' => 0,
            'last_sent' => null,
            'queue_size' => Queue::size('default')
        ];
    }
}
