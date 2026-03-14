<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Course;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class OptimizeVideosForStreaming extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'videos:optimize-for-streaming {--force : Force optimization even if already optimized}';

    /**
     * The description of the console command.
     *
     * @var string
     */
    protected $description = 'Optimize existing uploaded videos for streaming (faststart, H.264/AAC)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting video optimization for streaming...');

        $ffmpeg = config('app.ffmpeg_path', 'ffmpeg');
        if (!$this->checkFFmpeg($ffmpeg)) {
            $this->error("FFmpeg not found at: {$ffmpeg}. Please check FFMPEG_PATH in your .env file.");
            return 1;
        }

        $processedCount = 0;
        $skippedCount = 0;
        $errorCount = 0;

        // Process Courses
        $this->info('Processing Course lectures...');
        Course::chunk(50, function ($courses) use (&$processedCount, &$skippedCount, &$errorCount, $ffmpeg) {
            foreach ($courses as $course) {
                $lectures = $course->lectures;
                if (empty($lectures) || !is_array($lectures)) {
                    continue;
                }

                $updated = false;
                foreach ($lectures as &$lecture) {
                    if ($this->processLecture($lecture, $ffmpeg)) {
                        $processedCount++;
                        $updated = true;
                    } else {
                        $skippedCount++;
                    }
                }

                if ($updated) {
                    $course->lectures = $lectures;
                    $course->save();
                }
            }
        });

        // Process Categories
        $this->info('Processing Category lectures...');
        Category::chunk(50, function ($categories) use (&$processedCount, &$skippedCount, &$errorCount, $ffmpeg) {
            foreach ($categories as $category) {
                $lectures = $category->lectures;
                if (empty($lectures) || !is_array($lectures)) {
                    continue;
                }

                $updated = false;
                foreach ($lectures as &$lecture) {
                    if ($this->processLecture($lecture, $ffmpeg)) {
                        $processedCount++;
                        $updated = true;
                    } else {
                        $skippedCount++;
                    }
                }

                if ($updated) {
                    $category->lectures = $lectures;
                    $category->save();
                }
            }
        });

        $this->info("Optimization complete!");
        $this->info("Processed: {$processedCount}");
        $this->info("Skipped: {$skippedCount}");
        $this->info("Errors: {$errorCount}");

        return 0;
    }

    /**
     * Process a single lecture video.
     */
    private function processLecture(array &$lecture, string $ffmpeg): bool
    {
        $path = $lecture['path'] ?? null;
        if (!$path) {
            return false;
        }

        $disk = Storage::disk('public');
        if (!$disk->exists($path)) {
            $this->warn("File not found: {$path}");
            return false;
        }

        $fullPath = $disk->path($path);

        // Skip if not an mp4
        if (strtolower(pathinfo($fullPath, PATHINFO_EXTENSION)) !== 'mp4') {
            return false;
        }

        if (!$this->option('force') && $this->isAlreadyOptimized($fullPath)) {
            $this->line("Already optimized: {$path}");
            return false;
        }

        $this->info("Optimizing: {$path}");

        $tempPath = $fullPath . '.optimizing.mp4';

        // Re-encode to H.264/AAC with faststart
        $command = sprintf(
            '%s -y -i %s -movflags +faststart -c:v libx264 -preset veryfast -crf 23 -c:a aac -b:a 128k %s 2>&1',
            escapeshellcmd($ffmpeg),
            escapeshellarg($fullPath),
            escapeshellarg($tempPath)
        );

        exec($command, $output, $exitCode);

        if ($exitCode === 0 && file_exists($tempPath) && filesize($tempPath) > 0) {
            rename($tempPath, $fullPath);
            $this->info("Successfully optimized: {$path}");
            return true;
        } else {
            if (file_exists($tempPath)) {
                @unlink($tempPath);
            }
            $this->error("Failed to optimize: {$path}");
            if (!empty($output)) {
                foreach ($output as $line) {
                    $this->error("  " . $line);
                }
            }
            return false;
        }
    }

    /**
     * Check if FFmpeg is available.
     */
    private function checkFFmpeg(string $ffmpeg): bool
    {
        exec(escapeshellcmd($ffmpeg) . ' -version', $output, $exitCode);
        return $exitCode === 0;
    }

    /**
     * Basic check to see if moov atom is at the beginning.
     */
    private function isAlreadyOptimized(string $fullPath): bool
    {
        $handle = @fopen($fullPath, 'rb');
        if (!$handle) {
            return false;
        }

        try {
            // Read first 12 bytes
            $header = fread($handle, 12);
            if (!$header || strlen($header) < 12) {
                return false;
            }

            // MP4 atoms: [4 bytes size][4 bytes type]
            // We are looking for 'moov' atom early in the file (usually after ftyp)
            $type1 = substr($header, 4, 4);
            if ($type1 === 'moov') {
                return true;
            }

            $size1 = unpack('N', substr($header, 0, 4))[1];
            if (fseek($handle, $size1) !== 0) {
                return false;
            }

            $header2 = fread($handle, 8);
            if (!$header2 || strlen($header2) < 8) {
                return false;
            }

            $type2 = substr($header2, 4, 4);
            return $type2 === 'moov';
        } finally {
            fclose($handle);
        }
    }
}
