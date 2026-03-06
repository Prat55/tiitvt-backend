<?php

use App\Http\Controllers\Api\AccessControlController;
use App\Http\Controllers\Api\AuthApiController;
use App\Http\Controllers\Api\DocumentApiController;
use App\Http\Controllers\Api\StudentApiController;
use App\Http\Controllers\VideoStreamingController;
use Illuminate\Support\Facades\Route;

Route::post('/access-control/trigger', [AccessControlController::class, 'trigger'])
    ->middleware('throttle:api-auth')
    ->name('api.access-control.trigger');

Route::prefix('auth')->name('api.auth.')->group(function () {
    Route::post('/login', [AuthApiController::class, 'login'])
        ->middleware('throttle:api-auth')
        ->name('login');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthApiController::class, 'logout'])->name('logout');
    });
});

Route::prefix('student')->name('api.student.')->group(function () {
    Route::post('/login', [AuthApiController::class, 'studentLogin'])
        ->middleware('throttle:api-auth')
        ->name('login');

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

Route::get('/videos/stream/{path}', [VideoStreamingController::class, 'stream'])
    ->name('api.videos.stream')
    ->where('path', '.*');

Route::prefix('uploads')->group(function () {
    Route::post('/init', [\App\Http\Controllers\ChunkedUploadController::class, 'init']);
    Route::post('/{uploadId}/chunk', [\App\Http\Controllers\ChunkedUploadController::class, 'uploadChunk']);
    Route::post('/{uploadId}/complete', [\App\Http\Controllers\ChunkedUploadController::class, 'complete']);
});
