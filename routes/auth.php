<?php

use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\PasswordResetController;
use App\Services\SsoService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::middleware('guest')->group(function () {
    Volt::route('/login', 'auth.login')->name('login');
    Volt::route('password/forgot', 'auth.forgot-password')->name('password.request');
    Volt::route('password/reset/{token}', 'auth.reset')->name('password.reset');
});

Route::middleware(['auth'])->group(function () {
    Route::post('/logout', function () {
        Auth::guard('web')->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
        return redirect()->route('login');
    })->name('admin.logout');
});
