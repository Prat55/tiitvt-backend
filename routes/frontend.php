<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\frontend\PageController;

Route::name('frontend.')->group(function () {
    Route::get('/', [PageController::class, 'index'])->name('index');
});
