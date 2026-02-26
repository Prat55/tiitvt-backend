<?php

namespace App\Jobs;

use App\Mail\BirthdayWishMail;
use App\Models\Student;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendBirthdayWishesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $today = now();

        $students = Student::query()
            ->whereNotNull('date_of_birth')
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->whereMonth('date_of_birth', $today->month)
            ->whereDay('date_of_birth', $today->day)
            ->get();

        foreach ($students as $student) {
            Mail::to($student->email)->queue(new BirthdayWishMail($student));
        }

        Log::info('Birthday wishes job processed.', [
            'date' => $today->toDateString(),
            'students_count' => $students->count(),
        ]);
    }
}
