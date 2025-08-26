<?php

namespace App\Services;

use App\Helpers\MailHelper;
use App\Helpers\EmailNotificationHelper;
use App\Helpers\MailTemplateHelper;
use App\Models\Student;
use App\Models\Installment;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class EmailNotificationService
{
    /**
     * Send welcome email to new student
     *
     * @param Student $student
     * @param array $courseInfo
     * @return bool
     */
    public function sendWelcomeEmail(Student $student, array $courseInfo = []): bool
    {
        try {
            $data = [
                'studentName' => $student->first_name . ' ' . $student->surname,
                'courseInfo' => $courseInfo
            ];

            $result = EmailNotificationHelper::sendNotificationByType(
                'welcome_email',
                $student->email,
                $data,
                ['queue' => true]
            );

            if ($result) {
                Log::info("Welcome email sent successfully to {$student->email}");
            }

            return $result;
        } catch (\Exception $e) {
            Log::error("Failed to send welcome email", [
                'student_id' => $student->id,
                'email' => $student->email,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Send payment confirmation email
     *
     * @param Student $student
     * @param array $paymentDetails
     * @return bool
     */
    public function sendPaymentConfirmation(Student $student, array $paymentDetails): bool
    {
        try {
            $data = [
                'studentName' => $student->first_name . ' ' . $student->surname,
                'paymentDetails' => $paymentDetails
            ];

            $result = EmailNotificationHelper::sendNotificationByType(
                'payment_confirmation',
                $student->email,
                $data,
                ['queue' => true]
            );

            if ($result) {
                Log::info("Payment confirmation email sent successfully to {$student->email}");
            }

            return $result;
        } catch (\Exception $e) {
            Log::error("Failed to send payment confirmation email", [
                'student_id' => $student->id,
                'email' => $student->email,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Send course completion notification
     *
     * @param Student $student
     * @param array $courseDetails
     * @return bool
     */
    public function sendCourseCompletionNotification(Student $student, array $courseDetails): bool
    {
        try {
            $data = [
                'studentName' => $student->first_name . ' ' . $student->surname,
                'courseDetails' => $courseDetails
            ];

            $result = EmailNotificationHelper::sendNotificationByType(
                'course_completion',
                $student->email,
                $data,
                ['queue' => true]
            );

            if ($result) {
                Log::info("Course completion notification sent successfully to {$student->email}");
            }

            return $result;
        } catch (\Exception $e) {
            Log::error("Failed to send course completion notification", [
                'student_id' => $student->id,
                'email' => $student->email,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Send registration success notification
     *
     * @param Student $student
     * @param array $registrationData
     * @return bool
     */
    public function sendRegistrationSuccessNotification(Student $student, array $registrationData): bool
    {
        try {
            $data = [
                'studentName' => $student->first_name . ' ' . $student->surname,
                'tiitvtRegNo' => $student->tiitvt_reg_no,
                'courseName' => $registrationData['courseName'] ?? 'N/A',
                'centerName' => $registrationData['centerName'] ?? 'N/A',
                'enrollmentDate' => $registrationData['enrollmentDate'] ?? now()->format('d/m/Y'),
                'courseFees' => $registrationData['courseFees'] ?? 0,
                'downPayment' => $registrationData['downPayment'] ?? 0,
                'noOfInstallments' => $registrationData['noOfInstallments'] ?? 0,
                'monthlyInstallment' => $registrationData['monthlyInstallment'] ?? 0
            ];

            $result = EmailNotificationHelper::sendNotificationByType(
                'registration_success',
                $student->email,
                $data,
                ['queue' => true]
            );

            if ($result) {
                Log::info("Registration success notification sent successfully to {$student->email}");
            }

            return $result;
        } catch (\Exception $e) {
            Log::error("Failed to send registration success notification", [
                'student_id' => $student->id,
                'email' => $student->email,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Send system maintenance notification to multiple users
     *
     * @param array $emails
     * @param string $maintenanceDetails
     * @param string $scheduledTime
     * @return array
     */
    public function sendMaintenanceNotification(array $emails, string $maintenanceDetails, string $scheduledTime): array
    {
        try {
            $result = EmailNotificationHelper::sendNotificationByType(
                'system_maintenance',
                $emails[0], // Send to first email for bulk processing
                [
                    'maintenanceDetails' => $maintenanceDetails,
                    'scheduledTime' => $scheduledTime
                ],
                ['queue' => true]
            );

            // For bulk notifications, use the MailHelper's bulk method
            if (count($emails) > 1) {
                $subject = 'System Maintenance Notification';
                $body = MailTemplateHelper::renderTemplate(
                    'mail.notification.system.maintenance',
                    [
                        'maintenanceDetails' => $maintenanceDetails,
                        'scheduledTime' => $scheduledTime
                    ]
                );

                return MailHelper::sendBulkNotification(
                    array_slice($emails, 1), // Skip first email as it was already sent
                    $subject,
                    $body,
                    [], // CC
                    [], // BCC
                    2 // 2 second delay between emails for rate limiting
                );
            }

            return ['success' => $result ? 1 : 0, 'failed' => $result ? 0 : 1, 'errors' => []];
        } catch (\Exception $e) {
            Log::error("Failed to send maintenance notification", [
                'emails_count' => count($emails),
                'error' => $e->getMessage()
            ]);

            return ['success' => 0, 'failed' => count($emails), 'errors' => []];
        }
    }

    /**
     * Send personalized bulk notifications
     *
     * @param string $type
     * @param array $recipients
     * @param array $commonData
     * @return array
     */
    public function sendPersonalizedBulkNotifications(string $type, array $recipients, array $commonData): array
    {
        try {
            return EmailNotificationHelper::sendBulkPersonalizedNotification(
                $type,
                $recipients,
                $commonData,
                ['queue' => true]
            );
        } catch (\Exception $e) {
            Log::error("Failed to send personalized bulk notifications", [
                'type' => $type,
                'recipients_count' => count($recipients),
                'error' => $e->getMessage()
            ]);

            return ['success' => 0, 'failed' => count($recipients), 'errors' => []];
        }
    }

    /**
     * Schedule notification for future delivery
     *
     * @param string $type
     * @param string $email
     * @param array $data
     * @param Carbon $sendAt
     * @return bool
     */
    public function scheduleNotification(string $type, string $email, array $data, Carbon $sendAt): bool
    {
        try {
            $result = EmailNotificationHelper::scheduleNotification(
                $type,
                $email,
                $data,
                $sendAt,
                ['queue' => true]
            );

            if ($result) {
                Log::info("Notification scheduled successfully", [
                    'type' => $type,
                    'email' => $email,
                    'scheduled_for' => $sendAt->toISOString()
                ]);
            }

            return $result;
        } catch (\Exception $e) {
            Log::error("Failed to schedule notification", [
                'type' => $type,
                'email' => $email,
                'scheduled_for' => $sendAt->toISOString(),
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Test email configuration
     *
     * @param string $testEmail
     * @return array
     */
    public function testEmailConfiguration(string $testEmail): array
    {
        try {
            return MailHelper::testEmailConfiguration($testEmail);
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
    public function getEmailStatistics(): array
    {
        try {
            return MailHelper::getEmailStatistics();
        } catch (\Exception $e) {
            Log::error("Failed to get email statistics", [
                'error' => $e->getMessage()
            ]);

            return [
                'total_sent' => 0,
                'total_failed' => 0,
                'success_rate' => 0,
                'last_sent' => null,
                'queue_size' => 0
            ];
        }
    }

    /**
     * Get notification statistics for specific type
     *
     * @param string $type
     * @param string|null $email
     * @param Carbon|null $startDate
     * @param Carbon|null $endDate
     * @return array
     */
    public function getNotificationStatistics(string $type, ?string $email = null, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        try {
            return EmailNotificationHelper::getNotificationStatistics($type, $email, $startDate, $endDate);
        } catch (\Exception $e) {
            Log::error("Failed to get notification statistics", [
                'type' => $type,
                'error' => $e->getMessage()
            ]);

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
    }

    /**
     * Preview email template
     *
     * @param string $type
     * @return string
     */
    public function previewEmailTemplate(string $type): string
    {
        try {
            $sampleData = MailTemplateHelper::getSampleData($type);
            $templatePath = "mail.notification.{$type}";

            return MailTemplateHelper::previewTemplate($templatePath, $type);
        } catch (\Exception $e) {
            Log::error("Failed to preview email template", [
                'type' => $type,
                'error' => $e->getMessage()
            ]);

            return "Error: Unable to preview template for type '{$type}'";
        }
    }

    /**
     * Validate template data
     *
     * @param string $type
     * @param array $data
     * @return array
     */
    public function validateTemplateData(string $type, array $data): array
    {
        try {
            return MailTemplateHelper::validateTemplateData($type, $data);
        } catch (\Exception $e) {
            Log::error("Failed to validate template data", [
                'type' => $type,
                'error' => $e->getMessage()
            ]);

            return ["Error: Unable to validate data for type '{$type}'"];
        }
    }

    /**
     * Get available notification types
     *
     * @return array
     */
    public function getAvailableNotificationTypes(): array
    {
        try {
            return EmailNotificationHelper::getAvailableNotificationTypes();
        } catch (\Exception $e) {
            Log::error("Failed to get available notification types", [
                'error' => $e->getMessage()
            ]);

            return [];
        }
    }

    /**
     * Get notification type configuration
     *
     * @param string $type
     * @return array|null
     */
    public function getNotificationTypeConfig(string $type): ?array
    {
        try {
            return EmailNotificationHelper::getNotificationTypeConfig($type);
        } catch (\Exception $e) {
            Log::error("Failed to get notification type config", [
                'type' => $type,
                'error' => $e->getMessage()
            ]);

            return null;
        }
    }

    /**
     * Enable or disable notification type
     *
     * @param string $type
     * @param bool $enabled
     * @return bool
     */
    public function setNotificationTypeStatus(string $type, bool $enabled): bool
    {
        try {
            return EmailNotificationHelper::setNotificationTypeStatus($type, $enabled);
        } catch (\Exception $e) {
            Log::error("Failed to set notification type status", [
                'type' => $type,
                'enabled' => $enabled,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }
}
