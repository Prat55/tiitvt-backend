<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class FixStoragePermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'storage:fix-permissions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix storage directory permissions for file uploads and QR code generation';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Fixing storage permissions...');

        // Get the storage path
        $storagePath = storage_path('app/public');

        // Fix permissions for the main storage directory
        if (is_dir($storagePath)) {
            chmod($storagePath, 0775);
            $this->info('✓ Fixed permissions for storage/app/public');
        }

        // Fix permissions for students directory
        $studentsPath = $storagePath . '/students';
        if (is_dir($studentsPath)) {
            chmod($studentsPath, 0775);
            $this->info('✓ Fixed permissions for storage/app/public/students');
        }

        // Fix permissions for qr_codes directory
        $qrCodesPath = $studentsPath . '/qr_codes';
        if (is_dir($qrCodesPath)) {
            chmod($qrCodesPath, 0775);
            $this->info('✓ Fixed permissions for storage/app/public/students/qr_codes');
        } else {
            // Create the directory if it doesn't exist
            Storage::disk('public')->makeDirectory('students/qr_codes');
            chmod($qrCodesPath, 0775);
            $this->info('✓ Created and fixed permissions for storage/app/public/students/qr_codes');
        }

        // Ensure the storage link exists
        $this->call('storage:link');

        $this->info('Storage permissions fixed successfully!');
        $this->line('You can now generate QR codes without permission issues.');

        return Command::SUCCESS;
    }
}
