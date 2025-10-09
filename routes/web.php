<?php

use Livewire\Volt\Volt;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Dynamic favicon route
Route::get('/favicon.ico', [App\Http\Controllers\FaviconController::class, 'favicon'])->name('favicon');

Route::middleware(['admin.auth'])->group(function () {
    Route::redirect('/admin', '/admin/dashboard');
    Route::redirect('/admin', '/dashboard');

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::group(['middleware' => ['role:admin|center']], function () {
            Volt::route('/dashboard', 'backend.dashboard.index')->name('index');
            Volt::route('/profile', 'backend.profile.index')->name('profile');
        });

        Route::group(['middleware' => ['role:admin']], function () {
            Route::prefix('certificate')->name('certificate.')->group(function () {
                Volt::route('/', 'backend.certificate.index')->name('index');
                Volt::route('/create', 'backend.certificate.create')->name('create');
                Volt::route('/{certificate}/show', 'backend.certificate.show')->name('show');
                Volt::route('/{certificate}/edit', 'backend.certificate.edit')->name('edit');
            });

            Route::prefix('center')->name('center.')->group(function () {
                Volt::route('/', 'backend.center.index')->name('index');
                Volt::route('/create', 'backend.center.create')->name('create');
                Volt::route('/{uid}/edit', 'backend.center.edit')->name('edit');
                Volt::route('/{uid}/show', 'backend.center.show')->name('show');
            });

            Route::prefix('category')->name('category.')->group(function () {
                Volt::route('/', 'backend.category.index')->name('index');
            });

            Route::prefix('course')->name('course.')->group(function () {
                Volt::route('/', 'backend.course.index')->name('index');
                Volt::route('/create', 'backend.course.create')->name('create');
                Volt::route('/{course}/show', 'backend.course.show')->name('show');
                Volt::route('/{course}/edit', 'backend.course.edit')->name('edit');
            });

            Route::prefix('question')->name('question.')->group(function () {
                Volt::route('/', 'backend.question.index')->name('index');
                Volt::route('/create', 'backend.question.create')->name('create');
                Volt::route('/{question}/show', 'backend.question.show')->name('show');
                Volt::route('/{question}/edit', 'backend.question.edit')->name('edit');
            });

            Route::prefix('blog')->name('blog.')->group(function () {
                Volt::route('/', 'backend.blog.index')->name('index');
                Volt::route('/{blog}/edit', 'backend.blog.edit')->name('edit');
            });

            Route::prefix('website-setting')->name('website-setting.')->group(function () {
                Volt::route('/', 'backend.website-setting.index')->name('index');
            });

            Route::prefix('testimonial')->name('testimonial.')->group(function () {
                Volt::route('/', 'backend.testimonial.index')->name('index');
            });
        });

        Route::group(['middleware' => ['role:admin|center']], function () {
            Route::prefix('student')->name('student.')->group(function () {
                Volt::route('/', 'backend.student.index')->name('index');
                Volt::route('/create', 'backend.student.create')->name('create');
                Volt::route('/{student}/show', 'backend.student.show')->name('show');
                Volt::route('/{student}/edit', 'backend.student.edit')->name('edit');
                Volt::route('/{student}/delete', 'backend.student.delete')->name('delete');
            });

            Route::prefix('exam')->name('exam.')->group(function () {
                Volt::route('/', 'backend.exam.index')->name('index');
                Volt::route('/create', 'backend.exam.create')->name('create');
                Volt::route('/{exam}/show', 'backend.exam.show')->name('show');
                Volt::route('/{exam}/edit', 'backend.exam.edit')->name('edit');
                Volt::route('/schedule', 'backend.exam.schedule')->name('schedule');
                Volt::route('/results', 'backend.exam.result.index')->name('results');
                Volt::route('/result/{examId}/{studentRegNo}', 'backend.exam.result.show')->name('result.show');
            });
        });
    });
});

require __DIR__ . '/auth.php';
require __DIR__ . '/frontend.php';

// Certificate verification route (public)
Route::get('/certificate/verify/{token}', function ($token) {
    $certificateService = app(\App\Services\CertificateService::class);
    $certificate = $certificateService->verifyCertificate($token);

    if (!$certificate) {
        abort(404, 'Certificate not found or has been revoked.');
    }

    return view('certificates.verify', compact('certificate'));
})->name('certificate.verify');

// External certificate show (PDF-like demo rendering)
Route::get('/certificate/external/show/{id}', function ($id) {
    $ext = \App\Models\ExternalCertificate::findOrFail($id);

    // Map external certificate to expected variables in the blade
    $certificate = (object) [
        'tiitvt_reg_no' => $ext->reg_no,
        'center_name' => $ext->center->name,
        'issued_on' => $ext->issued_on ? \Illuminate\Support\Carbon::parse($ext->issued_on) : now(),
        'qr_token' => $ext->qr_token,
    ];

    $student = (object) [
        'full_name' => $ext->student_name,
        'percentage' => $ext->percentage,
        'grade' => $ext->grade,
        'course' => (object) ['name' => $ext->course_name],
        'examResult' => (object) ['data' => [
            'subjects' => $ext->data['subjects'] ?? [],
            'total_marks' => $ext->data['total_marks'] ?? null,
            'total_marks_obtained' => $ext->data['total_marks_obtained'] ?? null,
            'total_result' => $ext->data['total_result'] ?? null,
        ]],
    ];

    $qrUrl = $ext->qr_code_path ? asset('storage/' . ltrim($ext->qr_code_path, '/')) : null;
    $qrDataUri = null;
    if ($ext->qr_code_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($ext->qr_code_path)) {
        $contents = \Illuminate\Support\Facades\Storage::disk('public')->get($ext->qr_code_path);
        $qrDataUri = 'data:image/png;base64,' . base64_encode($contents);
    }

    // Fallback: generate on the fly if missing
    if (!$qrDataUri) {
        try {
            $verificationUrl = route('certificate.external.show', $ext->id);
            $websiteSettings = app(\App\Services\WebsiteSettingsService::class);
            $logoPath = null;
            if (($s = $websiteSettings->getSettings()) && $s->qr_code_image) {
                $logoPath = \Illuminate\Support\Facades\Storage::disk('public')->path($s->qr_code_image);
            }

            $builder = Endroid\QrCode\Builder\Builder::create()
                ->writer(new Endroid\QrCode\Writer\PngWriter())
                ->writerOptions([])
                ->data($verificationUrl)
                ->encoding(new Endroid\QrCode\Encoding\Encoding('UTF-8'))
                ->errorCorrectionLevel(Endroid\QrCode\ErrorCorrectionLevel::High)
                ->size(300)
                ->margin(10)
                ->roundBlockSizeMode(Endroid\QrCode\RoundBlockSizeMode::Margin);

            if ($logoPath && file_exists($logoPath)) {
                $builder->logoPath($logoPath)->logoResizeToWidth(60)->logoPunchoutBackground(true);
            }

            $result = $builder->build();
            $qrDataUri = $result->getDataUri();
        } catch (\Throwable $e) {
            // ignore
        }
    }

    return view('certificates.tiiitvt-merit-external', compact('certificate', 'student', 'qrUrl', 'qrDataUri'));
})->middleware(['web'])->name('certificate.external.show');

// Student QR verification route (public) - by QR token using Volt
Volt::route('/student/qr/{token}', 'frontend.student.qr-verification')->name('student.qr.verify');

// Modern certificate display route (public)
Route::get('/certificate/{id}', function ($id) {
    $certificate = \App\Models\ExternalCertificate::with('center')->findOrFail($id);

    // Generate QR code data URI if not exists
    $qrDataUri = null;
    if ($certificate->qr_code_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($certificate->qr_code_path)) {
        $contents = \Illuminate\Support\Facades\Storage::disk('public')->get($certificate->qr_code_path);
        $qrDataUri = 'data:image/png;base64,' . base64_encode($contents);
    }

    return view('certificates.modern-display', compact('certificate', 'qrDataUri'));
})->name('certificate.display');
