<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Installment reminders - daily at 9:00 AM
Schedule::command('installments:send-reminders')
    ->daily()
    ->at('09:00')
    ->appendOutputTo(storage_path('logs/installment-reminders.log'));

// Overdue handling - daily at 10:00 AM
Schedule::command('installments:handle-overdue')
    ->daily()
    ->at('10:00')
    ->appendOutputTo(storage_path('logs/overdue-handling.log'));
