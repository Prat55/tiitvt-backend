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
- [Website Settings Management](#website-settings-management)
- [QR Code Integration](#qr-code-integration)
- [Multiple Course Selection](#multiple-course-selection)
- [Email Notification System](#email-notification-system)
- [Installment System](#installment-system)
- [Question Seeder](#question-seeder)
- [Cron Jobs Setup](#cron-jobs-setup)
- [Setup Instructions](#setup-instructions)
- [Usage Examples](#usage-examples)
- [Configuration](#configuration)
- [Commands Reference](#commands-reference)
- [Testing](#testing)
- [Troubleshooting](#troubleshooting)

## 🎯 Overview

TIITVT Backend is a comprehensive SaaS-based IT classes management system built with Laravel + Livewire Volt and Spatie Roles & Permissions. The system manages centers, students, courses, exams, certificates, and financial transactions with automated email notifications, installment tracking, and centralized branding management.

## 🏗️ System Architecture

### User Types & Roles

- **Admin**: Full system access, manages centers, students, courses, exams, certificates, website settings
- **Center**: Views students assigned to them, manages their center information
- **Student**: Views courses, takes exams, views results and certificates

### Core Models

#### 1. User (extends Laravel's default with Spatie roles)

- **Fields**: name, email, password, phone, image, is_active
- **Traits**: HasRoles (Spatie)
- **Relationships**:
  - `hasMany` centers
  - `hasMany` students
  - `hasMany` declaredExamResults

#### 2. WebsiteSetting (NEW)

- **Fields**: website_name, logo, logo_dark, favicon, qr_code_image, meta_title, meta_keywords, meta_description, meta_author, primary_email, secondary_email, primary_phone, secondary_phone, address, facebook_url, twitter_url, instagram_url, linkedin_url
- **Used for**: Centralized branding and configurations

#### 3. Category

- **Fields**: name, description, status
- **Relationships**:
  - `hasMany` subcategories
  - `hasMany` courses

#### 4. Subcategory

- **Fields**: category_id, name, description, status
- **Relationships**:
  - `belongsTo` category

#### 5. Course

- **Fields**: category_id, name, description, duration, price, status, meta_title, meta_description, meta_keywords
- **Relationships**:
  - `belongsTo` category
  - `belongsToMany` students (via student_courses pivot table)
  - `hasMany` exams
  - `hasMany` certificates
  - `hasMany` studentCourses (pivot model)

#### 6. Center

- **Fields**: user_id, name, phone, address, status
- **Relationships**:
  - `belongsTo` user
  - `hasMany` students

#### 7. Student

- **Fields**: center_id, user_id, tiitvt_reg_no, first_name, fathers_name, surname, address, telephone_no, email, mobile, date_of_birth, age, qualification, additional_qualification, reference, course_fees, down_payment, no_of_installments, installment_date, enrollment_date, student_image, student_signature_image, incharge_name
- **Relationships**:
  - `belongsTo` center
  - `belongsToMany` courses (via student_courses pivot table)
  - `belongsTo` user
  - `hasMany` examResults
  - `hasMany` invoices
  - `hasMany` certificates
  - `hasMany` installments
  - `hasMany` studentCourses (pivot model)

#### 7.1. StudentCourse (Pivot Model)

- **Fields**: student_id, course_id, enrollment_date, course_taken, batch_time, scheme_given, incharge_name
- **Relationships**:
  - `belongsTo` student
  - `belongsTo` course

#### 8. Exam

- **Fields**: course_id, title, duration, is_active
- **Relationships**:
  - `belongsTo` course
  - `hasMany` questions
  - `hasMany` examResults

#### 9. Question

- **Fields**: exam_id, question_text, correct_option_id, points
- **Relationships**:
  - `belongsTo` exam
  - `belongsTo` correctOption (Option)
  - `hasMany` options

#### 10. ExamResult

- **Fields**: student_id, exam_id, score, result_status, declared_by, declared_at, answers (JSON)
- **Relationships**:
  - `belongsTo` student
  - `belongsTo` exam
  - `belongsTo` declaredBy (User)

#### 11. Invoice

- **Fields**: student_id, amount, status, issued_at, paid_at, invoice_number, description
- **Relationships**:
  - `belongsTo` student

#### 12. Certificate

- **Fields**: student_id, course_id, issued_on, pdf_path, qr_token, qr_code_path, certificate_number, status
- **Relationships**:
  - `belongsTo` student
  - `belongsTo` course

## 🌟 Features

- **Multi-tenant System**: Separate centers with their own students
- **Multiple Course Selection**: Students can enroll in multiple courses simultaneously
- **Single Fee Structure**: Unified fee management across all enrolled courses
- **Exam Management**: Create exams with questions and options
- **Certification**: Generate certificates with QR codes
- **Installment Tracking**: Manage payment installments
- **Email Notifications**: Automated notifications for various events
- **QR Code System**: Student verification and certificate verification
- **Website Settings Management**: Centralized branding and configuration
- **Dynamic Content**: Logo, favicon, and QR codes managed through dashboard

## 🎨 Website Settings Management

The system includes a comprehensive website settings management system that allows administrators to centrally manage branding, content, and configuration.

### Features

#### 1. Image Management

- **Website Logo**: Upload custom logo for website header
- **Dark Logo**: Separate logo for dark theme
- **Favicon**: Custom favicon for browser tabs
- **QR Code Image**: Logo/watermark used in QR codes across the system

#### 2. SEO & Meta Information

- Website name and title
- Meta descriptions and keywords
- Google-friendly optimization

#### 3. Contact Information

- Primary and secondary email addresses
- Primary and secondary phone numbers
- Full contact address

#### 4. Social Media Integration

- Facebook, Twitter, Instagram, LinkedIn profiles
- Automatic integration with frontend

### Usage

Navigate to **Admin Panel → Website Settings** to manage all configurations:

1. **General Settings**: Basic website information and meta tags
2. **Images & Branding**: Upload logos, favicon, and QR code image
3. **Contact Information**: Email, phone, and address details
4. **Social Media**: Social platform links

## 🔗 QR Code Integration

### Overview

Advanced QR code system with centralized logo management. All QR codes (student verification, certificates) automatically use the QR code image from website settings.

### Features

- **Centralized Logo Management**: Single upload updates all QR codes
- **Dynamic Generation**: QR codes generated with current logo settings
- **Bulk Operations**: Regenerate all existing QR codes with new logo
- **Auto-fallback**: Graceful fallback to default logo if none uploaded

### Services Enhanced

#### StudentQRService

- `generateQrCodeWithLogo()` - Main QR generation
- `generateEnhancedQRCode()` - Enhanced QR with labels
, `generateQRCodeDataUri()` - Data URI generation
- `regenerateStudentQRWithFreshLogo()` - Update existing QR codes
- `regenerateAllStudentQRsWithFreshLogo()` - Bulk regeneration

#### CertificateService

- `generateQrCode()` - Certificate verification QR codes

### How QR Logo Integration Works

```php
// Services automatically use website settings logo
$qrService = app(StudentQRService::class);
$studentQR = $qrService->generateStudentQR($student); // Uses custom logo automatically

// For certificates
$certService = app(CertificateService::class);
$certificate = $certService->issueCertificate($student, $course); // Uses custom logo
```

### Logo Path Resolution

```php
private function getQrLogoPath(): string
{
    $qrCodeImageUrl = $this->websiteSettings->getQrCodeImageUrl();
    
    if ($qrCodeImageUrl) {
        $storagePath = str_replace('/storage/', '', $qrCodeImageUrl);
        return Storage::path($storagePath);
    }
    
    return public_path('default/qr_logo.png');
}
```

## 🎓 Multiple Course Selection

### Overview

The system supports multiple course enrollment for students while maintaining a single fee structure. Students can enroll in multiple courses simultaneously, but all fees (course fees, down payment, installments) are managed at the student level, not per course.

### Key Features

- **Many-to-Many Relationship**: Students can enroll in multiple courses
- **Single Fee Structure**: All fees managed at student level
- **Course-Specific Details**: Each enrollment stores course-specific information (batch time, instructor, etc.)
- **Backward Compatibility**: Existing code continues to work with accessor methods
- **Automatic Fee Calculation**: Course fees automatically calculated from selected courses

### Database Structure

#### Student-Course Relationship

```php
// Students table (fees stored here)
students: {
    id, center_id, user_id, tiitvt_reg_no, first_name, fathers_name, surname,
    course_fees, down_payment, no_of_installments, installment_date,
    enrollment_date, student_image, student_signature_image, incharge_name
}

// Pivot table (course enrollment details)
student_courses: {
    id, student_id, course_id, enrollment_date, course_taken,
    batch_time, scheme_given, incharge_name
}
```

### Usage Examples

#### Enrolling a Student in Multiple Courses

```php
$student = Student::find(1);

// Set single fee amount for all courses
$student->update([
    'course_fees' => 8000,  // Single fee for all courses
    'down_payment' => 2000,
    'no_of_installments' => 4
]);

// Enroll in multiple courses
$student->courses()->attach([
    1 => [
        'enrollment_date' => now(),
        'course_taken' => 'Full Stack Development',
        'batch_time' => 'Morning',
        'incharge_name' => 'John Doe'
    ],
    2 => [
        'enrollment_date' => now(),
        'course_taken' => 'Data Science',
        'batch_time' => 'Evening',
        'incharge_name' => 'Jane Smith'
    ]
]);
```

#### Getting Student Course Information

```php
$student = Student::with('courses')->find(1);

// Display single fee for all courses
echo "Total Fees: ₹" . $student->course_fees;

foreach ($student->courses as $course) {
    echo $course->name . ' - Enrollment: ' . $course->pivot->enrollment_date;
    echo ' - Batch: ' . $course->pivot->batch_time;
}
```

#### Backward Compatibility

```php
// These still work due to accessor methods
$student->course;        // Returns first enrolled course
$student->course_id;     // Returns ID of first enrolled course
$student->course_fees;   // Returns fees from students table
$student->batch_time;    // Returns batch time from first course
```

### Form Integration

The student creation and edit forms now support:

- **Multi-select Course Dropdown**: Select multiple courses
- **Automatic Fee Calculation**: Fees calculated from selected course prices
- **Course Breakdown Display**: Shows individual course prices and total
- **Real-time Updates**: Form updates as courses are selected/deselected

### Migration Notes

- **Data Preservation**: All existing student-course relationships preserved
- **Seamless Transition**: Existing views continue to work
- **Primary Course**: First enrolled course treated as "primary" for compatibility
- **Fee Management**: All fees remain in students table for unified management

## 📧 Email Notification System

Automated email notifications for various system events:

### Notification Types

1. **Welcome Email**: Sent when new students register
2. **Installment Reminders**: Sent before payment due dates
3. **Payment Confirmations**: Sent when payments are received
4. **Course Completion**: Sent when students complete courses
5. **System Maintenance**: Sent for scheduled maintenance

### Email Templates

All templates are located in `resources/views/mail/notification/`:

- `welcome.blade.php`
- `installment/reminder.blade.php`
- `installment/overdue.blade.php`
- `payment/confirmation.blade.php`
- `course/completion.blade.php`
- `system/maintenance.blade.php`

### Configuration

Email settings are managed through:

- `WebsiteSettingsService` for primary/secondary email addresses
- `MailTemplateHelper` for template management
- `EmailNotificationHelper` for notification logic

## 💰 Installment System

### Features

- **Flexible Payment Terms**: Multiple installment options
- **Automated Reminders**: Email notifications before due dates
- **Status Tracking**: Track payment status for each installment
- **Payment Methods**: Support for various payment methods

### Installment Flow

1. Student enrollment creates initial installment
2. System schedules reminder emails
3. Payments recorded and status updated
4. Next installment activated automatically

## ❓ Question Seeder

Comprehensive question seeding system for exam creation:

### Features

- **Bulk Question Creation**: Generate multiple questions at once
- **Randomization**: Randomized correct answers for fair testing
- **Category Management**: Organize questions by categories
- **Export/Import**: Export questions for backup or sharing

### Usage

```bash
php artisan question:seed --category="Programming" --count=50
```

## ⏰ Cron Jobs Setup

Automated task scheduling:

### Scheduled Tasks

1. **Email Reminders**: Daily installment reminders
2. **Report Generation**: Weekly system reports
3. **Cleanup Tasks**: Monthly cleanup of old files
4. **Backup Operations**: Daily database backups

### Setup

1. Add cron job to server:

   ```bash
   * * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
   ```

2. Configure tasks in `app/Console/Kernel.php`

## 🚀 Setup Instructions

### Prerequisites

- PHP 8.2+
- Laravel 12.0+
- MySQL 8.0+
- Node.js 18+
- Composer

### Installation

1. **Clone Repository**:

   ```bash
   git clone <repository-url>
   cd tiitvt-backend
   ```

2. **Install Dependencies**:

   ```bash
   composer install
   npm install
   ```

3. **Environment Setup**:

   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Database Setup**:

   ```bash
   php artisan migrate
   php artisan db:seed
   ```

5. **Create Symbolic Link**:

   ```bash
   php artisan storage:link
   ```

6. **Configure Web Server**:
   Point document root to `public/` directory

### Role Setup

The system comes with predefined roles:

- `admin`: Full system access
- `center`: Center-level access
- `student`: Student-level access

Seed initial users with roles:

```bash
php artisan db:seed --class=UserWithRoleSeeder
```

## 💡 Usage Examples

### Multiple Course Selection

```php
// Create student with multiple courses
$student = Student::create([
    'first_name' => 'John',
    'fathers_name' => 'Doe',
    'email' => 'john@example.com',
    'course_fees' => 10000,  // Single fee for all courses
    'center_id' => 1
]);

// Enroll in multiple courses
$student->courses()->attach([
    1 => ['enrollment_date' => now(), 'batch_time' => 'Morning'],
    2 => ['enrollment_date' => now(), 'batch_time' => 'Evening']
]);

// Get all courses for a student
$student = Student::with('courses')->find(1);
foreach ($student->courses as $course) {
    echo $course->name . ' - ' . $course->pivot->batch_time;
}
```

### Website Settings Management

```php
// Access website settings in views
{{ $websiteSettings->getWebsiteName() }}
{{ $websiteSettings->getLogoUrl() }}
{{ $websiteSettings->getFaviconUrl() }}
{{ $websiteSettings->getQrCodeImageUrl() }}
```

### QR Code Generation

```php
// Generate student QR code
$qrService = app(StudentQRService::class);
$studentQR = $qrService->generateStudentQR($student);

// Generate certificate QR code
$certService = app(CertificateService::class);
$certificate = $certService->issueCertificate($student, $course);
```

### Email Notifications

```php
// Send notification
app(EmailNotificationService::class)->sendWelcomeEmail($student);
app(EmailNotificationService::class)->sendInstallmentReminder($student, $installment);
```

## ⚙️ Configuration

### Database Configuration

Update `.env` file:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=tiitvt_db
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### Email Configuration

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your_email@gmail.com
MAIL_PASSWORD=your_app_password
MAIL_ENCRYPTION=tls
```

### Storage Configuration

Ensure storage directories are writable:

```bash
chmod -R 775 storage/
chmod -R 775 bootstrap/cache/
```

## 🔧 Command Reference

### Multiple Course Management Commands

```bash
# Check student course enrollments
php artisan tinker
>>> Student::with('courses')->get()->each(function($s) { 
...     echo $s->first_name . ' - Courses: ' . $s->courses->count() . "\n"; 
... });

# Verify course relationships
php artisan tinker
>>> Course::with('students')->get()->each(function($c) { 
...     echo $c->name . ' - Students: ' . $c->students->count() . "\n"; 
... });
```

### Website Settings Commands

```bash
# Clear website settings cache
php artisan cache:forget website_settings

# Seed default website settings
php artisan db:seed --class=WebsiteSettingsSeeder
```

### QR Management Commands

```bash
# Regenerate QR codes for all students
php artisan qr:regenerate --all

# Regenerate QR code for specific student
php artisan qr:regenerate --student=123
```

### System Commands

```bash
# Run migrations
php artisan migrate

# Clear all caches
php artisan cache:clear && php artisan config:clear && php artisan route:clear

# Storage cleanup
php artisan storage:link

# Queue management
php artisan queue:work
php artisan queue:restart
```

## 🧪 Testing

### Manual Testing

1. **Test QR Integration**:

   ```bash
   php test_qr_integration.php
   ```

2. **Test Website Settings**:
   - Upload images in admin panel
   - Verify frontend displays updated logos
   - Check favicon changes across pages

3. **Test QR Generation**:
   - Create student and verify QR code includes website logo
   - Generate certificate and verify QR logo integration

### Automated Testing

```bash
# Run PHPUnit tests
php artisan test

# Run specific test suite
php artisan test --filter=QRServiceTest
```

## 🔍 Troubleshooting

### Common Issues

1. **Images Not Displaying**:

   ```bash
   php artisan storage:link
   chmod -R 755 storage/app/public
   ```

2. **QR Codes Not Generating**:
   - Check website settings QR image uploaded
   - Verify storage permissions
   - Clear cache: `php artisan cache:clear`

3. **Email Notifications Failing**:
   - Verify SMTP configuration in `.env`
   - Check queue worker: `php artisan queue:work`
   - Test email: `php artisan tinker` → test email

4. **Multiple Course Selection Issues**:
   - Verify student_courses table exists: `php artisan migrate:status`
   - Check course relationships: `php artisan tinker` → test relationships
   - Clear model cache: `php artisan model:clear`

5. **Website Settings Not Updating**:
   - Clear cache: `php artisan cache:clear`
   - Restart web server
   - Check file permissions

### Debug Mode

Enable debug mode in `.env`:

```env
APP_DEBUG=true
LOG_LEVEL=debug
```

### File Permissions

Ensure proper permissions:

```bash
chown -R www-data:www-data storage/
chown -R www-data:www-data bootstrap/cache/
chmod -R 755 storage/
chmod -R 755 bootstrap/cache/
```

## 📊 Performance Optimization

### Caching Strategy

- **Website Settings**: Cached for 1 hour
- **Route Caching**: Optimized for production
- **Config Caching**: Cache configuration files
- **View Caching**: Cache compiled views

### Optimization Commands

```bash
# Production optimization
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Clear optimization cache
php artisan optimize:clear
```

## 🔒 Security Considerations

- **Role-based Access Control**: Spatie permissions implemented
- **File Upload Validation**: Strict image validation
- **CSRF Protection**: All forms protected
- **Input Sanitization**: All inputs sanitized
- **SQL Injection Protection**: Eloquent ORM protects against injection

## 📄 License

This project is licensed under the MIT License - see the LICENSE file for details.

## 🤝 Contributing

1. Fork the project
2. Create feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit changes (`git commit -m 'Add AmazingFeature'`)
4. Push to branch (`git push origin feature/AmazingFeature`)
5. Open Pull Request

## 📞 Support

For support, email <support@tiitvt.com> or create an issue in the repository.

---

**Built with ❤️ using Laravel 12, Livewire Volt, and modern PHP practices**
