<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class DatabaseBackupNative extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:backup-native {--path=} {--compress}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a database backup using Laravel native methods';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting native database backup...');

        try {
            $connection = config('database.default');
            $database = config("database.connections.{$connection}.database");

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

            // Start building SQL dump
            $sql = "-- Database Backup\n";
            $sql .= "-- Generated on: " . now() . "\n";
            $sql .= "-- Database: {$database}\n\n";
            $sql .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
            $sql .= "START TRANSACTION;\n";
            $sql .= "SET time_zone = \"+00:00\";\n\n";

            // Get all tables
            $tables = DB::select('SHOW TABLES');
            $tableColumn = 'Tables_in_' . $database;

            foreach ($tables as $table) {
                $tableName = $table->$tableColumn;
                $this->info("Backing up table: {$tableName}");

                // Get table structure
                $createTable = DB::select("SHOW CREATE TABLE `{$tableName}`")[0];
                $sql .= "-- Table structure for table `{$tableName}`\n";
                $sql .= "DROP TABLE IF EXISTS `{$tableName}`;\n";
                $sql .= $createTable->{'Create Table'} . ";\n\n";

                // Get table data
                $rows = DB::table($tableName)->get();
                if ($rows->count() > 0) {
                    $sql .= "-- Data for table `{$tableName}`\n";

                    // Get column names
                    $columns = Schema::getColumnListing($tableName);
                    $columnList = '`' . implode('`, `', $columns) . '`';

                    foreach ($rows as $row) {
                        $values = [];
                        foreach ($columns as $column) {
                            $value = $row->$column;
                            if ($value === null) {
                                $values[] = 'NULL';
                            } else {
                                $values[] = "'" . addslashes($value) . "'";
                            }
                        }
                        $sql .= "INSERT INTO `{$tableName}` ({$columnList}) VALUES (" . implode(', ', $values) . ");\n";
                    }
                    $sql .= "\n";
                }
            }

            $sql .= "COMMIT;\n";

            // Write to file
            file_put_contents($fullPath, $sql);

            // Compress if requested
            if ($this->option('compress')) {
                $compressedPath = $fullPath . '.gz';
                $compressed = gzopen($compressedPath, 'w9');
                gzwrite($compressed, file_get_contents($fullPath));
                gzclose($compressed);

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

            $this->info("Native database backup completed successfully!");
            $this->info("File: {$filename}");
            $this->info("Size: " . $this->formatBytes(filesize($fullPath)));
            $this->info("Path: {$fullPath}");

            // Clean old backups (keep last 30 days)
            $this->cleanOldBackups($backupPath);
        } catch (\Exception $e) {
            $this->error("Backup failed: " . $e->getMessage());
            return 1;
        }

        return 0;
    }

    /**
     * Clean old backup files (older than 30 days)
     */
    private function cleanOldBackups($backupPath)
    {
        $this->info('Cleaning old backups...');

        $files = glob($backupPath . '/backup_*.sql*');
        $deletedCount = 0;

        foreach ($files as $file) {
            if (filemtime($file) < strtotime('-30 days')) {
                unlink($file);
                $deletedCount++;
            }
        }

        // Also clean database records
        DB::table('database_backups')
            ->where('created_at', '<', now()->subDays(30))
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
