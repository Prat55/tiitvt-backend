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
        $this->info('Starting video optimization dispatch...');

        $force = $this->option('force');
        $dispatchCount = 0;

        // Process Courses
        $this->info('Finding Course lectures to optimize...');
        Course::chunk(50, function ($courses) use (&$dispatchCount, $force) {
            foreach ($courses as $course) {
                $lectures = $course->lectures;
                if (empty($lectures) || !is_array($lectures)) {
                    continue;
                }

                foreach ($lectures as $lecture) {
                    $path = $lecture['path'] ?? null;
                    if ($path) {
                        \App\Jobs\OptimizeVideoJob::dispatch($path, $force);
                        $dispatchCount++;
                    }
                }
            }
        });

        // Process Categories
        $this->info('Finding Category lectures to optimize...');
        Category::chunk(50, function ($categories) use (&$dispatchCount, $force) {
            foreach ($categories as $category) {
                $lectures = $category->lectures;
                if (empty($lectures) || !is_array($lectures)) {
                    continue;
                }

                foreach ($lectures as $lecture) {
                    $path = $lecture['path'] ?? null;
                    if ($path) {
                        \App\Jobs\OptimizeVideoJob::dispatch($path, $force);
                        $dispatchCount++;
                    }
                }
            }
        });

        $this->info("Dispatch complete! {$dispatchCount} jobs have been added to the queue.");
        $this->info("Make sure your queue worker is running (php artisan queue:work).");

        return 0;
    }
}
