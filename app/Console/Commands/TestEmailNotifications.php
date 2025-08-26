<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\EmailNotificationService;
use App\Helpers\MailHelper;
use App\Helpers\EmailNotificationHelper;
use App\Helpers\MailTemplateHelper;

class TestEmailNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:test {type} {email} {--template=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test email notification helpers with different notification types';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $type = $this->argument('type');
        $email = $this->argument('email');
        $template = $this->option('template');

        $this->info("Testing email notification type: {$type}");
        $this->info("Target email: {$email}");
        $this->line('');

        try {
            switch ($type) {
                case 'welcome':
                    $this->testWelcomeEmail($email);
                    break;
                case 'payment':
                    $this->testPaymentConfirmation($email);
                    break;
                case 'completion':
                    $this->testCourseCompletion($email);
                    break;
                case 'maintenance':
                    $this->testMaintenanceNotification($email);
                    break;
                case 'installment':
                    $this->testInstallmentReminder($email);
                    break;
                case 'registration':
                    $this->testRegistrationSuccess($email);
                    break;
                case 'overdue':
                    $this->testOverdueNotification($email);
                    break;
                case 'basic':
                    $this->testBasicEmail($email);
                    break;
                case 'bulk':
                    $this->testBulkNotification($email);
                    break;
                case 'template':
                    $this->testTemplateHelper($template);
                    break;
                case 'stats':
                    $this->showEmailStatistics();
                    break;
                default:
                    $this->error("Unknown notification type: {$type}");
                    $this->showAvailableTypes();
                    return 1;
            }

            $this->info('✅ Test completed successfully!');
            return 0;
        } catch (\Exception $e) {
            $this->error("❌ Test failed: " . $e->getMessage());
            $this->line('Check the logs for more details.');
            return 1;
        }
    }

    /**
     * Test welcome email
     */
    private function testWelcomeEmail(string $email): void
    {
        $this->info('Testing welcome email...');

        $emailService = new EmailNotificationService();

        $courseInfo = [
            'name' => 'Web Development Fundamentals',
            'enrollment_date' => now()->format('d/m/Y'),
            'instructor' => 'Prof. Sarah Johnson'
        ];

        $result = $emailService->sendWelcomeEmail(
            (object) ['first_name' => 'John', 'surname' => 'Doe', 'email' => $email],
            $courseInfo
        );

        if ($result) {
            $this->info('Welcome email sent successfully!');
        } else {
            $this->warn('Welcome email failed to send.');
        }
    }

    /**
     * Test payment confirmation
     */
    private function testPaymentConfirmation(string $email): void
    {
        $this->info('Testing payment confirmation...');

        $emailService = new EmailNotificationService();

        $paymentDetails = [
            'amount' => '500.00',
            'date' => now()->format('d/m/Y'),
            'transaction_id' => 'TXN' . rand(100000, 999999),
            'next_due_date' => now()->addMonth()->format('d/m/Y')
        ];

        $result = $emailService->sendPaymentConfirmation(
            (object) ['first_name' => 'John', 'surname' => 'Doe', 'email' => $email],
            $paymentDetails
        );

        if ($result) {
            $this->info('Payment confirmation sent successfully!');
        } else {
            $this->warn('Payment confirmation failed to send.');
        }
    }

    /**
     * Test course completion
     */
    private function testCourseCompletion(string $email): void
    {
        $this->info('Testing course completion notification...');

        $emailService = new EmailNotificationService();

        $courseDetails = [
            'name' => 'Web Development Fundamentals',
            'completion_date' => now()->format('d/m/Y'),
            'grade' => 'A+',
            'certificate_number' => 'CERT' . date('Y') . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT)
        ];

        $result = $emailService->sendCourseCompletionNotification(
            (object) ['first_name' => 'John', 'surname' => 'Doe', 'email' => $email],
            $courseDetails
        );

        if ($result) {
            $this->info('Course completion notification sent successfully!');
        } else {
            $this->warn('Course completion notification failed to send.');
        }
    }

    /**
     * Test maintenance notification
     */
    private function testMaintenanceNotification(string $email): void
    {
        $this->info('Testing maintenance notification...');

        $emailService = new EmailNotificationService();

        $maintenanceDetails = 'Scheduled system maintenance for database optimization and security updates.';
        $scheduledTime = now()->addDay()->format('d/m/Y H:i');

        $results = $emailService->sendMaintenanceNotification(
            [$email],
            $maintenanceDetails,
            $scheduledTime
        );

        $this->info("Maintenance notification results: {$results['success']} successful, {$results['failed']} failed");
    }

    /**
     * Test installment reminder
     */
    private function testInstallmentReminder(string $email): void
    {
        $this->info('Testing installment reminder...');

        $data = [
            'student' => (object) ['first_name' => 'John', 'surname' => 'Doe', 'email' => $email],
            'installment' => (object) ['id' => 1, 'amount' => 500.00, 'due_date' => now()->addDays(7)],
            'days' => 7,
            'dueDate' => now()->addDays(7)->format('d/m/Y'),
            'amount' => '500.00',
            'urgencyText' => 'Important'
        ];

        $result = EmailNotificationHelper::sendNotificationByType(
            'installment_reminder',
            $email,
            $data,
            ['queue' => false]
        );

        if ($result) {
            $this->info('Installment reminder sent successfully!');
        } else {
            $this->warn('Installment reminder failed to send.');
        }
    }

    /**
     * Test overdue notification
     */
    private function testOverdueNotification(string $email): void
    {
        $this->info('Testing overdue notification...');

        $data = [
            'student' => (object) ['first_name' => 'John', 'surname' => 'Doe', 'email' => $email],
            'installment' => (object) ['id' => 1, 'amount' => 500.00, 'due_date' => now()->subDays(5)],
            'daysAfterOverdue' => 5,
            'daysOverdue' => 5,
            'dueDate' => now()->subDays(5)->format('d/m/Y'),
            'amount' => '500.00',
            'urgencyText' => 'URGENT'
        ];

        $result = EmailNotificationHelper::sendNotificationByType(
            'overdue_notification',
            $email,
            $data,
            ['queue' => false]
        );

        if ($result) {
            $this->info('Overdue notification sent successfully!');
        } else {
            $this->warn('Overdue notification failed to send.');
        }
    }

    /**
     * Test registration success notification
     */
    private function testRegistrationSuccess(string $email): void
    {
        $this->info('Testing registration success notification...');

        $data = [
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

        $result = EmailNotificationHelper::sendNotificationByType(
            'registration_success',
            $email,
            $data,
            ['queue' => false]
        );

        if ($result) {
            $this->info('Registration success notification sent successfully!');
        } else {
            $this->warn('Registration success notification failed to send.');
        }
    }

    /**
     * Test basic email
     */
    private function testBasicEmail(string $email): void
    {
        $this->info('Testing basic email...');

        $result = MailHelper::sendNotification(
            $email,
            'Test Email - TIITVT System',
            'This is a test email to verify the email configuration is working correctly.',
            [],
            [],
            false // Don't queue for immediate testing
        );

        if ($result) {
            $this->info('Basic email sent successfully!');
        } else {
            $this->warn('Basic email failed to send.');
        }
    }

    /**
     * Test bulk notification
     */
    private function testBulkNotification(string $email): void
    {
        $this->info('Testing bulk notification...');

        $emails = [$email, $email]; // Send to same email twice for testing
        $subject = 'Bulk Test Email';
        $body = 'This is a test bulk email notification.';

        $results = MailHelper::sendBulkNotification(
            $emails,
            $subject,
            $body,
            [],
            [],
            1 // 1 second delay
        );

        $this->info("Bulk notification results: {$results['success']} successful, {$results['failed']} failed");
    }

    /**
     * Test template helper
     */
    private function testTemplateHelper(?string $templateType): void
    {
        $this->info('Testing template helper...');

        if (!$templateType) {
            $this->warn('No template type specified. Available types:');
            $types = EmailNotificationHelper::getAvailableNotificationTypes();
            foreach ($types as $type) {
                $this->line("  - {$type}");
            }
            return;
        }

        // Test template validation
        $sampleData = MailTemplateHelper::getSampleData($templateType);
        $errors = MailTemplateHelper::validateTemplateData($templateType, $sampleData);

        if (empty($errors)) {
            $this->info("Template validation passed for '{$templateType}'");
        } else {
            $this->warn("Template validation failed for '{$templateType}':");
            foreach ($errors as $error) {
                $this->line("  - {$error}");
            }
        }

        // Test template preview
        $templatePath = "mail.notification.{$templateType}";
        if (MailTemplateHelper::templateExists($templatePath)) {
            $this->info("Template '{$templatePath}' exists");

            $preview = MailTemplateHelper::previewTemplate($templatePath, $templateType);
            $this->line("Template preview length: " . strlen($preview) . " characters");
        } else {
            $this->warn("Template '{$templatePath}' does not exist");
        }
    }

    /**
     * Show email statistics
     */
    private function showEmailStatistics(): void
    {
        $this->info('Email Statistics:');

        $stats = MailHelper::getEmailStatistics();
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Sent', $stats['total_sent']],
                ['Total Failed', $stats['total_failed']],
                ['Success Rate', $stats['success_rate'] . '%'],
                ['Queue Size', $stats['queue_size']],
                ['Last Sent', $stats['last_sent'] ?? 'N/A']
            ]
        );

        $this->line('');
        $this->info('Available Notification Types:');
        $types = EmailNotificationHelper::getAvailableNotificationTypes();
        foreach ($types as $type) {
            $config = EmailNotificationHelper::getNotificationTypeConfig($type);
            $status = $config['enabled'] ? '✅ Enabled' : '❌ Disabled';
            $this->line("  - {$type}: {$status}");
        }
    }

    /**
     * Show available notification types
     */
    private function showAvailableTypes(): void
    {
        $this->line('Available notification types:');
        $this->line('  - welcome          : Welcome email for new students');
        $this->line('  - payment          : Payment confirmation email');
        $this->line('  - completion       : Course completion notification');
        $this->line('  - maintenance      : System maintenance notification');
        $this->line('  - installment      : Installment reminder');
        $this->line('  - overdue          : Overdue payment notification');
        $this->line('  - registration     : Registration success notification');
        $this->line('  - basic            : Basic test email');
        $this->line('  - bulk             : Bulk email test');
        $this->line('  - template         : Template helper test (use --template=type)');
        $this->line('  - stats            : Show email statistics');

        $this->line('');
        $this->line('Examples:');
        $this->line('  php artisan email:test welcome user@example.com');
        $this->line('  php artisan email:test payment user@example.com');
        $this->line('  php artisan email:test registration user@example.com');
        $this->line('  php artisan email:test template --template=welcome_email');
        $this->line('  php artisan email:test stats');
    }
}
