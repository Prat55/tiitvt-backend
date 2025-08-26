<?php

namespace App\Helpers;

use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;

class MailTemplateHelper
{
    /**
     * Default template variables
     */
    private static array $defaultVariables = [
        'app_name' => 'TIITVT',
        'app_url' => null,
        'support_email' => null,
        'current_year' => null
    ];

    /**
     * Template fallbacks for different notification types
     */
    private static array $templateFallbacks = [
        'installment_reminder' => 'mail.notification.installment.reminder',
        'overdue_notification' => 'mail.notification.installment.overdue',
        'payment_confirmation' => 'mail.notification.payment.confirmation',
        'welcome_email' => 'mail.notification.welcome',
        'course_completion' => 'mail.notification.course.completion',
        'system_maintenance' => 'mail.notification.system.maintenance'
    ];

    /**
     * Initialize default variables
     */
    public static function initialize(): void
    {
        self::$defaultVariables['app_url'] = config('app.url');
        self::$defaultVariables['support_email'] = config('app.mail.support.address', 'support@tiitvt.com');
        self::$defaultVariables['current_year'] = date('Y');
    }

    /**
     * Render email template with fallback support
     *
     * @param string $templatePath
     * @param array $data
     * @param string|null $fallbackTemplate
     * @return string
     */
    public static function renderTemplate(string $templatePath, array $data, ?string $fallbackTemplate = null): string
    {
        try {
            // Merge default variables with provided data
            $mergedData = array_merge(self::$defaultVariables, $data);

            // Try to render the primary template
            if (View::exists($templatePath)) {
                return view($templatePath, $mergedData)->render();
            }

            // Try fallback template if provided
            if ($fallbackTemplate && View::exists($fallbackTemplate)) {
                Log::warning("Primary template not found, using fallback", [
                    'primary' => $templatePath,
                    'fallback' => $fallbackTemplate
                ]);
                return view($fallbackTemplate, $mergedData)->render();
            }

            // Generate basic HTML fallback
            return self::generateBasicHtmlFallback($mergedData);
        } catch (\Exception $e) {
            Log::error("Failed to render email template", [
                'template' => $templatePath,
                'fallback' => $fallbackTemplate,
                'error' => $e->getMessage()
            ]);

            return self::generateBasicHtmlFallback($data);
        }
    }

    /**
     * Generate basic HTML fallback when templates fail
     *
     * @param array $data
     * @return string
     */
    private static function generateBasicHtmlFallback(array $data): string
    {
        $appName = $data['app_name'] ?? 'TIITVT';
        $studentName = $data['studentName'] ?? $data['student']['first_name'] ?? 'Student';
        $content = $data['content'] ?? 'You have a notification from our system.';

        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='utf-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Notification from {$appName}</title>
        </head>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;'>
            <div style='background-color: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px;'>
                <h1 style='color: #007bff; margin: 0; text-align: center;'>{$appName}</h1>
            </div>

            <div style='background-color: #ffffff; padding: 20px; border-radius: 8px; border: 1px solid #dee2e6;'>
                <p>Dear <strong>{$studentName}</strong>,</p>
                <p>{$content}</p>
                <p>If you have any questions, please contact our support team.</p>
                <p>Best regards,<br><strong>{$appName} Team</strong></p>
            </div>

            <div style='text-align: center; margin-top: 20px; color: #6c757d; font-size: 12px;'>
                <p>This is an automated message. Please do not reply to this email.</p>
                <p>&copy; " . date('Y') . " {$appName}. All rights reserved.</p>
            </div>
        </body>
        </html>";
    }

    /**
     * Get template variables for a specific notification type
     *
     * @param string $type
     * @param array $data
     * @return array
     */
    public static function getTemplateVariables(string $type, array $data): array
    {
        $baseVariables = self::$defaultVariables;

        switch ($type) {
            case 'installment_reminder':
                return array_merge($baseVariables, [
                    'urgencyText' => $data['days'] === 1 ? 'URGENT' : 'Important',
                    'daysText' => $data['days'] === 1 ? 'tomorrow' : "in {$data['days']} days",
                    'actionRequired' => $data['days'] <= 3 ? 'IMMEDIATE ACTION REQUIRED' : 'Please take action soon'
                ], $data);

            case 'overdue_notification':
                return array_merge($baseVariables, [
                    'urgencyText' => $data['daysAfterOverdue'] === 0 ? 'CRITICAL' : 'URGENT',
                    'overdueText' => $data['daysOverdue'] === 1 ? '1 day overdue' : "{$data['daysOverdue']} days overdue",
                    'actionRequired' => 'IMMEDIATE ACTION REQUIRED'
                ], $data);

            case 'payment_confirmation':
                return array_merge($baseVariables, [
                    'paymentDate' => $data['paymentDetails']['date'] ?? now()->format('d/m/Y'),
                    'transactionId' => $data['paymentDetails']['transaction_id'] ?? 'N/A',
                    'nextDueDate' => $data['paymentDetails']['next_due_date'] ?? 'N/A'
                ], $data);

            case 'welcome_email':
                return array_merge($baseVariables, [
                    'enrollmentDate' => $data['courseInfo']['enrollment_date'] ?? now()->format('d/m/Y'),
                    'courseName' => $data['courseInfo']['name'] ?? 'your course',
                    'instructorName' => $data['courseInfo']['instructor'] ?? 'our instructor'
                ], $data);

            case 'course_completion':
                return array_merge($baseVariables, [
                    'completionDate' => $data['courseDetails']['completion_date'] ?? now()->format('d/m/Y'),
                    'grade' => $data['courseDetails']['grade'] ?? 'N/A',
                    'certificateNumber' => $data['courseDetails']['certificate_number'] ?? 'N/A'
                ], $data);

            default:
                return array_merge($baseVariables, $data);
        }
    }

    /**
     * Check if template exists
     *
     * @param string $templatePath
     * @return bool
     */
    public static function templateExists(string $templatePath): bool
    {
        return View::exists($templatePath);
    }

    /**
     * Get available templates
     *
     * @return array
     */
    public static function getAvailableTemplates(): array
    {
        $templates = [];
        $viewsPath = resource_path('views/mail');

        if (File::exists($viewsPath)) {
            self::scanTemplates($viewsPath, $templates);
        }

        return $templates;
    }

    /**
     * Recursively scan for templates
     *
     * @param string $path
     * @param array &$templates
     * @param string $prefix
     */
    private static function scanTemplates(string $path, array &$templates, string $prefix = ''): void
    {
        $files = File::files($path);
        $directories = File::directories($path);

        foreach ($files as $file) {
            if ($file->getExtension() === 'blade.php') {
                $templateName = $prefix . '.' . $file->getBasename('.blade.php');
                $templates[] = $templateName;
            }
        }

        foreach ($directories as $directory) {
            $dirName = $directory->getBasename();
            $newPrefix = $prefix ? $prefix . '.' . $dirName : $dirName;
            self::scanTemplates($directory->getPathname(), $templates, $newPrefix);
        }
    }

    /**
     * Validate template data
     *
     * @param string $type
     * @param array $data
     * @return array
     */
    public static function validateTemplateData(string $type, array $data): array
    {
        $errors = [];
        $requiredFields = self::getRequiredFields($type);

        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $errors[] = "Missing required field: {$field}";
            }
        }

        return $errors;
    }

    /**
     * Get required fields for template type
     *
     * @param string $type
     * @return array
     */
    private static function getRequiredFields(string $type): array
    {
        switch ($type) {
            case 'installment_reminder':
                return ['student', 'installment', 'days', 'dueDate', 'amount'];

            case 'overdue_notification':
                return ['student', 'installment', 'daysAfterOverdue', 'daysOverdue', 'dueDate', 'amount'];

            case 'payment_confirmation':
                return ['studentName', 'paymentDetails'];

            case 'welcome_email':
                return ['studentName', 'courseInfo'];

            case 'course_completion':
                return ['studentName', 'courseDetails'];

            case 'system_maintenance':
                return ['maintenanceDetails', 'scheduledTime'];

            case 'registration_success':
                return ['studentName', 'tiitvtRegNo', 'courseName', 'centerName'];

            default:
                return ['studentName'];
        }
    }

    /**
     * Preview template with sample data
     *
     * @param string $templatePath
     * @param string $type
     * @return string
     */
    public static function previewTemplate(string $templatePath, string $type): string
    {
        $sampleData = self::getSampleData($type);
        return self::renderTemplate($templatePath, $sampleData);
    }

    /**
     * Get sample data for template preview
     *
     * @param string $type
     * @return array
     */
    public static function getSampleData(string $type): array
    {
        switch ($type) {
            case 'installment_reminder':
                return [
                    'student' => [
                        'first_name' => 'John',
                        'surname' => 'Doe',
                        'email' => 'john.doe@example.com'
                    ],
                    'installment' => [
                        'id' => 1,
                        'amount' => 500.00,
                        'due_date' => now()->addDays(7)
                    ],
                    'days' => 7,
                    'dueDate' => now()->addDays(7)->format('d/m/Y'),
                    'amount' => '500.00'
                ];

            case 'overdue_notification':
                return [
                    'student' => [
                        'first_name' => 'Jane',
                        'surname' => 'Smith',
                        'email' => 'jane.smith@example.com'
                    ],
                    'installment' => [
                        'id' => 2,
                        'amount' => 500.00,
                        'due_date' => now()->subDays(5)
                    ],
                    'daysAfterOverdue' => 5,
                    'daysOverdue' => 5,
                    'dueDate' => now()->subDays(5)->format('d/m/Y'),
                    'amount' => '500.00'
                ];

            case 'payment_confirmation':
                return [
                    'studentName' => 'John Doe',
                    'paymentDetails' => [
                        'amount' => '500.00',
                        'date' => now()->format('d/m/Y'),
                        'transaction_id' => 'TXN123456',
                        'next_due_date' => now()->addMonth()->format('d/m/Y')
                    ]
                ];

            case 'welcome_email':
                return [
                    'studentName' => 'John Doe',
                    'courseInfo' => [
                        'name' => 'Web Development Fundamentals',
                        'enrollment_date' => now()->format('d/m/Y'),
                        'instructor' => 'Prof. Sarah Johnson'
                    ]
                ];

            case 'course_completion':
                return [
                    'studentName' => 'John Doe',
                    'courseDetails' => [
                        'name' => 'Web Development Fundamentals',
                        'completion_date' => now()->format('d/m/Y'),
                        'grade' => 'A+',
                        'certificate_number' => 'CERT2024001'
                    ]
                ];

            case 'system_maintenance':
                return [
                    'maintenanceDetails' => 'Scheduled system maintenance for database optimization and security updates.',
                    'scheduledTime' => now()->addDay()->format('d/m/Y H:i')
                ];

            case 'registration_success':
                return [
                    'studentName' => 'John Doe',
                    'tiitvtRegNo' => 'TIITVT2024001',
                    'courseName' => 'Web Development Fundamentals',
                    'centerName' => 'Main Campus',
                    'enrollmentDate' => now()->format('d/m/Y'),
                    'courseFees' => 50000.00,
                    'downPayment' => 10000.00,
                    'noOfInstallments' => 8,
                    'monthlyInstallment' => 5000.00
                ];

            default:
                return [
                    'studentName' => 'Sample Student',
                    'content' => 'This is a sample notification content.'
                ];
        }
    }
}
