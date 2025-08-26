# Email Notification Helpers - TIITVT Backend

This document provides a comprehensive guide to using the enhanced email notification system in the TIITVT backend application.

## Overview

The email notification system consists of three main helper classes:

1. **`MailHelper`** - Core email sending functionality with queue support
2. **`EmailNotificationHelper`** - Advanced notification management with type-based templates
3. **`MailTemplateHelper`** - Template rendering and management utilities

## Table of Contents

- [Quick Start](#quick-start)
- [Helper Classes](#helper-classes)
- [Email Templates](#email-templates)
- [Usage Examples](#usage-examples)
- [Configuration](#configuration)
- [Best Practices](#best-practices)
- [Troubleshooting](#troubleshooting)

## Quick Start

### Basic Email Sending

```php
use App\Helpers\MailHelper;

// Send a simple notification
$result = MailHelper::sendNotification(
    'user@example.com',
    'Subject',
    'Email body content'
);

// Send with CC and BCC
$result = MailHelper::sendNotification(
    'user@example.com',
    'Subject',
    'Email body content',
    ['cc@example.com'],
    ['bcc@example.com']
);
```

### Type-Based Notifications

```php
use App\Helpers\EmailNotificationHelper;

// Send welcome email
$result = EmailNotificationHelper::sendNotificationByType(
    'welcome_email',
    'student@example.com',
    [
        'studentName' => 'John Doe',
        'courseInfo' => ['name' => 'Web Development']
    ]
);
```

## Helper Classes

### 1. MailHelper

The core email sending class with enhanced functionality.

#### Key Methods

- `sendNotification()` - Send general notifications
- `sendInstallmentReminder()` - Send installment reminders
- `sendOverdueReminder()` - Send overdue notifications
- `sendBulkNotification()` - Send bulk emails with rate limiting
- `sendWelcomeEmail()` - Send welcome emails
- `sendPaymentConfirmation()` - Send payment confirmations
- `sendCourseCompletionNotification()` - Send completion notifications
- `sendMaintenanceNotification()` - Send system maintenance notices
- `testEmailConfiguration()` - Test email setup
- `getEmailStatistics()` - Get email statistics

#### Features

- **Queue Support**: All methods support queuing for better performance
- **Rate Limiting**: Bulk emails can be sent with configurable delays
- **Error Handling**: Comprehensive error logging and fallback mechanisms
- **CC/BCC Management**: Flexible recipient management
- **Template Support**: Works with existing Blade templates

### 2. EmailNotificationHelper

Advanced notification management with type-based templates and configuration.

#### Key Methods

- `sendNotificationByType()` - Send notifications by type with automatic template selection
- `sendBulkPersonalizedNotification()` - Send personalized bulk notifications
- `scheduleNotification()` - Schedule notifications for future delivery
- `getNotificationStatistics()` - Get notification statistics
- `setNotificationTypeStatus()` - Enable/disable notification types

#### Features

- **Type-Based Templates**: Automatic template selection based on notification type
- **Priority Management**: High-priority notifications sent immediately
- **Opt-out Support**: User preference management (framework ready)
- **Scheduling**: Future delivery scheduling
- **Personalization**: Bulk notifications with personal data

### 3. MailTemplateHelper

Template rendering and management utilities.

#### Key Methods

- `renderTemplate()` - Render templates with fallback support
- `getTemplateVariables()` - Get template variables for specific types
- `templateExists()` - Check if template exists
- `getAvailableTemplates()` - List all available templates
- `validateTemplateData()` - Validate template data
- `previewTemplate()` - Preview templates with sample data

#### Features

- **Fallback Support**: Automatic fallback to basic HTML if templates fail
- **Variable Management**: Automatic variable injection and validation
- **Template Discovery**: Automatic template scanning and listing
- **Sample Data**: Built-in sample data for template previews

## Email Templates

### Available Templates

1. **Installment Reminders** (`mail.notification.installment.reminder`)
2. **Overdue Notifications** (`mail.notification.installment.overdue`)
3. **Welcome Emails** (`mail.notification.welcome`)
4. **Payment Confirmations** (`mail.notification.payment.confirmation`)
5. **Course Completion** (`mail.notification.course.completion`)
6. **System Maintenance** (`mail.notification.system.maintenance`)

### Template Structure

All templates extend `mail.layout.app` and use consistent styling:

```blade
@extends('mail.layout.app')

@section('content')
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <!-- Template content -->
    </div>
@endsection
```

### Template Variables

Each template type has specific variables available:

#### Installment Reminder

- `student` - Student model
- `installment` - Installment model
- `days` - Days until due
- `dueDate` - Formatted due date
- `amount` - Formatted amount
- `urgencyText` - Urgency level text

#### Welcome Email

- `studentName` - Student's full name
- `courseInfo` - Course information array

#### Payment Confirmation

- `studentName` - Student's full name
- `paymentDetails` - Payment information array

## Usage Examples

### 1. Sending Welcome Emails

```php
use App\Services\EmailNotificationService;

$emailService = new EmailNotificationService();

$courseInfo = [
    'name' => 'Web Development Fundamentals',
    'enrollment_date' => now()->format('d/m/Y'),
    'instructor' => 'Prof. Sarah Johnson'
];

$result = $emailService->sendWelcomeEmail($student, $courseInfo);
```

### 2. Sending Payment Confirmations

```php
$paymentDetails = [
    'amount' => '500.00',
    'date' => now()->format('d/m/Y'),
    'transaction_id' => 'TXN123456',
    'next_due_date' => now()->addMonth()->format('d/m/Y')
];

$result = $emailService->sendPaymentConfirmation($student, $paymentDetails);
```

### 3. Sending Bulk Notifications

```php
// System maintenance notification
$emails = ['admin@tiitvt.com', 'support@tiitvt.com', 'tech@tiitvt.com'];
$maintenanceDetails = 'Database optimization and security updates';
$scheduledTime = now()->addDay()->format('d/m/Y H:i');

$results = $emailService->sendMaintenanceNotification(
    $emails,
    $maintenanceDetails,
    $scheduledTime
);
```

### 4. Scheduling Notifications

```php
use Carbon\Carbon;

// Schedule welcome email for tomorrow
$sendAt = Carbon::tomorrow()->at('09:00');

$result = $emailService->scheduleNotification(
    'welcome_email',
    'student@example.com',
    ['studentName' => 'John Doe'],
    $sendAt
);
```

### 5. Using Template Helpers

```php
use App\Helpers\MailTemplateHelper;

// Preview a template
$preview = MailTemplateHelper::previewTemplate(
    'mail.notification.welcome',
    'welcome_email'
);

// Validate template data
$data = ['studentName' => 'John Doe'];
$errors = MailTemplateHelper::validateTemplateData('welcome_email', $data);

// Check if template exists
$exists = MailTemplateHelper::templateExists('mail.notification.welcome');
```

## Configuration

### Environment Variables

Ensure these are set in your `.env` file:

```env
# Mail Configuration
MAIL_MAILER=smtp
MAIL_HOST=your-smtp-host
MAIL_PORT=587
MAIL_USERNAME=your-username
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@tiitvt.com
MAIL_FROM_NAME="TIITVT System"

# Application Mail Addresses
APP_MAIL_TO_ADDRESS=admin@tiitvt.com
APP_MAIL_BACKUP_ADDRESS=backup@tiitvt.com
APP_MAIL_SUPPORT_ADDRESS=support@tiitvt.com
```

### Queue Configuration

For optimal performance, configure Laravel queues:

```env
QUEUE_CONNECTION=database
```

Then run:

```bash
php artisan queue:table
php artisan migrate
php artisan queue:work
```

## Best Practices

### 1. Use Type-Based Notifications

Instead of manually creating subjects and bodies:

```php
// ❌ Don't do this
$subject = 'Welcome to TIITVT';
$body = view('mail.welcome', $data)->render();
MailHelper::sendNotification($email, $subject, $body);

// ✅ Do this instead
EmailNotificationHelper::sendNotificationByType(
    'welcome_email',
    $email,
    $data
);
```

### 2. Queue Non-Critical Emails

```php
// For critical notifications (overdue, urgent)
$options = ['queue' => false];

// For regular notifications (welcome, confirmations)
$options = ['queue' => true];
```

### 3. Handle Errors Gracefully

```php
try {
    $result = EmailNotificationHelper::sendNotificationByType(
        'welcome_email',
        $email,
        $data
    );
    
    if (!$result) {
        Log::warning("Failed to send welcome email to {$email}");
        // Handle failure gracefully
    }
} catch (\Exception $e) {
    Log::error("Exception sending welcome email", [
        'email' => $email,
        'error' => $e->getMessage()
    ]);
    // Handle exception
}
```

### 4. Use Template Validation

```php
// Validate data before sending
$errors = MailTemplateHelper::validateTemplateData('welcome_email', $data);

if (!empty($errors)) {
    Log::error("Template validation failed", ['errors' => $errors]);
    return false;
}
```

### 5. Monitor Email Statistics

```php
// Get email statistics
$stats = MailHelper::getEmailStatistics();

// Get notification-specific statistics
$notificationStats = EmailNotificationHelper::getNotificationStatistics(
    'welcome_email',
    null,
    now()->subMonth(),
    now()
);
```

## Troubleshooting

### Common Issues

#### 1. Emails Not Sending

Check your mail configuration:

```bash
php artisan tinker
MailHelper::testEmailConfiguration('your-email@example.com');
```

#### 2. Template Rendering Errors

Use template validation:

```php
$errors = MailTemplateHelper::validateTemplateData('welcome_email', $data);
```

#### 3. Queue Issues

Check queue status:

```bash
php artisan queue:work --verbose
php artisan queue:failed
```

#### 4. Missing Templates

List available templates:

```php
$templates = MailTemplateHelper::getAvailableTemplates();
```

### Debug Mode

Enable debug logging in your `.env`:

```env
APP_DEBUG=true
LOG_LEVEL=debug
```

### Testing

Test email functionality:

```php
// Test basic email
$result = MailHelper::testEmailConfiguration('test@example.com');

// Test specific notification type
$result = EmailNotificationHelper::sendNotificationByType(
    'welcome_email',
    'test@example.com',
    ['studentName' => 'Test User']
);
```

## Migration from Old System

### Before (Old MailHelper Usage)

```php
// Old way
MailHelper::sendInstallmentReminder(
    $student->email,
    $subject,
    $body,
    $installment
);
```

### After (New EmailNotificationHelper Usage)

```php
// New way
EmailNotificationHelper::sendNotificationByType(
    'installment_reminder',
    $student->email,
    [
        'student' => $student,
        'installment' => $installment,
        'days' => $days,
        'dueDate' => $dueDate,
        'amount' => $amount
    ]
);
```

## Performance Considerations

### Queue Usage

- **Immediate**: Critical notifications (overdue, urgent)
- **Queued**: Regular notifications (welcome, confirmations)
- **Scheduled**: Future notifications (reminders, announcements)

### Rate Limiting

For bulk emails, use delays to avoid overwhelming mail servers:

```php
MailHelper::sendBulkNotification(
    $emails,
    $subject,
    $body,
    [],
    [],
    2 // 2 second delay between emails
);
```

### Template Caching

Templates are automatically cached by Laravel. For production, ensure:

```bash
php artisan view:cache
```

## Support

For issues or questions:

1. Check the logs in `storage/logs/laravel.log`
2. Use the test methods to verify configuration
3. Validate template data before sending
4. Check queue status for queued emails

## Conclusion

The new email notification system provides:

- **Better Organization**: Type-based notification management
- **Improved Reliability**: Fallback templates and error handling
- **Enhanced Performance**: Queue support and rate limiting
- **Easier Maintenance**: Centralized template management
- **Better Monitoring**: Comprehensive logging and statistics

This system is designed to be scalable, maintainable, and user-friendly while providing robust email notification capabilities for the TIITVT backend application.
