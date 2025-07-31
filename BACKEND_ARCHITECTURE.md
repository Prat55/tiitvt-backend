# IT Classes Management System - Backend Architecture

## 📋 Overview

This document outlines the backend architecture for the SaaS-based IT classes management system built with Laravel + Livewire Volt and Spatie Roles & Permissions.

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

#### 7. Exam

- **Fields**: course_id, title, duration, is_active
- **Relationships**:
  - `belongsTo` course
  - `hasMany` questions
  - `hasMany` examResults

#### 8. Question

- **Fields**: exam_id, question_text, options (JSON), correct_option, points
- **Relationships**:
  - `belongsTo` exam

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

## 🗄️ Database Structure

### Migrations Created

1. `2025_01_01_000001_create_categories_table.php`
2. `2025_01_01_000002_create_subcategories_table.php`
3. `2025_01_01_000003_create_courses_table.php`
4. `2025_01_01_000004_create_centers_table.php`
5. `2025_01_01_000005_create_students_table.php`
6. `2025_01_01_000006_create_exams_table.php`
7. `2025_01_01_000007_create_questions_table.php`
8. `2025_01_01_000008_create_exam_results_table.php`
9. `2025_01_01_000009_create_invoices_table.php`
10. `2025_01_01_000010_create_certificates_table.php`

### Key Features

- **Foreign Key Constraints**: All relationships properly constrained with cascade deletes
- **JSON Fields**: Options for questions, answers for exam results
- **Unique Constraints**: Invoice numbers, certificate numbers, QR tokens
- **Status Enums**: Active/inactive statuses for most entities
- **Timestamps**: Created_at and updated_at on all models

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
│   └── Certificate.php
├── Services/
│   ├── ExamService.php
│   ├── CertificateService.php
│   └── InvoiceService.php
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
│   └── 2025_01_01_000010_create_certificates_table.php
└── seeders/
    ├── DatabaseSeeder.php
    ├── UserWithRoleSeeder.php
    └── SystemDataSeeder.php

routes/
├── web.php (with role-protected groups)
└── auth.php
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
5. **Email Notifications**: Set up email notifications for various events
6. **API Routes**: Create API endpoints if needed
7. **Testing**: Write unit and feature tests

## 🔧 Configuration Notes

- **Passing Score**: Currently set to 70% in ExamService
- **QR Code Size**: 300px with 10px margin
- **Invoice Number Format**: INV-YYYYMM-XXXXXX
- **Certificate Number Format**: CERT-YYYY-XXXXXX
- **File Storage**: Uses Laravel's public disk for certificates and QR codes

This architecture provides a solid foundation for the IT classes management system with proper separation of concerns, role-based access control, and extensible service layer.
