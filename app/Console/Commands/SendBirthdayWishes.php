<?php

namespace App\Console\Commands;

use App\Jobs\SendBirthdayWishesJob;
use Illuminate\Console\Command;

class SendBirthdayWishes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'students:send-birthday-wishes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatch birthday wish emails to students whose birthday is today';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Dispatching birthday wishes job to the queue...');

        SendBirthdayWishesJob::dispatch();

        $this->info('Birthday wishes job has been queued successfully.');

        return Command::SUCCESS;
    }
}
