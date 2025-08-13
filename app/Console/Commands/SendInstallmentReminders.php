<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Installment;
use App\Models\Student;
use App\Services\InstallmentReminderService;
use Carbon\Carbon;

class SendInstallmentReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'installments:send-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send installment reminders to students based on remaining days (7, 5, 3, 2, 1)';

    /**
     * Execute the console command.
     */
    public function handle(InstallmentReminderService $reminderService)
    {
        $this->info('Starting installment reminder process...');

        try {
            $remindersSent = $reminderService->sendReminders();

            $this->info("Installment reminders sent successfully!");
            $this->info("Total reminders sent: {$remindersSent}");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Error sending installment reminders: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
