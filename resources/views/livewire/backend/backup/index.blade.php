<?php

use Carbon\Carbon;
use Mary\Traits\Toast;
use Illuminate\View\View;
use Livewire\WithPagination;
use Livewire\Volt\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Title;

new class extends Component {
    use Toast, WithPagination;
    #[Title('Database Backups')]
    public $headers;
    public $search = '';
    public $sortBy = ['column' => 'created_at', 'direction' => 'desc'];
    public $perPage = 20;
    public bool $createBackupModal = false;
    public bool $compressed = true;

    public function boot(): void
    {
        $this->headers = [['key' => 'created_at', 'label' => 'Date & Time'], ['key' => 'filename', 'label' => 'Filename', 'sortable' => false], ['key' => 'size', 'label' => 'Size', 'sortable' => false], ['key' => 'compressed', 'label' => 'Compressed']];
    }

    public function rendering(View $view): void
    {
        $view->backups = $this->getBackups()->paginate($this->perPage);
    }

    private function getBackups()
    {
        $query = DB::table('database_backups')
            ->when($this->search, function ($query) {
                $query->where('filename', 'like', '%' . $this->search . '%');
            })
            ->orderBy($this->sortBy['column'], $this->sortBy['direction']);

        return $query;
    }

    public function createBackup()
    {
        try {
            // Use native backup command that doesn't rely on mysqldump
            $exitCode = \Illuminate\Support\Facades\Artisan::call('db:backup-native', [
                '--compress' => $this->compressed,
            ]);

            if ($exitCode === 0) {
                $this->success('Database backup created successfully!');
                $this->createBackupModal = false;
                $this->dispatch('$refresh');
            } else {
                $this->error('Failed to create backup. Please check the logs.');
            }
        } catch (\Exception $e) {
            $this->error('Error creating backup: ' . $e->getMessage());
        }
    }

    public function downloadBackup($backupId)
    {
        try {
            $backup = DB::table('database_backups')->find($backupId);

            if (!$backup) {
                $this->error('Backup not found.');
                return;
            }

            if (!file_exists($backup->path)) {
                $this->error('Backup file not found on disk.');
                return;
            }

            // Redirect to download route instead of returning response directly
            return redirect()->route('admin.backup.download', ['id' => $backupId]);
        } catch (\Exception $e) {
            $this->error('Error downloading backup: ' . $e->getMessage());
        }
    }

    public function deleteBackup($backupId)
    {
        try {
            $backup = DB::table('database_backups')->find($backupId);

            if (!$backup) {
                $this->error('Backup not found.');
                return;
            }

            // Delete file from disk
            if (file_exists($backup->path)) {
                unlink($backup->path);
            }

            // Delete record from database
            DB::table('database_backups')->where('id', $backupId)->delete();

            $this->success('Backup deleted successfully!');
            $this->dispatch('$refresh');
        } catch (\Exception $e) {
            $this->error('Error deleting backup: ' . $e->getMessage());
        }
    }

    public function sortBy($column)
    {
        if ($this->sortBy['column'] === $column) {
            $this->sortBy['direction'] = $this->sortBy['direction'] === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = ['column' => $column, 'direction' => 'asc'];
        }
        $this->resetPage();
    }

    public function formatBytes($size, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
            $size /= 1024;
        }

        return round($size, $precision) . ' ' . $units[$i];
    }
};
?>
<div>
    <div class="flex flex-col items-center justify-between gap-2 mt-3 mb-5 lg:items-center lg:flex-row">
        <div class="text-sm breadcrumbs">
            <h1 class="mb-2 text-2xl font-bold">
                Database Backups
            </h1>
            <ul>
                <li>
                    <a href="{{ route('admin.index') }}" wire:navigate>
                        Dashboard
                    </a>
                </li>
                <li>
                    Database Backups
                </li>
            </ul>
        </div>
        <div class="flex gap-3">
            <x-input placeholder="Search backups..." clearable wire:model.live="search" />
            <x-button icon="o-plus" class="btn-primary" wire:click="$toggle('createBackupModal')" label="Create Backup" />
        </div>
    </div>

    <hr class="my-5">

    <x-table :headers="$headers" :rows="$backups" with-pagination :sort-by="$sortBy" per-page="perPage" :per-page-values="[10, 20, 50]">

        @scope('cell_size', $backup)
            {{ $this->formatBytes($backup->size) }}
        @endscope

        @scope('cell_compressed', $backup)
            <span class="badge {{ $backup->compressed ? 'badge-success' : 'badge-warning' }}">
                {{ $backup->compressed ? 'Yes' : 'No' }}
            </span>
        @endscope

        @scope('cell_created_at', $backup)
            {{ Carbon::parse($backup->created_at)->format('d M Y g:i A') }}
        @endscope

        @scope('actions', $backup)
            <div class="flex gap-2">
                <x-button icon="o-arrow-down-tray" class="btn-sm btn-primary"
                    wire:click="downloadBackup({{ $backup->id }})" tooltip="Download Backup" />
                <x-button icon="o-trash" class="btn-sm btn-error" wire:click="deleteBackup({{ $backup->id }})"
                    wire:confirm="Are you sure you want to delete this backup?" tooltip-left="Delete Backup" />
            </div>
        @endscope

        <x-slot:empty>
            <x-empty icon="o-server" message="No backups found." />
        </x-slot>
    </x-table>

    <!-- Create Backup Modal -->
    <x-modal wire:model="createBackupModal" title="Create Database Backup">
        <div class="mt-3">
            <x-form wire:submit="createBackup">
                <x-checkbox label="Compress backup (recommended)" wire:model="compressed" />

                <div class="mt-4 p-4 bg-base-200 rounded-lg">
                    <h4 class="font-semibold mb-2">Backup Information:</h4>
                    <ul class="text-sm space-y-1">
                        <li>• Backup will be stored in storage/app/backups/</li>
                        <li>• Old backups (30+ days) will be automatically cleaned</li>
                        <li>• Compressed backups save disk space</li>
                        <li>• Backup process may take a few minutes</li>
                    </ul>
                </div>

                <x-slot:actions>
                    <x-button label="Cancel" @click="$wire.createBackupModal = false" />
                    <x-button label="Create Backup" type="submit" class="btn-primary" spinner="createBackup" />
                </x-slot:actions>
            </x-form>
        </div>
    </x-modal>
</div>
