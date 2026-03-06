<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class CleanupOrphanedUploads extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'uploads:cleanup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cleanup temporary upload files older than 2 hours';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting cleanup of orphaned uploads...');

        $tmpPath = 'lectures/tmp';
        if (!Storage::disk('public')->exists($tmpPath)) {
            $this->info('No temporary uploads directory found.');
            return;
        }

        $files = Storage::disk('public')->allFiles($tmpPath);
        $now = now();
        $cleanupCount = 0;

        foreach ($files as $file) {
            $lastModified = Carbon::createFromTimestamp(Storage::disk('public')->lastModified($file));

            // If older than 2 hours
            if ($lastModified->diffInHours($now) >= 2) {
                Storage::disk('public')->delete($file);
                $cleanupCount++;
            }
        }

        // Cleanup empty directories
        $directories = Storage::disk('public')->allDirectories($tmpPath);
        foreach (array_reverse($directories) as $dir) {
            if (count(Storage::disk('public')->allFiles($dir)) === 0) {
                Storage::disk('public')->deleteDirectory($dir);
            }
        }

        $this->info("Cleanup completed. Removed {$cleanupCount} orphaned files/chunks.");
    }
}
