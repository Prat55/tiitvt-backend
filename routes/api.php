<?php

use App\Http\Controllers\Api\AccessControlController;
use App\Http\Controllers\Api\AuthApiController;
use App\Http\Controllers\Api\DocumentApiController;
use App\Http\Controllers\Api\StudentApiController;
use Illuminate\Support\Facades\Route;

// Access control trigger endpoint (bypasses site access middleware)
Route::post('/access-control/trigger', [AccessControlController::class, 'trigger'])
    ->name('api.access-control.trigger');

Route::prefix('auth')->name('api.auth.')->group(function () {
    Route::post('/login', [AuthApiController::class, 'login'])->name('login');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthApiController::class, 'logout'])->name('logout');
    });
});

Route::prefix('student')->name('api.student.')->group(function () {
    // Backward compatible student-only login
    Route::post('/login', [AuthApiController::class, 'studentLogin'])->name('login');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthApiController::class, 'logout'])->name('logout');
        Route::get('/profile', [StudentApiController::class, 'profile'])->name('profile');
        Route::get('/courses', [StudentApiController::class, 'courses'])->name('courses.index');
        Route::get('/results', [StudentApiController::class, 'results'])->name('results.index');
        Route::get('/payment-logs', [StudentApiController::class, 'paymentLogs'])->name('payment-logs.index');
        Route::get('/certificates', [StudentApiController::class, 'certificates'])->name('certificates.index');
    });
});

Route::middleware('auth:sanctum')->prefix('documents')->name('api.documents.')->group(function () {
    Route::get('/receipts/installments/{installment}', [DocumentApiController::class, 'installmentReceipt'])
        ->name('receipts.installment');
    Route::get('/receipts/students/{student}/down-payment', [DocumentApiController::class, 'downPaymentReceipt'])
        ->name('receipts.down-payment');
    Route::get('/certificates/{certificate}/download', [DocumentApiController::class, 'certificateDownload'])
        ->name('certificates.download');
    Route::get('/certificates/auto/{course}/download', [DocumentApiController::class, 'autoCertificateDownload'])
        ->name('certificates.auto-download');
});
