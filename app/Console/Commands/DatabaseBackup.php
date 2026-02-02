<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\DatabaseBackupMail;
use App\Models\WebsiteSetting;
use Carbon\Carbon;

class DatabaseBackup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:backup {--path=} {--compress}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a database backup';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting database backup...');

        try {
            $connection = config('database.default');
            $database = config("database.connections.{$connection}.database");
            $username = config("database.connections.{$connection}.username");
            $password = config("database.connections.{$connection}.password");
            $host = config("database.connections.{$connection}.host");
            $port = config("database.connections.{$connection}.port");

            // Generate filename with timestamp
            $timestamp = Carbon::now()->format('Y-m-d_H-i-s');
            $filename = "backup_{$database}_{$timestamp}.sql";

            // Use custom path if provided, otherwise use storage/app/backups
            $backupPath = $this->option('path') ?: storage_path('app/backups');

            // Create directory if it doesn't exist
            if (!file_exists($backupPath)) {
                mkdir($backupPath, 0755, true);
            }

            $fullPath = $backupPath . '/' . $filename;

            // Build mysqldump command with proper password handling
            $passwordArg = $password ? "--password=" . escapeshellarg($password) : "";
            $command = sprintf(
                'mysqldump --host=%s --port=%s --user=%s %s --single-transaction --routines --triggers %s > %s',
                escapeshellarg($host),
                escapeshellarg($port),
                escapeshellarg($username),
                $passwordArg,
                escapeshellarg($database),
                escapeshellarg($fullPath)
            );

            // Debug: Log the command (without password for security)
            $debugCommand = str_replace($passwordArg, '[PASSWORD]', $command);
            $this->info("Executing command: " . $debugCommand);

            // Execute backup command
            $result = null;
            $output = [];
            exec($command . ' 2>&1', $output, $result);

            if ($result !== 0) {
                $errorMessage = 'Database backup failed. Check your database credentials and mysqldump installation.';
                if (!empty($output)) {
                    $errorMessage .= ' Error details: ' . implode(' ', $output);
                }
                throw new \Exception($errorMessage);
            }

            // Compress if requested
            if ($this->option('compress')) {
                $compressedPath = $fullPath . '.gz';
                $compressCommand = "gzip -c {$fullPath} > {$compressedPath}";
                exec($compressCommand);

                // Remove original file
                unlink($fullPath);
                $fullPath = $compressedPath;
                $filename .= '.gz';
            }

            // Store backup info in database
            DB::table('database_backups')->insert([
                'filename' => $filename,
                'path' => $fullPath,
                'size' => filesize($fullPath),
                'compressed' => $this->option('compress') ? 1 : 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->info("Database backup completed successfully!");
            $this->info("File: {$filename}");
            $this->info("Size: " . $this->formatBytes(filesize($fullPath)));
            $this->info("Path: {$fullPath}");

            // Clean old backups (keep last 30 days)
            $this->cleanOldBackups($backupPath);

            // Create a zip of the backup file and send it via email to backup address
            try {
                $zipFilename = pathinfo($filename, PATHINFO_FILENAME) . '.zip';
                $zipPath = $backupPath . '/' . $zipFilename;

                $zip = new \ZipArchive();
                if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true) {
                    $zip->addFile($fullPath, basename($fullPath));
                    $zip->close();

                    // Fetch backup_mail from website settings
                    $backupEmail = WebsiteSetting::first()?->backup_mail;
                    if ($backupEmail && trim($backupEmail) !== '') {
                        Mail::to($backupEmail)->send(new DatabaseBackupMail($zipPath, $zipFilename));
                        $this->info("Backup emailed to {$backupEmail}");
                    } else {
                        $this->warn('No backup_mail configured in website settings. Only database entry created, no email sent.');
                    }
                } else {
                    $this->error('Failed to create zip archive for backup.');
                }
            } catch (\Exception $e) {
                $this->error('Failed to email backup: ' . $e->getMessage());
            }
        } catch (\Exception $e) {
            $this->error("Backup failed: " . $e->getMessage());
            return 1;
        }

        return 0;
    }

    /**
     * Clean old backup files (older than configured days)
     */
    private function cleanOldBackups($backupPath)
    {
        $this->info('Cleaning old backups...');

        $files = glob($backupPath . '/backup_*.sql*');
        $deletedCount = 0;
        $backupDays = config('app.backup.days', 7);

        foreach ($files as $file) {
            if (filemtime($file) < strtotime("-{$backupDays} days")) {
                unlink($file);
                $deletedCount++;
            }
        }

        // Also clean database records
        DB::table('database_backups')
            ->where('created_at', '<', now()->subDays($backupDays))
            ->delete();

        if ($deletedCount > 0) {
            $this->info("Cleaned {$deletedCount} old backup files.");
        }
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes($size, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
            $size /= 1024;
        }

        return round($size, $precision) . ' ' . $units[$i];
    }
}
