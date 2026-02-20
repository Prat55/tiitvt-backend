<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\InstallmentReminderService;

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
    protected $description = 'Send payment reminders to students with outstanding balances based on enrollment date';

    /**
     * Execute the console command.
     */
    public function handle(InstallmentReminderService $reminderService)
    {
        $this->info('Starting payment reminder process...');

        try {
            $remindersSent = $reminderService->sendReminders();

            $this->info("Payment reminders sent successfully!");
            $this->info("Total reminders sent: {$remindersSent}");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Error sending installment reminders: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
