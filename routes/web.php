<?php

use Livewire\Volt\Volt;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['admin.auth'])->group(function () {
    Route::redirect('/admin', '/dashboard');
    Route::redirect('/admin/dashboard', '/dashboard');

    Route::group(['middleware' => ['role:admin|reseller']], function () {
        Route::name('admin.')->group(function () {
            Volt::route('/dashboard', 'backend.dashboard.index')->name('index');
            Volt::route('/profile', 'backend.profile.index')->name('profile');
        });
    });
});

require __DIR__ . '/auth.php';

// Certificate verification route (public)
Route::get('/certificate/verify/{token}', function ($token) {
    $certificateService = app(\App\Services\CertificateService::class);
    $certificate = $certificateService->verifyCertificate($token);

    if (!$certificate) {
        abort(404, 'Certificate not found or has been revoked.');
    }

    return view('certificates.verify', compact('certificate'));
})->name('certificate.verify');
