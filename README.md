# TIITVT Backend - IT Classes Management System

<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## 📋 Table of Contents

- [Overview](#overview)
- [System Architecture](#system-architecture)
- [Features](#features)
- [Email Notification System](#email-notification-system)
- [Installment System](#installment-system)
- [Question Seeder](#question-seeder)
- [Cron Jobs Setup](#cron-jobs-setup)
- [Setup Instructions](#setup-instructions)
- [Usage Examples](#usage-examples)
- [Configuration](#configuration)
- [Troubleshooting](#troubleshooting)

## 🎯 Overview

TIITVT Backend is a comprehensive SaaS-based IT classes management system built with Laravel + Livewire Volt and Spatie Roles & Permissions. The system manages centers, students, courses, exams, certificates, and financial transactions with automated email notifications and installment tracking.

## 🏗️ System Architecture

### User Types & Roles

- **Admin**: Full system access, manages centers, students, courses, exams, certificates
- **Center**: Views students assigned to them, manages their center information
- **Student**: Views courses, takes exams, views results and certificates

### Core Models

#### 1. User (extends Laravel's default with Spatie roles)

- **Fields**: name, email, password
- **Traits**: HasRoles (Spatie)
- **Relationships**:
  - `hasMany` centers
  - `hasMany` students
  - `hasMany` declaredExamResults

#### 2. Category

- **Fields**: name, description, status
- **Relationships**:
  - `hasMany` subcategories
  - `hasMany` courses

#### 3. Subcategory

- **Fields**: category_id, name, description, status
- **Relationships**:
  - `belongsTo` category

#### 4. Course

- **Fields**: category_id, name, description, duration, fee, status
- **Relationships**:
  - `belongsTo` category
  - `hasMany` students
  - `hasMany` exams
  - `hasMany` certificates

#### 5. Center

- **Fields**: user_id, name, phone, address, status
- **Relationships**:
  - `belongsTo` user
  - `hasMany` students

#### 6. Student

- **Fields**: center_id, course_id, user_id, name, phone, address, status, fee, join_date
- **Relationships**:
  - `belongsTo` center
  - `belongsTo` course
  - `belongsTo` user
  - `hasMany` examResults
  - `hasMany` invoices
  - `hasMany` certificates
  - `hasMany` installments

#### 7. Exam

- **Fields**: course_id, title, duration, is_active
- **Relationships**:
  - `belongsTo` course
  - `hasMany` questions
  - `hasMany` examResults

#### 8. Question

- **Fields**: exam_id, question_text, correct_option_id, points
- **Relationships**:
  - `belongsTo` exam
  - `belongsTo` correctOption (Option)
  - `hasMany` options

#### 9. ExamResult

- **Fields**: student_id, exam_id, score, result_status, declared_by, declared_at, answers (JSON)
- **Relationships**:
  - `belongsTo` student
  - `belongsTo` exam
  - `belongsTo` declaredBy (User)

#### 10. Invoice

- **Fields**: student_id, amount, status, issued_at, paid_at, invoice_number, description
- **Relationships**:
  - `belongsTo` student

#### 11. Certificate

- **Fields**: student_id, course_id, issued_on, pdf_path, qr_token, qr_code_path, certificate_number, status
- **Relationships**:
  - `belongsTo` student
  - `belongsTo` course

#### 12. Installment

- **Fields**: student_id, installment_no, amount, due_date, status, paid_date, paid_amount, notes
- **Relationships**:
  - `belongsTo` student

## 🔧 Service Classes

### 1. ExamService (`app/Services/ExamService.php`)

**Purpose**: Handles exam creation, evaluation, and result management

**Key Methods**:

- `createExam(array $data)`: Create exam with questions
- `evaluateExam(Student $student, Exam $exam, array $answers)`: Evaluate student answers
- `getStudentExamResults(Student $student)`: Get all results for a student
- `getExamStatistics(Exam $exam)`: Get exam performance statistics
- `toggleExamStatus(Exam $exam)`: Activate/deactivate exam

### 2. CertificateService (`app/Services/CertificateService.php`)

**Purpose**: Handles certificate issuance, QR code generation, and verification

**Key Methods**:

- `issueCertificate(Student $student, Course $course)`: Issue new certificate
- `generateQrToken()`: Generate unique QR token
- `generateCertificateNumber()`: Generate unique certificate number
- `verifyCertificate(string $token)`: Verify certificate by QR token
- `revokeCertificate(Certificate $certificate)`: Revoke certificate
- `getCertificateStatistics()`: Get certificate statistics

### 3. InvoiceService (`app/Services/InvoiceService.php`)

**Purpose**: Handles invoice creation, payment tracking, and financial reporting

**Key Methods**:

- `createInvoice(Student $student, array $data)`: Create new invoice
- `generateInvoiceNumber()`: Generate unique invoice number
- `markAsPaid(Invoice $invoice)`: Mark invoice as paid
- `getInvoiceStatistics()`: Get financial statistics
- `bulkCreateInvoices(array $students, array $data)`: Create multiple invoices
- `sendPaymentReminders()`: Send reminders for unpaid invoices

## 🚀 Features

### Core Functionality

- **User Management**: Role-based access control with Spatie Roles & Permissions
- **Center Management**: Multi-center support with individual center dashboards
- **Course Management**: Category-based course organization with subcategories
- **Student Management**: Comprehensive student profiles with enrollment tracking
- **Exam System**: Multi-question exams with automated evaluation
- **Certificate Generation**: QR-coded certificates with verification system
- **Financial Management**: Invoice generation and payment tracking
- **Installment System**: Flexible payment plans with automated reminders

### Advanced Features

- **Email Notifications**: Comprehensive email system with templates and queuing
- **Automated Reminders**: Cron-based installment reminders and overdue handling
- **Question Seeding**: Automated database population with sample questions
- **QR Code Integration**: Certificate verification via QR codes
- **Bulk Operations**: Mass email sending and invoice generation
- **Reporting**: Comprehensive statistics and financial reporting

## 📧 Email Notification System

### Overview

The email notification system has been significantly enhanced with better organization, enhanced functionality, and improved reliability.

### What Was Implemented

#### 1. Enhanced MailHelper Class (`app/Helpers/MailHelper.php`)

**New Features Added:**

- **Queue Support**: All email methods now support queuing for better performance
- **Rate Limiting**: Bulk emails with configurable delays to prevent server overload
- **Enhanced Error Handling**: Better logging with stack traces and detailed error information
- **Additional Methods**: New methods for various email types (welcome, payment confirmation, course completion, etc.)
- **Bulk Email Support**: Efficient bulk email sending with rate limiting
- **System Maintenance Notifications**: Support for system-wide announcements
- **Email Testing**: Built-in email configuration testing
- **Statistics**: Email statistics and queue monitoring

**Key Methods Enhanced:**

- `sendInstallmentReminder()` - Now supports queuing and better error handling
- `sendOverdueReminder()` - Enhanced with queue support and improved logging
- `sendNotification()` - Added queue support and better CC/BCC management
- `sendBulkNotification()` - New method for bulk emails with rate limiting
- `sendWelcomeEmail()` - New method for student welcome emails
- `sendPaymentConfirmation()` - New method for payment confirmations
- `sendCourseCompletionNotification()` - New method for course completions
- `sendMaintenanceNotification()` - New method for system maintenance
- `testEmailConfiguration()` - New method for testing email setup
- `getEmailStatistics()` - New method for monitoring email performance

#### 2. New EmailNotificationHelper Class (`app/Helpers/EmailNotificationHelper.php`)

**Purpose:** Advanced notification management with type-based templates and configuration.

**Key Features:**

- **Type-Based Templates**: Automatic template selection based on notification type
- **Priority Management**: High-priority notifications sent immediately, others queued
- **Opt-out Support**: Framework for user preference management
- **Scheduling**: Future delivery scheduling capabilities
- **Personalization**: Bulk notifications with personal data
- **Configuration Management**: Enable/disable notification types globally
- **Statistics**: Notification-specific statistics and monitoring

**Supported Notification Types:**

- `installment_reminder` - Installment payment reminders
- `overdue_notification` - Overdue payment notifications
- `payment_confirmation` - Payment confirmations
- `welcome_email` - Welcome emails for new students
- `course_completion` - Course completion notifications
- `system_maintenance` - System maintenance announcements

#### 3. New MailTemplateHelper Class (`app/Helpers/MailTemplateHelper.php`)

**Purpose:** Template rendering and management utilities with fallback support.

**Key Features:**

- **Fallback Support**: Automatic fallback to basic HTML if templates fail
- **Variable Management**: Automatic variable injection and validation
- **Template Discovery**: Automatic template scanning and listing
- **Sample Data**: Built-in sample data for template previews
- **Validation**: Template data validation before rendering
- **Preview**: Template preview functionality for testing

#### 4. New Email Templates

**Created Templates:**

1. **Welcome Email** (`resources/views/mail/notification/welcome.blade.php`)
2. **Payment Confirmation** (`resources/views/mail/notification/payment/confirmation.blade.php`)
3. **Course Completion** (`resources/views/mail/notification/course/completion.blade.php`)
4. **System Maintenance** (`resources/views/mail/notification/system/maintenance.blade.php`)

#### 5. New EmailNotificationService Class (`app/Services/EmailNotificationService.php`)

**Purpose:** High-level service class that provides easy-to-use methods for common email operations.

#### 6. New SendScheduledNotification Job (`app/Jobs/SendScheduledNotification.php`)

**Purpose:** Handle scheduled email notifications using Laravel's queue system.

### System Architecture

#### Before (Old System)
```
Services → MailHelper → NotificationMail → Email Templates
```

#### After (New System)
```
Services → EmailNotificationService → EmailNotificationHelper → MailHelper → NotificationMail → Email Templates
                ↓
        MailTemplateHelper (Template Management)
                ↓
        SendScheduledNotification Job (Queue System)
```

### Benefits of the New System

1. **Better Organization**: Type-based notification management
2. **Improved Reliability**: Fallback templates and comprehensive error handling
3. **Enhanced Performance**: Email queuing and rate limiting
4. **Easier Maintenance**: Centralized configuration and template management
5. **Better Monitoring**: Detailed logging and statistics
6. **Scalability**: Support for bulk notifications and queue-based processing

## 💰 Installment System

### Overview

The installment system has been refactored from storing installment amounts in the students table to a separate installments table with individual records and future due dates.

### Changes Made

#### 1. Database Structure

**New Table: `installments`**

- **File**: `database/migrations/2025_01_01_000011_create_installments_table.php`
- **Fields**:
  - `id` - Primary key
  - `student_id` - Foreign key to students table
  - `installment_no` - Sequential installment number
  - `amount` - Installment amount (decimal)
  - `due_date` - Future due date for the installment
  - `status` - Enum: pending, paid, overdue
  - `paid_date` - Date when installment was paid (nullable)
  - `paid_amount` - Amount actually paid (nullable)
  - `notes` - Additional notes (nullable)
  - `timestamps` - Created and updated timestamps

#### 2. Models

**New Model: `Installment`**

- **File**: `app/Models/Installment.php`
- **Features**:
  - Relationship with Student model
  - Status management (pending, paid, overdue)
  - Helper methods for status checks and formatting
  - Scopes for filtering by status
  - Methods to mark as paid or overdue

#### 3. Key Benefits

1. **Better Data Management**: Each installment is now a separate record with its own status and payment tracking
2. **Future Date Support**: Installments can have specific due dates that extend into the future
3. **Payment Tracking**: Individual installment status (pending, paid, overdue) for better financial management
4. **Scalability**: Easy to add features like payment reminders, overdue notifications, etc.
5. **Data Integrity**: Proper foreign key relationships and constraints

### How It Works

1. **Student Creation**: When a student is created with installments:
   - Student record is saved to `students` table
   - Individual installment records are created in `installments` table
   - Each installment gets a calculated amount and future due date

2. **Installment Calculation**:
   - Remaining amount (after down payment) is divided by number of installments
   - Last installment may vary slightly to account for rounding
   - Due dates are calculated by adding months to the base installment date

3. **Display**:
   - Fees Summary still shows calculated values for user reference
   - Individual installments are displayed with their status and due dates
   - Easy to track which installments are pending, paid, or overdue

## 📚 Question Seeder

### Overview

The Question Seeder creates sample questions with multiple-choice options across different categories. Each question includes:

- Question text
- 4 multiple-choice options
- Correct answer identification
- Point values (1, 2, 3, or 5 points)
- Category association

### Files Created

1. **`database/seeders/QuestionSeeder.php`** - Main seeder integrated with DatabaseSeeder
2. **`database/seeders/QuestionOnlySeeder.php`** - Standalone seeder for testing
3. **`database/factories/QuestionFactory.php`** - Factory for generating random question data
4. **`database/factories/OptionFactory.php`** - Factory for generating random option data

### Usage

#### Option 1: Run All Seeders
```bash
php artisan db:seed
```

#### Option 2: Run Only Question Seeder
```bash
php artisan db:seed --class=QuestionSeeder
```

#### Option 3: Run Standalone Question Seeder
```bash
php artisan db:seed --class=QuestionOnlySeeder
```

### Sample Questions Created

The seeder creates questions in the following categories:

- **Computer Science & IT**: HTML purpose, JavaScript, CSS, server-side programming
- **Business & Management**: SWOT analysis, management functions, marketing goals
- **Finance & Accounting**: Accounting equation, financial statements, ROI
- **Marketing & Sales**: Marketing mix (4 Ps), marketing fundamentals, market research

### Points System

Questions use a weighted points system:

- **1 point**: Basic knowledge questions
- **2 points**: Standard difficulty questions
- **3 points**: Advanced knowledge questions
- **5 points**: Expert-level questions

## ⏰ Cron Jobs Setup

### Overview

The system includes two main cron jobs:

1. **Installment Reminders**: Sends reminders 7, 5, 3, 2, and 1 day before due date
2. **Overdue Handling**: Updates overdue status and sends reminders at 0, 3, 5, 7, and 15 days after due date

### Commands Available

#### 1. Installment Reminders
```bash
php artisan installments:send-reminders
```

- **Purpose**: Sends reminder emails to students before installment due dates
- **Frequency**: Should run daily (recommended: 9:00 AM)
- **Logic**: Checks for installments due in 7, 5, 3, 2, and 1 days

#### 2. Overdue Installment Handling
```bash
php artisan installments:handle-overdue
```

- **Purpose**: Updates overdue statuses and sends overdue reminder emails
- **Frequency**: Should run daily (recommended: 10:00 AM)
- **Logic**:
  - Updates pending installments past due date to 'overdue' status
  - Sends overdue reminders at 0, 3, 5, 7, and 15 days after due date

### Cron Job Configuration

#### Linux/Unix Cron Setup

Add these lines to your crontab (`crontab -e`):

```bash
# Installment reminders - run daily at 9:00 AM
0 9 * * * cd /path/to/your/project && php artisan installments:send-reminders >> /var/log/installment-reminders.log 2>&1

# Overdue handling - run daily at 10:00 AM
0 10 * * * cd /path/to/your/project && php artisan installments:handle-overdue >> /var/log/overdue-handling.log 2>&1
```

#### Using Laravel Task Scheduler (Recommended)

Add this to your `routes/console.php`:

```php
<?php

use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Artisan;

// Installment reminders - daily at 9:00 AM
Schedule::command('installments:send-reminders')
    ->daily()
    ->at('09:00')
    ->appendOutputTo(storage_path('logs/installment-reminders.log'));

// Overdue handling - daily at 10:00 AM
Schedule::command('installments:handle-overdue')
    ->daily()
    ->at('10:00')
    ->appendOutputTo(storage_path('logs/overdue-handling.log'));
```

Then set up a single cron job to run the scheduler:

```bash
# Run Laravel scheduler every minute
* * * * * cd /path/to/your/project && php artisan schedule:run >> /dev/null 2>&1
```

## 🚀 Setup Instructions

### 1. Install Dependencies

```bash
composer install
npm install
```

### 2. Environment Setup

```bash
cp .env.example .env
php artisan key:generate
```

### 3. Database Setup

```bash
php artisan migrate
php artisan db:seed
```

### 4. Storage Setup

```bash
php artisan storage:link
```

### 5. Optional: QR Code Package

For QR code generation in certificates:

```bash
composer require simplesoftwareio/simple-qrcode
```

### 6. Queue Setup (for email notifications)

```bash
php artisan queue:table
php artisan migrate
php artisan queue:work
```

## 📖 Usage Examples

### Email Notifications

#### Send Welcome Email
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

#### Send Payment Confirmation
```php
$paymentDetails = [
    'amount' => '500.00',
    'date' => now()->format('d/m/Y'),
    'transaction_id' => 'TXN123456',
    'next_due_date' => now()->addMonth()->format('d/m/Y')
];

$result = $emailService->sendPaymentConfirmation($student, $paymentDetails);
```

#### Use Type-Based Notifications
```php
use App\Helpers\EmailNotificationHelper;

$result = EmailNotificationHelper::sendNotificationByType(
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

### Question Management

#### Generate Questions with Factory
```php
// Generate a single question with options
$question = Question::factory()->create();
$options = Option::factory()->count(4)->create(['question_id' => $question->id]);

// Generate multiple questions
$questions = Question::factory()->count(10)->create();
```

## ⚙️ Configuration

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

# Queue Configuration
QUEUE_CONNECTION=database

# Application Settings
APP_DEBUG=true
LOG_LEVEL=debug
```

### Mail Configuration

The system supports various mail drivers:

- **SMTP**: For production use
- **Mailgun**: For high-volume sending
- **SES**: For AWS integration
- **Log**: For development/testing

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

## 🔧 Testing

### Email Testing

Test email functionality:

```bash
# Test basic email
php artisan email:test welcome user@example.com

# Test payment confirmation
php artisan email:test payment user@example.com

# Test template helper
php artisan email:test template --template=welcome_email

# Show email statistics
php artisan email:test stats
```

### Manual Testing

Test the cron commands manually before setting up cron jobs:

```bash
# Test installment reminders
php artisan installments:send-reminders

# Test overdue handling
php artisan installments:handle-overdue
```

## 🐛 Troubleshooting

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

#### 5. Cron Job Issues

- Verify cron job syntax
- Check file permissions
- Ensure correct project path
- Check cron logs

### Debug Mode

Enable debug logging by setting `APP_DEBUG=true` in your `.env` file for detailed error information.

### Logs

Monitor the logs for successful execution:

```bash
# Check Laravel logs
tail -f storage/logs/laravel.log

# Check cron job logs (if using direct cron)
tail -f /var/log/installment-reminders.log
tail -f /var/log/overdue-handling.log
```

## 📁 Folder Structure

```
app/
├── Models/
│   ├── User.php (with Spatie roles)
│   ├── Category.php
│   ├── Subcategory.php
│   ├── Course.php
│   ├── Center.php
│   ├── Student.php
│   ├── Exam.php
│   ├── Question.php
│   ├── ExamResult.php
│   ├── Invoice.php
│   ├── Certificate.php
│   └── Installment.php
├── Services/
│   ├── ExamService.php
│   ├── CertificateService.php
│   ├── InvoiceService.php
│   └── EmailNotificationService.php
├── Helpers/
│   ├── MailHelper.php
│   ├── EmailNotificationHelper.php
│   └── MailTemplateHelper.php
├── Jobs/
│   └── SendScheduledNotification.php
├── Http/
│   ├── Controllers/
│   ├── Middleware/
│   │   ├── AdminAuthMiddleware.php
│   │   └── RoleMiddleware.php
│   └── Requests/
├── Enums/
│   └── RolesEnum.php
└── Livewire/ (for Volt components)

database/
├── migrations/
│   ├── 2025_01_01_000001_create_categories_table.php
│   ├── 2025_01_01_000002_create_subcategories_table.php
│   ├── 2025_01_01_000003_create_courses_table.php
│   ├── 2025_01_01_000004_create_centers_table.php
│   ├── 2025_01_01_000005_create_students_table.php
│   ├── 2025_01_01_000006_create_exams_table.php
│   ├── 2025_01_01_000007_create_questions_table.php
│   ├── 2025_01_01_000008_create_exam_results_table.php
│   ├── 2025_01_01_000009_create_invoices_table.php
│   ├── 2025_01_01_000010_create_certificates_table.php
│   └── 2025_01_01_000011_create_installments_table.php
└── seeders/
    ├── DatabaseSeeder.php
    ├── UserWithRoleSeeder.php
    ├── SystemDataSeeder.php
    ├── QuestionSeeder.php
    └── QuestionOnlySeeder.php

resources/
├── views/
│   ├── mail/
│   │   ├── layout/
│   │   │   └── app.blade.php
│   │   └── notification/
│   │       ├── welcome.blade.php
│   │       ├── installment/
│   │       │   ├── reminder.blade.php
│   │       │   └── overdue.blade.php
│   │       ├── payment/
│   │       │   └── confirmation.blade.php
│   │       ├── course/
│   │       │   └── completion.blade.php
│   │       └── system/
│   │           └── maintenance.blade.php
│   └── livewire/
│       └── backend/
│           ├── student/
│           │   ├── create.blade.php
│           │   ├── show.blade.php
│           │   └── edit.blade.php
│           └── ...

routes/
├── web.php (with role-protected groups)
├── auth.php
└── console.php (for scheduled tasks)
```

## 🔐 Authentication & Authorization

### Spatie Roles & Permissions

- **Roles**: admin, center, student
- **Middleware**: `role:admin`, `role:center`, `role:student`
- **User Methods**: `isAdmin()`, `isCenter()`, `isStudent()`

### Route Protection

```php
// Admin routes
Route::middleware(['auth', 'role:admin'])->group(function () {
    // Admin-only routes
});

// Center routes
Route::middleware(['auth', 'role:center'])->group(function () {
    // Center-only routes
});

// Student routes
Route::middleware(['auth', 'role:student'])->group(function () {
    // Student-only routes
});
```

## 📊 Sample Data

The `SystemDataSeeder` creates:

- 3 categories (Programming, Web Development, Data Science)
- 3 courses (PHP, Laravel, Python)
- 2 centers with users
- 2 students with users
- Sample exams with questions
- Sample invoices

## 🔗 Public Routes

### Certificate Verification

- **URL**: `/certificate/verify/{token}`
- **Purpose**: Public verification of certificates via QR code
- **Response**: Certificate details or 404 if not found/revoked

## 🎯 Next Steps

1. **Controllers**: Create RESTful controllers for each model
2. **Form Requests**: Create validation rules for data input
3. **Livewire Volt Components**: Build UI components for each feature
4. **PDF Generation**: Implement actual PDF certificate generation
5. **API Routes**: Create API endpoints if needed
6. **Testing**: Write unit and feature tests
7. **Performance Optimization**: Implement caching and database optimization
8. **Monitoring**: Set up application monitoring and alerting

## 🔧 Configuration Notes

- **Passing Score**: Currently set to 70% in ExamService
- **QR Code Size**: 300px with 10px margin
- **Invoice Number Format**: INV-YYYYMM-XXXXXX
- **Certificate Number Format**: CERT-YYYY-XXXXXX
- **File Storage**: Uses Laravel's public disk for certificates and QR codes
- **Email Rate Limiting**: Configurable delays for bulk emails
- **Queue Retries**: 3 attempts with 60-second backoff for failed jobs

## 📚 Additional Documentation

For detailed information on specific components, refer to:

- **Email System**: `EMAIL_NOTIFICATION_HELPERS_README.md` - Comprehensive usage guide
- **Testing**: `php artisan email:test` command for testing all functionality
- **Logs**: Check `storage/logs/laravel.log` for detailed error information
- **Templates**: All email templates are located in `resources/views/mail/notification/`

## 🤝 Contributing

Thank you for considering contributing to the TIITVT Backend system! 

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests if applicable
5. Submit a pull request

## 📄 License

The TIITVT Backend system is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## 🆘 Support

For technical support or questions:

1. Check the logs in `storage/logs/laravel.log`
2. Use the test methods to verify configuration
3. Validate template data before sending
4. Check queue status for queued emails
5. Refer to the comprehensive documentation above

## 🏁 Conclusion

The TIITVT Backend system provides:

- **Comprehensive Management**: Complete IT classes management with role-based access
- **Advanced Email System**: Professional email notifications with templates and queuing
- **Flexible Installments**: Automated payment tracking and reminders
- **Robust Architecture**: Scalable Laravel-based system with proper separation of concerns
- **Easy Maintenance**: Centralized configuration and comprehensive documentation
- **Production Ready**: Includes testing, monitoring, and troubleshooting tools

This system is designed to be scalable, maintainable, and user-friendly while providing robust capabilities for managing IT education centers and their operations.
