<?php

namespace App\Console\Commands;

use App\Services\StudentQRService;
use Illuminate\Console\Command;

class RegenerateQRCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'qr:regenerate
                            {--all : Regenerate QR codes for all students}
                            {--student= : Regenerate QR code for specific student ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Regenerate QR codes with fresh logo from website settings';

    protected StudentQRService $qrService;

    public function __construct(StudentQRService $qrService)
    {
        parent::__construct();
        $this->qrService = $qrService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ($this->option('all')) {
            return $this->regenerateAllQRs();
        }

        if ($studentId = $this->option('student')) {
            return $this->regenerateStudentQR($studentId);
        }

        $this->error('Please specify either --all or --student=ID option.');
        return 1;
    }

    protected function regenerateAllQRs(): int
    {
        $this->info('Regenerating QR codes for all students with fresh logo...');

        $results = $this->qrService->regenerateAllStudentQRsWithFreshLogo();
        $successCount = 0;
        $errorCount = 0;

        foreach ($results as $studentId => $result) {
            if (isset($result['error'])) {
                $this->error("Student {$studentId}: {$result['error']}");
                $errorCount++;
            } else {
                $this->line("Student {$studentId}: ✓ Regenerated successfully");
                $successCount++;
            }
        }

        $this->info("Completed: {$successCount} success, {$errorCount} errors");
        return $errorCount > 0 ? 1 : 0;
    }

    protected function regenerateStudentQR(string $studentId): int
    {
        try {
            $student = \App\Models\Student::findOrFail($studentId);

            if (!$student->qrCode) {
                $this->error("Student {$studentId} does not have a QR code. Generating new one...");
                $this->qrService->generateStudentQR($student);
            } else {
                $this->qrService->regenerateStudentQRWithFreshLogo($student);
            }

            $this->info("✓ QR code regenerated successfully for student {$studentId}");
            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to regenerate QR code for student {$studentId}: {$e->getMessage()}");
            return 1;
        }
    }
}
