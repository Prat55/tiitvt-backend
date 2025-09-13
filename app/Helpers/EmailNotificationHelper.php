<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class EmailNotificationHelper
{
    /**
     * Notification types and their default settings
     */
    private static array $notificationTypes = [
        'installment_reminder' => [
            'enabled' => true,
            'template' => 'mail.notification.installment.reminder',
            'subject_prefix' => 'Reminder: ',
            'priority' => 'normal'
        ],
        'overdue_notification' => [
            'enabled' => true,
            'template' => 'mail.notification.installment.overdue',
            'subject_prefix' => 'URGENT: ',
            'priority' => 'high'
        ],
        'payment_confirmation' => [
            'enabled' => true,
            'template' => 'mail.notification.payment.confirmation',
            'subject_prefix' => 'Payment Confirmed: ',
            'priority' => 'normal'
        ],
        'welcome_email' => [
            'enabled' => true,
            'template' => 'mail.notification.welcome',
            'subject_prefix' => 'Welcome: ',
            'priority' => 'normal'
        ],
        'course_completion' => [
            'enabled' => true,
            'template' => 'mail.notification.course.completion',
            'subject_prefix' => 'Congratulations: ',
            'priority' => 'normal'
        ],
        'system_maintenance' => [
            'enabled' => true,
            'template' => 'mail.notification.system.maintenance',
            'subject_prefix' => 'System Notice: ',
            'priority' => 'high'
        ],
        'registration_success' => [
            'enabled' => true,
            'template' => 'mail.notification.registration.success',
            'subject_prefix' => 'Registration Successful: ',
            'priority' => 'normal'
        ]
    ];

    /**
     * Send notification based on type with automatic template selection
     *
     * @param string $type
     * @param string $email
     * @param array $data
     * @param array $options
     * @return bool
     */
    public static function sendNotificationByType(string $type, string $email, array $data, array $options = []): bool
    {
        if (!isset(self::$notificationTypes[$type])) {
            Log::channel('mail')->error("Unknown notification type: {$type}");
            return false;
        }

        $config = self::$notificationTypes[$type];

        if (!$config['enabled']) {
            Log::channel('mail')->info("Notification type {$type} is disabled");
            return false;
        }

        try {
            // Check if user has opted out of this notification type
            if (self::isNotificationOptedOut($email, $type)) {
                Log::channel('mail')->info("User {$email} has opted out of {$type} notifications");
                return false;
            }

            // Generate subject and body
            $subject = self::generateSubject($type, $data, $options);
            $body = self::generateBody($type, $data, $options);

            // Determine if email should be queued based on priority
            $queue = $config['priority'] !== 'high' && ($options['queue'] ?? true);

            // Send the email
            $result = MailHelper::sendNotification($email, $subject, $body, [], [], $queue);

            if ($result) {
                self::logNotificationSent($type, $email, $data);
            }

            return $result;
        } catch (\Exception $e) {
            Log::channel('mail')->error("Failed to send {$type} notification", [
                'email' => $email,
                'type' => $type,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return false;
        }
    }

    /**
     * Generate subject line for notification
     *
     * @param string $type
     * @param array $data
     * @param array $options
     * @return string
     */
    private static function generateSubject(string $type, array $data, array $options): string
    {
        $config = self::$notificationTypes[$type];
        $prefix = $options['subject_prefix'] ?? $config['subject_prefix'];

        switch ($type) {
            case 'installment_reminder':
                $days = $data['days'] ?? 0;
                if ($days === 1) {
                    return $prefix . 'Installment Payment Due Tomorrow';
                }
                return $prefix . "Installment Payment Due in {$days} Days";

            case 'overdue_notification':
                $daysOverdue = $data['daysOverdue'] ?? 0;
                if ($daysOverdue === 0) {
                    return $prefix . 'Installment Payment Overdue - Immediate Action Required';
                }
                return $prefix . "Installment Payment Overdue - {$daysOverdue} Day(s) Past Due";

            case 'payment_confirmation':
                return $prefix . 'Payment Confirmation - Thank You!';

            case 'welcome_email':
                return $prefix . 'Welcome to TIITVT - Your Journey Begins Here!';

            case 'course_completion':
                return $prefix . 'Course Completion Certificate';

            case 'system_maintenance':
                return $prefix . 'System Maintenance Notification';

            case 'registration_success':
                return $prefix . 'Welcome to TIITVT - Registration Complete';

            default:
                return $prefix . 'Notification from TIITVT';
        }
    }

    /**
     * Generate email body using appropriate template
     *
     * @param string $type
     * @param array $data
     * @param array $options
     * @return string
     */
    private static function generateBody(string $type, array $data, array $options): string
    {
        $config = self::$notificationTypes[$type];
        $template = $options['template'] ?? $config['template'];

        try {
            return view($template, $data)->render();
        } catch (\Exception $e) {
            Log::channel('mail')->error("Failed to render email template: {$template}", [
                'type' => $type,
                'error' => $e->getMessage()
            ]);

            // Fallback to basic text
            return self::generateFallbackBody($type, $data);
        }
    }

    /**
     * Generate fallback body if template fails
     *
     * @param string $type
     * @param array $data
     * @return string
     */
    private static function generateFallbackBody(string $type, array $data): string
    {
        $studentName = $data['studentName'] ?? $data['student']['first_name'] ?? 'Student';

        switch ($type) {
            case 'installment_reminder':
                $days = $data['days'] ?? 0;
                $amount = $data['amount'] ?? 'N/A';
                return "Dear {$studentName}, this is a reminder that your installment payment of {$amount} is due in {$days} days.";

            case 'overdue_notification':
                $amount = $data['amount'] ?? 'N/A';
                return "Dear {$studentName}, your installment payment of {$amount} is overdue. Please make payment immediately.";

            case 'payment_confirmation':
                $amount = $data['paymentDetails']['amount'] ?? 'N/A';
                return "Dear {$studentName}, thank you for your payment of {$amount}. Your payment has been confirmed.";

            case 'welcome_email':
                return "Dear {$studentName}, welcome to TIITVT! We're excited to have you on board.";

            case 'course_completion':
                return "Dear {$studentName}, congratulations on completing your course! You will receive your certificate shortly.";

            case 'registration_success':
                return "Dear {$studentName}, congratulations! Your registration with TIITVT has been successfully completed. Welcome to our learning community!";

            default:
                return "Dear {$studentName}, you have a notification from TIITVT.";
        }
    }

    /**
     * Check if user has opted out of specific notification type
     *
     * @param string $email
     * @param string $type
     * @return bool
     */
    private static function isNotificationOptedOut(string $email, string $type): bool
    {
        // This would typically check a database table for user preferences
        // For now, we'll use a simple cache-based approach
        $cacheKey = "notification_optout_{$email}_{$type}";

        return Cache::get($cacheKey, false);
    }

    /**
     * Log notification sent for tracking purposes
     *
     * @param string $type
     * @param string $email
     * @param array $data
     * @return void
     */
    private static function logNotificationSent(string $type, string $email, array $data): void
    {
        try {
            // This would typically insert into a database table
            // For now, we'll just log it
            Log::channel('mail')->info("Notification logged", [
                'type' => $type,
                'email' => $email,
                'timestamp' => now()->toISOString(),
                'data_keys' => array_keys($data)
            ]);
        } catch (\Exception $e) {
            Log::channel('mail')->error("Failed to log notification", [
                'type' => $type,
                'email' => $email,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send scheduled notification at specific time
     *
     * @param string $type
     * @param string $email
     * @param array $data
     * @param Carbon $sendAt
     * @param array $options
     * @return bool
     */
    public static function scheduleNotification(string $type, string $email, array $data, Carbon $sendAt, array $options = []): bool
    {
        try {
            $job = new \App\Jobs\SendScheduledNotification($type, $email, $data, $options);

            \Illuminate\Support\Facades\Queue::later($sendAt, $job);

            Log::channel('mail')->info("Notification scheduled", [
                'type' => $type,
                'email' => $email,
                'scheduled_for' => $sendAt->toISOString()
            ]);

            return true;
        } catch (\Exception $e) {
            Log::channel('mail')->error("Failed to schedule notification", [
                'type' => $type,
                'email' => $email,
                'scheduled_for' => $sendAt->toISOString(),
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Send notification to multiple recipients with personalization
     *
     * @param string $type
     * @param array $recipients
     * @param array $commonData
     * @param array $options
     * @return array
     */
    public static function sendBulkPersonalizedNotification(string $type, array $recipients, array $commonData, array $options = []): array
    {
        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => []
        ];

        foreach ($recipients as $recipient) {
            $email = $recipient['email'];
            $personalData = array_merge($commonData, $recipient['personal_data'] ?? []);

            try {
                $result = self::sendNotificationByType($type, $email, $personalData, $options);

                if ($result) {
                    $results['success']++;
                } else {
                    $results['failed']++;
                }
            } catch (\Exception $e) {
                Log::channel('mail')->error("Failed to send bulk personalized notification", [
                    'type' => $type,
                    'email' => $email,
                    'error' => $e->getMessage()
                ]);
                $results['failed']++;
                $results['errors'][] = [
                    'email' => $email,
                    'error' => $e->getMessage()
                ];
            }
        }

        Log::channel('mail')->info("Bulk personalized notification completed", [
            'type' => $type,
            'total' => count($recipients),
            'success' => $results['success'],
            'failed' => $results['failed']
        ]);

        return $results;
    }

    /**
     * Get notification statistics for a specific type
     *
     * @param string $type
     * @param string|null $email
     * @param Carbon|null $startDate
     * @param Carbon|null $endDate
     * @return array
     */
    public static function getNotificationStatistics(string $type, ?string $email = null, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        // This would typically query a database table
        // For now, returning basic structure
        return [
            'type' => $type,
            'total_sent' => 0,
            'total_failed' => 0,
            'success_rate' => 0,
            'period' => [
                'start' => $startDate?->toISOString(),
                'end' => $endDate?->toISOString()
            ],
            'email_filter' => $email
        ];
    }

    /**
     * Enable or disable notification type globally
     *
     * @param string $type
     * @param bool $enabled
     * @return bool
     */
    public static function setNotificationTypeStatus(string $type, bool $enabled): bool
    {
        if (!isset(self::$notificationTypes[$type])) {
            return false;
        }

        self::$notificationTypes[$type]['enabled'] = $enabled;

        Log::channel('mail')->info("Notification type status updated", [
            'type' => $type,
            'enabled' => $enabled
        ]);

        return true;
    }

    /**
     * Get all available notification types
     *
     * @return array
     */
    public static function getAvailableNotificationTypes(): array
    {
        return array_keys(self::$notificationTypes);
    }

    /**
     * Get notification type configuration
     *
     * @param string $type
     * @return array|null
     */
    public static function getNotificationTypeConfig(string $type): ?array
    {
        return self::$notificationTypes[$type] ?? null;
    }
}
