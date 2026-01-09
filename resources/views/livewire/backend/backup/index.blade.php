<?php
use App\Models\Backup;
use Livewire\Volt\Component;

new class extends Component {
    public $headers;
    public $search = '';
    public $sortBy = ['column' => 'lastModified', 'direction' => 'desc'];
    public $perPage = 20;
    public $files;

    public function boot()
    {
        $this->headers = [['key' => 'name', 'label' => 'File Name'], ['key' => 'size', 'label' => 'Size (KB)'], ['key' => 'lastModified', 'label' => 'Last Modified'], ['key' => 'actions', 'label' => 'Action', 'class' => 'w-32']];
    }

    public function rendering($view)
    {
        $files = Backup::recentFiles(30);
        if ($this->search) {
            $files = $files->filter(fn($f) => str_contains(strtolower($f['name']), strtolower($this->search)));
        }
        $files = $files->sortByDesc('lastModified')->values();

        $page = request('page', 1);
        $total = $files->count();
        $perPage = $this->perPage;
        $paged = $files->slice(($page - 1) * $perPage, $perPage)->values();
        $paginator = new \Illuminate\Pagination\LengthAwarePaginator($paged, $total, $perPage, $page, ['path' => request()->url(), 'query' => request()->query()]);
        $view->files = $paginator;
        $view->total = $total;
    }

    public function downloadFile($filename)
    {
        $path = 'backups/' . $filename;
        if (!\Illuminate\Support\Facades\Storage::disk('local')->exists($path)) {
            $this->error('File not found.');
            return;
        }

        return response()->streamDownload(function () use ($path) {
            echo \Illuminate\Support\Facades\Storage::disk('local')->get($path);
        }, $filename);
    }
};
?>

<div>
    <div class="flex justify-between items-start lg:items-center flex-col lg:flex-row mt-3 mb-5 gap-2">
        <div>
            <h1 class="text-2xl font-bold">Backup Files (Last 30 Days)</h1>
            <div class="breadcrumbs text-sm">
                <ul class="flex">
                    <li>
                        <a href="{{ route('admin.index') }}" wire:navigate>Dashboard</a>
                    </li>
                    <li>Backup Files</li>
                </ul>
            </div>
        </div>
        <div class="flex gap-3">
            <x-input placeholder="Search backups..." icon="o-magnifying-glass" wire:model.live.debounce="search" />
        </div>
    </div>
    <hr class="mb-5">

    <x-table :headers="$headers" :rows="$files" with-pagination :sort-by="$sortBy" per-page="perPage" :total="$total">
        @scope('cell_name', $file)
            <span class="font-medium">{{ $file['name'] }}</span>
        @endscope
        @scope('cell_size', $file)
            {{ number_format($file['size'] / 1024, 2) }} KB
        @endscope
        @scope('cell_lastModified', $file)
            {{ $file['lastModified']->format('Y-m-d H:i') }}
        @endscope
        @scope('cell_actions', $file)
            <x-button label="Download" class="btn-sm btn-primary" wire:click="downloadFile('{{ $file['name'] }}')" />
        @endscope
        <x-slot:empty>
            <x-empty icon="o-no-symbol" message="No backups found." />
        </x-slot>
    </x-table>
</div>
