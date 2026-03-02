<?php

use App\Models\CourseInquiry;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Livewire\Attributes\{Layout, Title};

new #[Layout('components.layouts.app')] #[Title('Course Inquiries')] class extends Component {
    use WithPagination;

    public string $search = '';
    public string $status = '';
    public array $sortBy = ['column' => 'created_at', 'direction' => 'desc'];
    public array $headers = [];

    public function boot(): void
    {
        $this->headers = [['key' => 'id', 'label' => '#', 'class' => 'w-12'], ['key' => 'name', 'label' => 'Name', 'class' => 'w-40'], ['key' => 'email', 'label' => 'Email', 'class' => 'w-48'], ['key' => 'phone', 'label' => 'Phone', 'class' => 'w-36'], ['key' => 'course', 'label' => 'Course', 'class' => 'w-36', 'sortable' => false], ['key' => 'status', 'label' => 'Status', 'class' => 'w-28'], ['key' => 'created_at', 'label' => 'Date', 'class' => 'w-40']];
    }

    public function updating(): void
    {
        $this->resetPage();
    }

    public function updateStatus(int $id, string $status): void
    {
        CourseInquiry::findOrFail($id)->update(['status' => $status]);
        $this->dispatch('toast', message: 'Status updated', type: 'success');
    }

    public function with(): array
    {
        return [
            'inquiries' => CourseInquiry::with('course')
                ->when(
                    $this->search,
                    fn($q) => $q->where(function ($q) {
                        $q->where('name', 'like', "%{$this->search}%")
                            ->orWhere('email', 'like', "%{$this->search}%")
                            ->orWhere('phone', 'like', "%{$this->search}%")
                            ->orWhereHas('course', fn($q) => $q->where('name', 'like', "%{$this->search}%"));
                    }),
                )
                ->when($this->status, fn($q) => $q->where('status', $this->status))
                ->orderBy($this->sortBy['column'], $this->sortBy['direction'])
                ->paginate(20),
        ];
    }
}; ?>

<div>
    <div class="flex justify-between items-start lg:items-center flex-col lg:flex-row mt-3 mb-5 gap-2">
        <div>
            <h1 class="text-2xl font-bold">Course Inquiries</h1>
            <div class="breadcrumbs text-sm">
                <ul class="flex">
                    <li><a href="{{ route('admin.index') }}" wire:navigate>Dashboard</a></li>
                    <li>Course Inquiries</li>
                </ul>
            </div>
        </div>

        <div class="flex gap-3 flex-wrap md:flex-nowrap">
            <x-input placeholder="Search name, email, course..." icon="o-magnifying-glass"
                wire:model.live.debounce="search" class="w-56" />
            <select wire:model.live="status" class="select select-bordered">
                <option value="">All Statuses</option>
                <option value="new">New</option>
                <option value="contacted">Contacted</option>
                <option value="enrolled">Enrolled</option>
                <option value="closed">Closed</option>
            </select>
        </div>
    </div>
    <hr class="mb-5">

    <x-table :headers="$headers" :rows="$inquiries" with-pagination :sort-by="$sortBy">
        @scope('cell_email', $inquiry)
            <a href="mailto:{{ $inquiry->email }}" class="link link-primary text-sm">
                {{ $inquiry->email }}
            </a>
        @endscope

        @scope('cell_phone', $inquiry)
            <a href="tel:{{ $inquiry->phone }}" class="text-sm">{{ $inquiry->phone }}</a>
        @endscope

        @scope('cell_course', $inquiry)
            <span class="badge badge-primary badge-sm">
                {{ $inquiry->course?->name ?? '—' }}
            </span>
        @endscope

        @scope('cell_status', $inquiry)
            @php
                $colors = [
                    'new' => 'badge-warning',
                    'contacted' => 'badge-info',
                    'enrolled' => 'badge-success',
                    'closed' => 'badge-ghost',
                ];
            @endphp
            <span class="badge {{ $colors[$inquiry->status] ?? 'badge-ghost' }} badge-sm capitalize">
                {{ $inquiry->status }}
            </span>
        @endscope

        @scope('cell_created_at', $inquiry)
            <span class="text-xs text-gray-500">
                {{ $inquiry->created_at->format('d M Y, h:i A') }}
            </span>
        @endscope

        @scope('actions', $inquiry)
            <div class="dropdown dropdown-end">
                <label tabindex="0" class="btn btn-xs btn-ghost">
                    <x-icon name="o-ellipsis-vertical" class="w-4 h-4" />
                </label>
                <ul tabindex="0" class="dropdown-content menu p-2 shadow bg-base-100 rounded-box w-40 z-50">
                    @foreach (['new', 'contacted', 'enrolled', 'closed'] as $s)
                        @if ($s !== $inquiry->status)
                            <li>
                                <button wire:click="updateStatus({{ $inquiry->id }}, '{{ $s }}')"
                                    class="capitalize text-sm">
                                    Mark as {{ $s }}
                                </button>
                            </li>
                        @endif
                    @endforeach
                </ul>
            </div>
        @endscope

        <x-slot:empty>
            <x-empty icon="o-inbox" message="No inquiries found." />
        </x-slot>
    </x-table>
</div>
