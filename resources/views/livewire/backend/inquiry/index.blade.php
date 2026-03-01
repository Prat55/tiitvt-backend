<?php

use App\Models\CourseInquiry;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Livewire\Attributes\{Layout, Title};

new #[Layout('components.layouts.app')] #[Title('Course Inquiries')] class extends Component {
    use WithPagination;

    public string $search = '';
    public string $status = '';

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
                ->latest()
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

        <div class="flex gap-3 flex-wrap">
            <x-input placeholder="Search name, email, course..." icon="o-magnifying-glass"
                wire:model.live.debounce="search" class="w-56" />
            <select wire:model.live="status" class="select select-bordered select-sm">
                <option value="">All Statuses</option>
                <option value="new">New</option>
                <option value="contacted">Contacted</option>
                <option value="enrolled">Enrolled</option>
                <option value="closed">Closed</option>
            </select>
        </div>
    </div>
    <hr class="mb-5">

    <div class="overflow-x-auto">
        <table class="table table-zebra w-full">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Course</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($inquiries as $inquiry)
                    <tr>
                        <td>{{ $inquiry->id }}</td>
                        <td class="font-medium">{{ $inquiry->name }}</td>
                        <td>
                            <a href="mailto:{{ $inquiry->email }}" class="link link-primary text-sm">
                                {{ $inquiry->email }}
                            </a>
                        </td>
                        <td>
                            <a href="tel:{{ $inquiry->phone }}" class="text-sm">{{ $inquiry->phone }}</a>
                        </td>
                        <td>
                            <span class="badge badge-primary badge-sm">
                                {{ $inquiry->course?->name ?? '—' }}
                            </span>
                        </td>
                        <td>
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
                        </td>
                        <td class="text-xs text-gray-500">
                            {{ $inquiry->created_at->format('d M Y, h:i A') }}
                        </td>
                        <td>
                            <div class="dropdown dropdown-end">
                                <label tabindex="0" class="btn btn-xs btn-ghost">
                                    <x-icon name="o-ellipsis-vertical" class="w-4 h-4" />
                                </label>
                                <ul tabindex="0"
                                    class="dropdown-content menu p-2 shadow bg-base-100 rounded-box w-40 z-50">
                                    @foreach (['new', 'contacted', 'enrolled', 'closed'] as $s)
                                        @if ($s !== $inquiry->status)
                                            <li>
                                                <button
                                                    wire:click="updateStatus({{ $inquiry->id }}, '{{ $s }}')"
                                                    class="capitalize text-sm">
                                                    Mark as {{ $s }}
                                                </button>
                                            </li>
                                        @endif
                                    @endforeach
                                </ul>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-8 text-gray-400">
                            <x-icon name="o-inbox" class="w-10 h-10 mx-auto mb-2" />
                            No inquiries found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $inquiries->links() }}
    </div>
</div>
