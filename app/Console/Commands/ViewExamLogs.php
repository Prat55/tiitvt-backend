<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ViewExamLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'exam:logs {--lines=50 : Number of lines to show} {--follow : Follow log file in real-time}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'View exam system logs';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $logPath = storage_path('logs/exam.log');

        if (!File::exists($logPath)) {
            $this->error('Exam log file not found at: ' . $logPath);
            return 1;
        }

        $lines = $this->option('lines');
        $follow = $this->option('follow');

        if ($follow) {
            $this->info('Following exam logs (Press Ctrl+C to stop)...');
            $this->line('');
            passthru("tail -f -n {$lines} " . escapeshellarg($logPath));
        } else {
            $this->info("Last {$lines} lines of exam logs:");
            $this->line('');
            passthru("tail -n {$lines} " . escapeshellarg($logPath));
        }

        return 0;
    }
}
