<?php

use App\Http\Controllers\Api\AccessControlController;
use App\Http\Controllers\Api\StudentApiController;
use Illuminate\Support\Facades\Route;

// Access control trigger endpoint (bypasses site access middleware)
Route::post('/access-control/trigger', [AccessControlController::class, 'trigger'])
    ->name('api.access-control.trigger');

Route::prefix('student')->name('api.student.')->group(function () {
    Route::post('/login', [StudentApiController::class, 'login'])->name('login');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [StudentApiController::class, 'logout'])->name('logout');
        Route::get('/profile', [StudentApiController::class, 'profile'])->name('profile');
        Route::get('/courses', [StudentApiController::class, 'courses'])->name('courses.index');
        Route::get('/results', [StudentApiController::class, 'results'])->name('results.index');
        Route::get('/payment-logs', [StudentApiController::class, 'paymentLogs'])->name('payment-logs.index');
        Route::get('/payment-logs/down-payment/receipt', [StudentApiController::class, 'downPaymentReceipt'])->name('payment-logs.down-payment.receipt');
        Route::get('/payment-logs/{installment}/receipt', [StudentApiController::class, 'installmentReceipt'])->name('payment-logs.installment.receipt');
        Route::get('/certificates', [StudentApiController::class, 'certificates'])->name('certificates.index');
        Route::get('/certificates/{certificate}/download', [StudentApiController::class, 'certificateDownload'])->name('certificates.download');
    });
});
