<?php

use App\Http\Controllers\CertificateController;
use App\Http\Controllers\StudentController;
use Illuminate\Support\Facades\DB;
use Livewire\Volt\Volt;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Dynamic favicon route
Route::get('/favicon.ico', [App\Http\Controllers\FaviconController::class, 'favicon'])->name('favicon');

Route::middleware(['admin.auth'])->group(function () {
    Route::redirect('/admin', '/admin/dashboard');

    Route::prefix('app')->name('admin.')->group(function () {
        Route::group(['middleware' => ['role:admin|center']], function () {
            Volt::route('/dashboard', 'backend.dashboard.index')->name('index');
            Volt::route('/profile', 'backend.profile.index')->name('profile');
        });

        Route::group(['middleware' => ['role:admin']], function () {
            Route::prefix('certificate')->name('certificate.')->group(function () {
                Volt::route('/', 'backend.certificate.index')->name('index');
                Volt::route('/create', 'backend.certificate.create')->name('create');
                Volt::route('/{certificate}/edit', 'backend.certificate.edit')->name('edit');
                Route::get('/{id}/show', [CertificateController::class, 'display'])->name('show');
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

            // Backup routes (admin only)
            Route::prefix('app')->group(function () {
                Volt::route('database-backup', 'backend.backup.index')->name('backup.index');
                Route::get('/download/{id}', function ($id) {
                    $backup = DB::table('database_backups')->find($id);

                    if (!$backup || !file_exists($backup->path)) {
                        abort(404, 'Backup not found');
                    }

                    return response()->download($backup->path, $backup->filename);
                })->name('backup.download');
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

    // Certificate preview route (authenticated users only - admin or center)
    Route::group(['middleware' => ['role:admin|center']], function () {
        Route::prefix('/app/certificate')->name('certificate.')->group(function () {
            Route::get('/exam/preview/{regNo}', [CertificateController::class, 'preview'])->name('exam.preview');
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

    // Track page visit
    trackPageVisit('certificate_verify', [
        'token' => $token,
        'certificate_id' => $certificate->id,
        'student_id' => $certificate->student_id ?? null,
    ]);

    // Always show certificate without verification
    return view('certificates.verify', compact('certificate'));
})->name('certificate.verify');

// Student QR verification route (public) - by QR token using Volt
Volt::route('/student/qr/{token}', 'frontend.student.qr-verification')->name('student.qr.verify');

Route::get('/student/result/{regNo}', [StudentController::class, 'resultView'])->name('student.result.view');

// Payment receipt route (authenticated users only - admin or center)
Route::group(['middleware' => ['role:admin|center']], function () {
    Route::get('/app/receipt/{type}/{id}', [StudentController::class, 'receipt'])->name('receipt.payment');
});
