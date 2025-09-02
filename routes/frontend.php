<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\frontend\PageController;

Route::name('frontend.')->group(function () {
    Route::get('/', [PageController::class, 'index'])->name('index');
    Route::get('/about', [PageController::class, 'about'])->name('about');
    Route::get('/contact', [PageController::class, 'contact'])->name('contact');
    Route::post('/contact', [PageController::class, 'contact_submit'])->name('contact_submit');
    Route::get('/blog', [PageController::class, 'blogIndex'])->name('blog.index');
    Route::get('/blog/{slug}', [PageController::class, 'blogShow'])->name('blog.show');
});
