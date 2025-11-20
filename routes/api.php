<?php

use App\Http\Controllers\Api\AccessControlController;
use Illuminate\Support\Facades\Route;

// Access control trigger endpoint (bypasses site access middleware)
Route::post('/access-control/trigger', [AccessControlController::class, 'trigger'])
    ->name('api.access-control.trigger');
