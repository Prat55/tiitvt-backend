<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Helpers\EmailNotificationHelper;
use Illuminate\Support\Facades\Log;

class SendScheduledNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public $backoff = 60;

    /**
     * Create a new job instance.
     *
     * @param string $type
     * @param string $email
     * @param array $data
     * @param array $options
     */
    public function __construct(
        private string $type,
        private string $email,
        private array $data,
        private array $options = []
    ) {}

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        try {
            Log::info("Processing scheduled notification job", [
                'type' => $this->type,
                'email' => $this->email,
                'job_id' => $this->job->getJobId()
            ]);

            $result = EmailNotificationHelper::sendNotificationByType(
                $this->type,
                $this->email,
                $this->data,
                $this->options
            );

            if ($result) {
                Log::info("Scheduled notification sent successfully", [
                    'type' => $this->type,
                    'email' => $this->email,
                    'job_id' => $this->job->getJobId()
                ]);
            } else {
                Log::warning("Scheduled notification failed to send", [
                    'type' => $this->type,
                    'email' => $this->email,
                    'job_id' => $this->job->getJobId()
                ]);
            }
        } catch (\Exception $e) {
            Log::error("Error processing scheduled notification job", [
                'type' => $this->type,
                'email' => $this->email,
                'job_id' => $this->job->getJobId(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     *
     * @param \Throwable $exception
     * @return void
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Scheduled notification job failed permanently", [
            'type' => $this->type,
            'email' => $this->email,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
}
