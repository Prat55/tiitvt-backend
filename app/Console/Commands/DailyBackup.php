<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class DailyBackup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:daily';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create daily database backup with compression';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting daily database backup...');

        // Call the native backup command with compression
        $this->call('db:backup-native', [
            '--compress' => true
        ]);

        $this->info('Daily backup completed successfully!');
    }
}
