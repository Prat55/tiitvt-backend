<?php

namespace App\Console\Commands;

use App\Services\ExamResultSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncExamResults extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'exam-results:sync
                            {--student-id= : Sync only one student}
                            {--course-id= : Sync only one course}
                            {--result-id= : Sync only one exam result row}
                            {--dry-run : Show what would change without writing to the database}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalculate exam result rows after mark changes and keep stored pass/fail values in sync.';

    public function __construct(private readonly ExamResultSyncService $examResultSyncService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $studentId = $this->option('student-id');
        $courseId = $this->option('course-id');
        $resultId = $this->option('result-id');
        $dryRun = (bool) $this->option('dry-run');

        $this->info($dryRun ? 'Running exam result sync in dry-run mode...' : 'Running exam result sync...');

        $summary = $this->examResultSyncService->syncResults(
            $studentId !== null ? (int) $studentId : null,
            $courseId !== null ? (int) $courseId : null,
            $resultId !== null ? (int) $resultId : null,
            $dryRun,
        );

        foreach ($summary['changes'] as $change) {
            if (isset($change['error'])) {
                $this->error(sprintf(
                    'Result #%d failed: %s',
                    $change['result_id'],
                    $change['error'],
                ));
                continue;
            }

            $formattedChanges = collect($change['changes'])
                ->map(function (array $fieldChange, string $field) {
                    return sprintf('%s: %s -> %s', $field, var_export($fieldChange['from'], true), var_export($fieldChange['to'], true));
                })
                ->implode(', ');

            $this->line(sprintf(
                'Result #%d (student %d, exam %d, category %s): %s',
                $change['result_id'],
                $change['student_id'],
                $change['exam_id'],
                $change['category_id'] ?? 'n/a',
                $formattedChanges,
            ));
        }

        $this->newLine();
        $this->info(sprintf('Processed: %d', $summary['processed']));
        $this->info(sprintf('Updated: %d', $summary['updated']));
        $this->info(sprintf('Failed: %d', $summary['failed']));

        Log::info('Exam result sync completed', [
            'processed' => $summary['processed'],
            'updated' => $summary['updated'],
            'failed' => $summary['failed'],
            'dry_run' => $dryRun,
            'student_id' => $studentId,
            'course_id' => $courseId,
            'result_id' => $resultId,
        ]);

        return $summary['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
