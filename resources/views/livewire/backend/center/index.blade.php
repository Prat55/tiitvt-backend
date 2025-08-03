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
        $this->headers = [['key' => 'id', 'label' => '#', 'class' => 'w-1'], ['key' => 'institute_logo', 'label' => 'Image', 'class' => 'w-1'], ['key' => 'name', 'label' => 'Center Name', 'class' => 'w-48'], ['key' => 'phone', 'label' => 'Phone', 'class' => 'w-32'], ['key' => 'email', 'label' => 'Email', 'class' => 'w-48'], ['key' => 'owner_name', 'label' => 'Owner', 'class' => 'w-32'], ['key' => 'location', 'label' => 'Location', 'class' => 'w-40']];
    }

    public function rendering(View $view): void
    {
        $view->centers = Center::orderBy(...array_values($this->sortBy))
            ->whereAny(['name', 'phone', 'email', 'owner_name', 'state', 'country'], 'like', "%$this->search%")
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
            <x-input placeholder="Search centers, phone, email, owner..." icon="o-magnifying-glass"
                wire:model.live.debounce="search" />
            <x-button label="Add Center" icon="o-plus" class="btn-primary inline-flex" responsive
                link="{{ route('admin.center.create') }}" />
        </div>
    </div>
    <hr class="mb-5">
    <x-table :headers="$headers" :rows="$centers" with-pagination :sort-by="$sortBy">
        @scope('cell_name', $center)
            <div class="flex items-center gap-2">
                <span class="badge badge-xs {{ $center->status === 'active' ? 'badge-success' : 'badge-error' }}">
                    {{ $center->status === 'active' ? 'Active' : 'Inactive' }}
                </span>
                <span class="font-medium">{{ $center->name }}</span>
            </div>
        @endscope
        @scope('cell_institute_logo', $center)
            @if ($center->institute_logo)
                <div class="avatar select-none">
                    <div class="w-12 rounded-md">
                        <img src="{{ asset('storage/' . $center->institute_logo) }}" alt="{{ $center->name }}" />
                    </div>
                </div>
            @else
                <div class="select-none avatar avatar-placeholder">
                    <div class="w-8 rounded-md bg-neutral text-neutral-content">
                        <span class="text-xs">{{ substr($center->name, 0, 1) }}</span>
                    </div>
                </div>
            @endif
        @endscope
        @scope('cell_phone', $center)
            @if ($center->phone)
                <span class="text-sm">{{ $center->phone }}</span>
            @else
                <span class="text-xs text-gray-400">-</span>
            @endif
        @endscope
        @scope('cell_email', $center)
            @if ($center->email)
                <span class="text-sm">{{ $center->email }}</span>
            @else
                <span class="text-xs text-gray-400">-</span>
            @endif
        @endscope
        @scope('cell_owner_name', $center)
            @if ($center->owner_name)
                <span class="text-sm font-medium">{{ $center->owner_name }}</span>
            @else
                <span class="text-xs text-gray-400">-</span>
            @endif
        @endscope
        @scope('cell_location', $center)
            @if ($center->state || $center->country)
                <div class="text-xs">
                    @if ($center->state)
                        <div>{{ $center->state }}</div>
                    @endif
                    @if ($center->country)
                        <div class="text-gray-500">{{ $center->country }}</div>
                    @endif
                </div>
            @else
                <span class="text-xs text-gray-400">-</span>
            @endif
        @endscope
        @scope('cell_status', $center)
            <span class="badge badge-xs {{ $center->status === 'active' ? 'badge-success' : 'badge-error' }}">
                {{ $center->status === 'active' ? 'Active' : 'Inactive' }}
            </span>
        @endscope
        @scope('actions', $center)
            <div class="flex gap-1">
                <x-button icon="o-eye" link="{{ route('admin.center.show', $center->uid) }}" class="btn-xs btn-ghost"
                    title="View Details" />
                <x-button icon="o-pencil" link="{{ route('admin.center.edit', $center->uid) }}" class="btn-xs btn-ghost"
                    title="Edit Center" />
            </div>
        @endscope
        <x-slot:empty>
            <x-empty icon="o-no-symbol" message="No centers found" />
        </x-slot>
    </x-table>
</div>
