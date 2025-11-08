<?php

namespace App\Console\Commands;

use App\Models\Exam;
use App\Enums\ExamStatusEnum;
use Illuminate\Console\Command;
use App\Enums\ExamResultStatusEnum;
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
    protected $description = 'Cancel exams that were scheduled but never completed and are past their end time. Also re-evaluate cancelled exams to check for partial completion.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting overdue exam cancellation process...');

        try {
            // Get all scheduled exams that are past their end time
            $overdueExams = Exam::overdue()->get();

            // Get all cancelled exams that are past their end time (to re-evaluate for partial completion)
            $cancelledExams = Exam::where('status', ExamStatusEnum::CANCELLED)
                ->where('date', '<=', now()->toDateString())
                ->where('end_time', '<', now()->format('H:i:s'))
                ->get();

            $allExamsToProcess = $overdueExams->merge($cancelledExams);

            if ($allExamsToProcess->isEmpty()) {
                $this->info('No overdue or cancelled exams found to process.');
                return 0;
            }

            $this->info("Found {$overdueExams->count()} overdue exam(s) and {$cancelledExams->count()} cancelled exam(s) to process.");

            $updatedCount = 0;
            $failedCount = 0;

            foreach ($allExamsToProcess as $exam) {
                try {
                    // Get all enrolled students
                    $enrolledStudents = $exam->examStudents;
                    $totalStudents = $enrolledStudents->count();

                    // Count students who have completed (have results with result_status != NotDeclared)
                    $completedStudents = $exam->examResults()
                        ->whereNotIn('result_status', [ExamResultStatusEnum::NotDeclared->value])
                        ->distinct('student_id')
                        ->count('student_id');

                    // Count students who have failed (result = 'failed')
                    $failedStudents = $exam->examResults()
                        ->where('result', 'failed')
                        ->distinct('student_id')
                        ->count('student_id');

                    // Determine status based on completion
                    $oldStatus = $exam->status;
                    $newStatus = ExamStatusEnum::CANCELLED;
                    $statusReason = 'Automatically cancelled due to being overdue - no students completed';

                    if ($completedStudents > 0 && $completedStudents < $totalStudents) {
                        // Some students completed but not all
                        $newStatus = ExamStatusEnum::PARTIAL_COMPLETED;
                        $statusReason = "Automatically marked as partial completed - {$completedStudents} out of {$totalStudents} students completed";
                    } elseif ($completedStudents === $totalStudents && $totalStudents > 0) {
                        // All students completed
                        $newStatus = ExamStatusEnum::COMPLETED;
                        $statusReason = "All students completed the exam";
                    }

                    // Only update if status has changed
                    if ($oldStatus !== $newStatus) {
                        $exam->update([
                            'status' => $newStatus
                        ]);
                        $updatedCount++;
                    } else {
                        // Status unchanged, skip logging
                        continue;
                    }

                    $studentNames = $enrolledStudents->map(function ($examStudent) {
                        return $examStudent->student->full_name;
                    })->implode(', ');

                    $statusLabel = $newStatus->label();
                    $this->line("✓ Updated exam: {$exam->exam_id} to status: {$statusLabel}");
                    $this->line("  - Total students: {$totalStudents}");
                    $this->line("  - Completed: {$completedStudents}");
                    $this->line("  - Failed: {$failedStudents}");
                    $this->line("  - Students: {$studentNames}");

                    // Log the status update
                    Log::info("Exam status updated", [
                        'exam_id' => $exam->exam_id,
                        'old_status' => $oldStatus->value,
                        'new_status' => $newStatus->value,
                        'total_students' => $totalStudents,
                        'completed_students' => $completedStudents,
                        'failed_students' => $failedStudents,
                        'course_id' => $exam->course_id,
                        'scheduled_date' => $exam->date,
                        'scheduled_end_time' => $exam->end_time,
                        'updated_at' => now(),
                        'reason' => $statusReason
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
            $this->info("  ✓ Successfully updated: {$updatedCount}");
            $this->info("  ✗ Failed to update: {$failedCount}");

            if ($updatedCount > 0) {
                Log::info("Exam status update completed", [
                    'total_processed' => $allExamsToProcess->count(),
                    'overdue_exams' => $overdueExams->count(),
                    'cancelled_exams' => $cancelledExams->count(),
                    'successfully_updated' => $updatedCount,
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
