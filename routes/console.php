<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Models\WebsiteSetting;

// Payment reminders - daily at 9:00 AM (enrollment-date-based)
Schedule::command('installments:send-reminders')
    ->daily()
    ->at('09:00')
    ->appendOutputTo(storage_path('logs/payment-reminders.log'));

// Outstanding balance reminders - daily at 10:00 AM
Schedule::command('installments:handle-overdue')
    ->daily()
    ->at('10:00')
    ->appendOutputTo(storage_path('logs/outstanding-balance-handling.log'));

// Exam cancellation - every 15 minutes to check for overdue exams
Schedule::command('exams:cancel-overdue')
    ->everyFifteenMinutes()
    ->appendOutputTo(storage_path('logs/exam-cancellation.log'));

// Exam result recalculation - every minute to keep manual mark changes in sync
Schedule::command('exam-results:sync')
    ->everyMinute()
    ->appendOutputTo(storage_path('logs/exam-result-sync.log'));

// Daily database backup at 5:30 AM
Artisan::command('schedule:daily-backup', function () {
    $this->call('backup:daily');
})->purpose('Schedule daily database backup')->dailyAt('05:30');

// Birthday wishes - daily at 8:30 AM
Schedule::command('students:send-birthday-wishes')
    ->dailyAt('08:30')
    ->appendOutputTo(storage_path('logs/birthday-wishes.log'));

// Cleanup orphaned uploads - hourly
Schedule::command('uploads:cleanup')
    ->hourly()
    ->appendOutputTo(storage_path('logs/uploads-cleanup.log'));
