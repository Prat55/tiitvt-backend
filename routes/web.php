<?php

use Livewire\Volt\Volt;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Volt::route('/', 'admin.index')->name('admin.index');

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
