<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\OverdueInstallmentService;

class HandleOverdueInstallments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'installments:handle-overdue';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Handle overdue installments: update status and send reminder emails';

    /**
     * Execute the console command.
     */
    public function handle(OverdueInstallmentService $overdueService)
    {
        $this->info('Starting overdue installment handling process...');

        try {
            $result = $overdueService->handleOverdueInstallments();

            $this->info("Overdue installment handling completed successfully!");
            $this->info("Status updates: {$result['status_updates']}");
            $this->info("Overdue reminders sent: {$result['reminders_sent']}");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Error handling overdue installments: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
