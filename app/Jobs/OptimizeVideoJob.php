<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class OptimizeVideoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $path;
    public $force;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 3600; // 1 hour for large videos

    /**
     * Create a new job instance.
     */
    public function __construct(string $path, bool $force = false)
    {
        $this->path = $path;
        $this->force = $force;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $ffmpeg = config('app.ffmpeg_path', 'ffmpeg');
        $disk = Storage::disk('public');

        if (!$disk->exists($this->path)) {
            Log::warning("OptimizeVideoJob: File not found: {$this->path}");
            return;
        }

        $fullPath = $disk->path($this->path);

        // Skip if not an mp4
        if (strtolower(pathinfo($fullPath, PATHINFO_EXTENSION)) !== 'mp4') {
            return;
        }

        if (!$this->force && $this->isAlreadyOptimized($fullPath)) {
            Log::info("OptimizeVideoJob: Already optimized: {$this->path}");
            return;
        }

        Log::info("OptimizeVideoJob: Starting optimization for: {$this->path}");

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
            Log::info("OptimizeVideoJob: Successfully optimized: {$this->path}");
        } else {
            if (file_exists($tempPath)) {
                @unlink($tempPath);
            }
            Log::error("OptimizeVideoJob: Failed to optimize: {$this->path}");
            if (!empty($output)) {
                Log::error("FFmpeg Output for {$this->path}:\n" . implode("\n", $output));
            }
        }
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
        } catch (\Exception $e) {
            return false;
        } finally {
            fclose($handle);
        }
    }
}
