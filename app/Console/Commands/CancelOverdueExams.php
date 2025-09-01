<?php

namespace App\Console\Commands;

use App\Models\Exam;
use App\Enums\ExamStatusEnum;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CancelOverdueExams extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'exams:cancel-overdue';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cancel exams that were scheduled but never completed and are past their end time';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting overdue exam cancellation process...');

        try {
            // Get all scheduled exams that are past their end time
            $overdueExams = Exam::overdue()->get();

            if ($overdueExams->isEmpty()) {
                $this->info('No overdue exams found.');
                return 0;
            }

            $this->info("Found {$overdueExams->count()} overdue exam(s) to cancel.");

            $cancelledCount = 0;
            $failedCount = 0;

            foreach ($overdueExams as $exam) {
                try {
                    $exam->update([
                        'status' => ExamStatusEnum::CANCELLED
                    ]);

                    $cancelledCount++;

                    $studentNames = $exam->examStudents->map(function ($examStudent) {
                        return $examStudent->student->full_name;
                    })->implode(', ');

                    $this->line("✓ Cancelled exam: {$exam->exam_id} for students: {$studentNames}");

                    // Log the cancellation
                    Log::info("Exam automatically cancelled", [
                        'exam_id' => $exam->exam_id,
                        'student_count' => $exam->examStudents->count(),
                        'course_id' => $exam->course_id,
                        'scheduled_date' => $exam->date,
                        'scheduled_end_time' => $exam->end_time,
                        'cancelled_at' => now(),
                        'reason' => 'Automatically cancelled due to being overdue'
                    ]);
                } catch (\Exception $e) {
                    $failedCount++;
                    $this->error("✗ Failed to cancel exam {$exam->exam_id}: " . $e->getMessage());

                    Log::error("Failed to cancel overdue exam", [
                        'exam_id' => $exam->exam_id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            $this->newLine();
            $this->info("Process completed:");
            $this->info("  ✓ Successfully cancelled: {$cancelledCount}");
            $this->info("  ✗ Failed to cancel: {$failedCount}");

            if ($cancelledCount > 0) {
                Log::info("Overdue exam cancellation completed", [
                    'total_processed' => $overdueExams->count(),
                    'successfully_cancelled' => $cancelledCount,
                    'failed' => $failedCount
                ]);
            }

            return 0;
        } catch (\Exception $e) {
            $this->error("An error occurred during the cancellation process: " . $e->getMessage());
            Log::error("Error in overdue exam cancellation command", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }
}
