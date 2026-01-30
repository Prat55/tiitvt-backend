<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::middleware('guest')->group(function () {
    Volt::route('/login', 'auth.login')->name('login');
    Volt::route('password/forgot', 'auth.forgot-password')->name('password.request');
    Volt::route('password/reset/{token}', 'auth.reset')->name('password.reset');
});

Route::middleware(['auth'])->group(function () {
    Volt::route('v1/2fa-verify', 'auth.verify-2fa-email')->name('auth.verify-2fa-email');
    Volt::route('v2/2fa-verify', 'auth.verify-2fa-authenticator')->name('auth.verify-2fa-authenticator');

    Route::post('/logout', function () {
        Auth::guard('web')->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
        return redirect()->route('login');
    })->name('admin.logout');
});
