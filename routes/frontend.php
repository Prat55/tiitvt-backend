<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\frontend\PageController;
use Livewire\Volt\Volt;

Route::name('frontend.')->group(function () {
    Route::get('/', [PageController::class, 'index'])->name('index');
    Route::get('/about', [PageController::class, 'about'])->name('about');
    Route::get('/contact', [PageController::class, 'contact'])->name('contact');
    Route::post('/contact', [PageController::class, 'contact_submit'])->name('contact_submit');
    Route::get('/blog', [PageController::class, 'blogIndex'])->name('blog.index');
    Route::get('/blog/{slug}', [PageController::class, 'blogShow'])->name('blog.show');

    // Exam routes
    Route::prefix('exam')->name('exam.')->group(function () {
        Volt::route('/login', 'frontend.exam.login')->name('login');
        Volt::route('/dashboard', 'frontend.exam.index')->name('index');
        Volt::route('t/{exam_id}/{slug}', 'frontend.exam.take')->name('take');
        Volt::route('/test', 'frontend.exam.test')->name('test');
    });
});
