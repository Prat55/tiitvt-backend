<?php

use Livewire\Volt\Component;
use Livewire\Attributes\{Layout};
use App\Models\Center;
use Livewire\WithPagination;
use Illuminate\View\View;
use Livewire\Attributes\Url;
use Livewire\Attributes\Title;

new class extends Component {
    use WithPagination;
    #[Title('All Centers')]
    public $headers;
    #[Url]
    public string $search = '';

    public $sortBy = ['column' => 'name', 'direction' => 'asc'];
    // boot
    public function boot(): void
    {
        $this->headers = [['key' => 'id', 'label' => '#', 'class' => 'w-1'], ['key' => 'image', 'label' => 'Image', 'class' => 'w-1'], ['key' => 'name', 'label' => 'Name']];
    }

    public function rendering(View $view): void
    {
        $view->centers = Center::orderBy(...array_values($this->sortBy))
            ->where('name', 'like', "%$this->search%")
            ->paginate(20);
        $view->title('All Centers');
    }
};
?>

<div>
    <div class="flex justify-between items-start lg:items-center flex-col lg:flex-row mt-3 mb-5 gap-2">
        <div>
            <h1 class="text-2xl font-bold">
                All Centers
            </h1>
            <div class="breadcrumbs text-sm">
                <ul class="flex">
                    <li>
                        <a href="{{ route('admin.index') }}" wire:navigate>
                            Dashboard
                        </a>
                    </li>
                    <li>
                        All Centers
                    </li>
                </ul>
            </div>
        </div>
        <div class="flex gap-3">
            <x-input placeholder="Search ..." icon="o-magnifying-glass" wire:model.live.debounce="search" />
            <x-button label="Add Center" icon="o-plus" class="btn-primary inline-flex" responsive
                link="{{ route('admin.center.create') }}" />
        </div>
    </div>
    <hr class="mb-5">
    <x-table :headers="$headers" :rows="$centers" with-pagination :sort-by="$sortBy">
        @scope('cell_name', $client)
            <span class="badge badge-xs {{ $client->is_published === 1 ? 'badge-success' : 'badge-error' }}">
            </span>
            {{ $center->name }}
        @endscope
        @scope('cell_image', $center)
            @if ($center->image)
                <div class="avatar select-none">
                    <div class="w-8 rounded-md">
                        <img src="{{ $center->image }}" alt="{{ $center->name }}" />
                    </div>
                </div>
            @else
                <div class="select-none avatar avatar-placeholder">
                    <div class="w-10 rounded-md bg-neutral text-neutral-content">
                        <span class="text-lg">{{ substr($center->name, 0, 1) }}</span>
                    </div>
                </div>
            @endif
        @endscope
        @scope('actions', $center)
            <div class="flex">
                <x-button icon="o-pencil" link="{{ route('admin.center.edit', $center->id) }}" class="btn-xs" />
            </div>
        @endscope
        <x-slot:empty>
            <x-empty icon="o-no-symbol" message="No centers found" />
        </x-slot>
    </x-table>
</div>
