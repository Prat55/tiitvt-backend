<?php

use Livewire\Volt\Volt;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['admin.auth'])->group(function () {
    Route::redirect('/admin', '/dashboard');

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::group(['middleware' => ['role:admin|center']], function () {
            Volt::route('/dashboard', 'backend.dashboard.index')->name('index');
            Volt::route('/profile', 'backend.profile.index')->name('profile');
        });

        Route::group(['middleware' => ['role:admin']], function () {
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

            Route::prefix('exam')->name('exam.')->group(function () {
                Volt::route('/', 'backend.exam.index')->name('index');
                Volt::route('/create', 'backend.exam.create')->name('create');
                Volt::route('/{exam}/show', 'backend.exam.show')->name('show');
                Volt::route('/{exam}/edit', 'backend.exam.edit')->name('edit');
                Volt::route('/schedule', 'backend.exam.schedule')->name('schedule');
                Volt::route('/results', 'backend.exam.results')->name('results');
            });

            Route::prefix('question')->name('question.')->group(function () {
                Volt::route('/', 'backend.question.index')->name('index');
                Volt::route('/create', 'backend.question.create')->name('create');
                Volt::route('/{question}/show', 'backend.question.show')->name('show');
                Volt::route('/{question}/edit', 'backend.question.edit')->name('edit');
            });

            Route::prefix('website-setting')->name('website-setting.')->group(function () {
                Volt::route('/', 'backend.website-setting.index')->name('index');
            });

            Route::prefix('testimonial')->name('testimonial.')->group(function () {
                Volt::route('/', 'backend.testimonial.index')->name('index');
            });

            Route::prefix('blog')->name('blog.')->group(function () {
                Volt::route('/', 'backend.blog.index')->name('index');
                Volt::route('/{blog}/edit', 'backend.blog.edit')->name('edit');
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
