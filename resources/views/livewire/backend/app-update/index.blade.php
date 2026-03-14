<?php

use App\Models\AppUpdate;
use Illuminate\Support\Facades\Storage;
use Livewire\Volt\Component;
use Mary\Traits\Toast;
use Livewire\Attributes\Title;
use Livewire\WithPagination;

new #[Title('App Updates')] class extends Component {
    use Toast, WithPagination;

    public $type = 'tiitvt';
    public $version = '';
    public $version_code = '';
    public $changelog = '';
    public $published_at = '';
    public $apk_path = '';

    public $showCreateModal = false;
    public $search = '';

    public array $sortBy = ['column' => 'created_at', 'direction' => 'desc'];

    public function mount()
    {
        $this->published_at = now()->format('Y-m-d\TH:i');
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function headers(): array
    {
        return [['key' => 'id', 'label' => '#', 'class' => 'w-1'], ['key' => 'type', 'label' => 'Type'], ['key' => 'version', 'label' => 'Version'], ['key' => 'version_code', 'label' => 'Code'], ['key' => 'published_at', 'label' => 'Published At'], ['key' => 'created_at', 'label' => 'Created At']];
    }

    public function appUpdates()
    {
        return AppUpdate::query()
            ->when($this->search, function ($query) {
                $query->where('version', 'like', '%' . $this->search . '%')->orWhere('type', 'like', '%' . $this->search . '%');
            })
            ->orderBy(...array_values($this->sortBy))
            ->paginate(10);
    }

    public function save()
    {
        $this->validate([
            'type' => 'required|in:tiitvt,it-centre',
            'version' => 'required|string',
            'version_code' => 'required|integer',
            'apk_path' => 'required|string',
            'changelog' => 'nullable|string',
            'published_at' => 'required',
        ]);

        // Move APK from tmp to final destination with formatted name
        $extension = pathinfo($this->apk_path, PATHINFO_EXTENSION) ?: 'apk';
        $filename = "{$this->type}_{$this->version}({$this->version_code}).{$extension}";
        $finalPath = 'app-updates/' . $filename;

        Storage::disk('public')->move($this->apk_path, $finalPath);

        AppUpdate::create([
            'type' => $this->type,
            'version' => $this->version,
            'version_code' => $this->version_code,
            'apk_path' => $finalPath,
            'changelog' => $this->changelog,
            'published_at' => $this->published_at,
        ]);

        $this->reset(['type', 'version', 'version_code', 'changelog', 'apk_path', 'showCreateModal']);
        $this->published_at = now()->format('Y-m-d\TH:i');
        $this->success('App update published successfully.');
    }

    public function delete(AppUpdate $appUpdate)
    {
        Storage::disk('public')->delete($appUpdate->apk_path);
        $appUpdate->delete();
        $this->success('App update deleted successfully.');
    }
}; ?>

<div>
    <x-header title="App Updates" separator progress-indicator>
        <x-slot:actions>
            <x-input placeholder="Search..." wire:model.live.debounce="search" icon="o-magnifying-glass" />
            <x-button label="New Update" icon="o-plus" class="btn-primary" @click="$wire.showCreateModal = true" />
        </x-slot:actions>
    </x-header>

    <x-card>
        <x-table :headers="$this->headers()" :rows="$this->appUpdates()" :sort-by="$sortBy" with-pagination>
            @scope('cell_type', $update)
                <x-badge :value="$update->type" :class="$update->type == 'tiitvt' ? 'badge-primary' : 'badge-secondary'" />
            @endscope

            @scope('cell_published_at', $update)
                {{ $update->published_at ? $update->published_at->format('M d, Y H:i') : 'N/A' }}
            @endscope

            @scope('cell_created_at', $update)
                {{ $update->created_at->format('M d, Y H:i') }}
            @endscope

            @scope('actions', $update)
                <x-button icon="o-trash" class="btn-ghost btn-sm text-error" wire:click="delete({{ $update->id }})"
                    wire:confirm="Are you sure you want to delete this update?" />
            @endscope

            <x-slot:empty>
                <x-empty message="No App Updates Found" icon="o-device-phone-mobile" />
            </x-slot:empty>
        </x-table>
    </x-card>

    <x-modal wire:model="showCreateModal" title="Push New App Update" separator>
        <x-form wire:submit="save">
            <x-select label="App Type" wire:model="type" :options="[['id' => 'tiitvt', 'name' => 'TIITVT'], ['id' => 'it-centre', 'name' => 'IT Centre']]" />
            <div class="grid grid-cols-2 gap-4">
                <x-input label="Version" wire:model="version" placeholder="1.0.0" />
                <x-input label="Version Code" wire:model="version_code" type="number" placeholder="1" />
            </div>
            <x-datetime label="Publish At" wire:model="published_at" />
            <x-textarea label="Changelog" wire:model="changelog" placeholder="Explain what's new..."
                hint="Supports markdown" />

            <div class="mb-4">
                <label class="label">
                    <span class="label-text font-semibold">APK File</span>
                </label>
                <div x-data="chunkedUpload()" class="space-y-2">
                    <input type="file" @change="startUpload($event)" class="file-input file-input-bordered w-full"
                        accept=".apk" :disabled="uploading" />

                    <div class="w-full bg-gray-200 rounded-full h-4 dark:bg-gray-700 mt-2" x-show="uploading" x-cloak>
                        <div class="bg-primary h-4 rounded-full transition-all duration-300 text-xs text-white text-center flex items-center justify-center"
                            :style="`width: ${progress}%`" x-text="`${progress}%` ">
                        </div>
                    </div>

                    <p x-show="uploading" x-cloak class="text-xs text-gray-500">Uploading...</p>
                    <p x-show="completed" x-cloak class="text-xs text-success font-semibold flex items-center gap-1">
                        <x-icon name="o-check-circle" class="w-4 h-4" /> APK Uploaded Successfully
                    </p>
                    <input type="hidden" wire:model="apk_path" />
                    @error('apk_path')
                        <div class="text-error text-xs">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </x-form>

        <x-slot:actions>
            <x-button label="Cancel" @click="$wire.showCreateModal = false" />
            <x-button label="Publish Update" class="btn-primary" wire:click="save" spinner="save" />
        </x-slot:actions>
    </x-modal>

    @push('scripts')
        <script>
            function chunkedUpload() {
                return {
                    uploading: false,
                    progress: 0,
                    completed: false,
                    uploadId: null,

                    async startUpload(event) {
                        const file = event.target.files[0];
                        if (!file) return;

                        this.uploading = true;
                        this.progress = 0;
                        this.completed = false;

                        try {
                            // 1. Initialize
                            const initRes = await fetch('/api/uploads/init', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                                        'content')
                                },
                                body: JSON.stringify({
                                    filename: file.name,
                                    totalSize: file.size
                                })
                            });
                            const {
                                uploadId
                            } = await initRes.json();
                            this.uploadId = uploadId;

                            // 2. Upload Chunks
                            const chunkSize = 5 * 1024 * 1024; // 5MB chunks
                            const totalChunks = Math.ceil(file.size / chunkSize);

                            for (let i = 0; i < totalChunks; i++) {
                                const start = i * chunkSize;
                                const end = Math.min(start + chunkSize, file.size);
                                const chunk = file.slice(start, end);

                                const formData = new FormData();
                                formData.append('chunk', chunk);
                                formData.append('index', i);

                                await fetch(`/api/uploads/${this.uploadId}/chunk`, {
                                    method: 'POST',
                                    headers: {
                                        'Accept': 'application/json',
                                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                            .getAttribute('content')
                                    },
                                    body: formData
                                });

                                this.progress = Math.round(((i + 1) / totalChunks) * 100);
                            }

                            // 3. Complete
                            const completeRes = await fetch(`/api/uploads/${this.uploadId}/complete`, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                        .getAttribute('content')
                                }
                            });
                            const {
                                path
                            } = await completeRes.json();

                            this.completed = true;
                            this.uploading = false;
                            this.$wire.set('apk_path', path);

                        } catch (error) {
                            console.error('Upload failed:', error);
                            alert('Upload failed. Please try again.');
                            this.uploading = false;
                        }
                    }
                }
            }
        </script>
    @endpush
</div>
