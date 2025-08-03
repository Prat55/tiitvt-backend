<?php

use App\Models\Center;
use Mary\Traits\Toast;
use App\Models\Student;
use Illuminate\View\View;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Title;
use Livewire\Attributes\{Layout};

new class extends Component {
    use WithPagination, Toast;
    #[Title('Center Details')]
    public $center;
    public $studentHeaders;
    #[Url]
    public string $search = '';
    public $sortBy = ['column' => 'name', 'direction' => 'asc'];

    public function mount($uid)
    {
        $this->center = Center::whereUid($uid)->first();

        if (!$this->center) {
            $this->error('Center not found!', position: 'toast-bottom', redirect: route('admin.center.index'));
            return;
        }
    }

    public function boot(): void
    {
        $this->studentHeaders = [['key' => 'id', 'label' => '#', 'class' => 'w-1'], ['key' => 'name', 'label' => 'Student Name', 'class' => 'w-48'], ['key' => 'phone', 'label' => 'Phone', 'class' => 'w-32'], ['key' => 'course_name', 'label' => 'Course', 'class' => 'w-40'], ['key' => 'fee', 'label' => 'Fee', 'class' => 'w-24'], ['key' => 'join_date', 'label' => 'Join Date', 'class' => 'w-32'], ['key' => 'status', 'label' => 'Status', 'class' => 'w-24']];
    }

    public function rendering(View $view): void
    {
        $view->students = $this->center
            ->students()
            ->with('course')
            ->orderBy(...array_values($this->sortBy))
            ->whereAny(['first_name', 'last_name', 'phone'], 'like', "%$this->search%")
            ->paginate(15);

        $view->title('Center Details - ' . $this->center->name);
    }
};
?>

<div>
    <!-- Header Section -->
    <div class="flex justify-between items-start lg:items-center flex-col lg:flex-row mt-3 mb-5 gap-2">
        <div>
            <h1 class="text-2xl font-bold">
                Center Details
            </h1>
            <div class="breadcrumbs text-sm">
                <ul class="flex">
                    <li>
                        <a href="{{ route('admin.index') }}" wire:navigate>
                            Dashboard
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('admin.center.index') }}" wire:navigate>
                            All Centers
                        </a>
                    </li>
                    <li>
                        {{ $center->name }}
                    </li>
                </ul>
            </div>
        </div>
        <div class="flex gap-3">
            <x-button label="Edit Center" icon="o-pencil" class="btn-primary inline-flex" responsive
                link="{{ route('admin.center.edit', $center->uid) }}" />
            <x-button label="Back to Centers" icon="o-arrow-left" class="btn-ghost inline-flex" responsive
                link="{{ route('admin.center.index') }}" />
        </div>
    </div>
    <hr class="mb-5">

    <!-- Center Information Cards -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <div>
            <!-- Center Logo and Basic Info -->
            <x-card class="lg:col-span-1">
                <div class="flex flex-col items-center text-center space-y-4">
                    @if ($center->institute_logo)
                        <div class="avatar">
                            <div class="w-24 h-24 rounded-lg">
                                <img src="{{ asset('storage/' . $center->institute_logo) }}"
                                    alt="{{ $center->name }}" />
                            </div>
                        </div>
                    @else
                        <div class="avatar avatar-placeholder">
                            <div class="w-24 h-24 rounded-lg bg-primary text-primary-content text-2xl font-bold">
                                {{ substr($center->name, 0, 1) }}
                            </div>
                        </div>
                    @endif

                    <div>
                        <h2 class="text-xl font-bold">{{ $center->name }}</h2>
                        <span
                            class="badge badge-lg {{ $center->status === 'active' ? 'badge-success' : 'badge-error' }} mt-2">
                            {{ $center->status === 'active' ? 'Active' : 'Inactive' }}
                        </span>
                    </div>

                    <div class="text-sm text-gray-400">
                        <p><strong>Center ID:</strong> {{ $center->uid }}</p>
                    </div>
                </div>
            </x-card>

            <!-- Contact Information -->
            <x-card class="lg:col-span-1 mt-3">
                <x-slot:title>
                    <div class="flex items-center gap-2">
                        <x-icon name="o-phone" class="w-5 h-5" />
                        Contact Information
                    </div>
                </x-slot:title>

                <div class="flex gap-3">
                    <div class="space-y-3">
                        @if ($center->phone)
                            <div class="flex items-center gap-2">
                                <x-icon name="o-phone" class="w-4 h-4 text-gray-500" />
                                <span class="text-sm">{{ $center->phone }}</span>
                            </div>
                        @endif

                        @if ($center->email)
                            <div class="flex items-center gap-2">
                                <x-icon name="o-envelope" class="w-4 h-4 text-gray-500" />
                                <span class="text-sm">{{ $center->email }}</span>
                            </div>
                        @endif

                        @if ($center->owner_name)
                            <div class="flex items-center gap-2">
                                <x-icon name="o-user" class="w-4 h-4 text-gray-500" />
                                <span class="text-sm"><strong>Owner:</strong> {{ $center->owner_name }}</span>
                            </div>
                        @endif

                        @if ($center->address)
                            <div class="flex items-start gap-2">
                                <x-icon name="o-map-pin" class="w-4 h-4 text-gray-500 mt-0.5" />
                                <span class="text-sm">{{ $center->address }}</span>
                            </div>
                        @endif

                        @if ($center->state || $center->country)
                            <div class="flex items-center gap-2">
                                <x-icon name="o-globe-alt" class="w-4 h-4 text-gray-500" />
                                <span class="text-sm">
                                    @if ($center->state && $center->country)
                                        {{ $center->state }}, {{ $center->country }}
                                    @elseif ($center->state)
                                        {{ $center->state }}
                                    @else
                                        {{ $center->country }}
                                    @endif
                                </span>
                            </div>
                        @endif
                    </div>
                    <div>

                    </div>
                </div>
            </x-card>
        </div>

        <!-- Statistics -->
        <x-card class="lg:col-span-2">
            <x-slot:title>
                <div class="flex items-center gap-2">
                    <x-icon name="o-chart-bar" class="w-5 h-5" />
                    Statistics
                </div>
            </x-slot:title>

            <div class="space-y-4">
                <div class="stat">
                    <div class="stat-title">Total Students</div>
                    <div class="stat-value text-primary">{{ $center->students()->count() }}</div>
                </div>

                <div class="stat">
                    <div class="stat-title">Active Students</div>
                    <div class="stat-value text-success">{{ $center->students()->where('status', 'active')->count() }}
                    </div>
                </div>

                <div class="stat">
                    <div class="stat-title">Total Revenue</div>
                    <div class="stat-value text-info">
                        ₹{{ number_format($center->students()->sum('fee'), 2) }}
                    </div>
                </div>
            </div>
        </x-card>
    </div>

    <!-- Office Photos Section -->
    @if ($center->front_office_photo || $center->back_office_photo)
        <x-card class="mb-8">
            <x-slot:title>
                <div class="flex items-center gap-2">
                    <x-icon name="o-photo" class="w-5 h-5" />
                    Office Photos
                </div>
            </x-slot:title>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                @if ($center->front_office_photo)
                    <div
                        class="group relative overflow-hidden rounded-xl shadow-lg hover:shadow-xl transition-all duration-300">
                        <div
                            class="absolute inset-0 bg-gradient-to-b from-transparent via-transparent to-black/80 z-10">
                        </div>
                        <img src="{{ asset('storage/' . $center->front_office_photo) }}" alt="Front Office"
                            class="w-full h-64 object-cover group-hover:scale-105 transition-transform duration-300" />
                        <div class="absolute bottom-0 left-0 right-0 p-4 z-20">
                            <h4 class="text-white font-bold text-lg mb-1 flex items-center gap-2">
                                <x-icon name="o-building-office" class="w-5 h-5" />
                                Front Office
                            </h4>
                        </div>
                    </div>
                @endif

                @if ($center->back_office_photo)
                    <div
                        class="group relative overflow-hidden rounded-xl shadow-lg hover:shadow-xl transition-all duration-300">
                        <div
                            class="absolute inset-0 bg-gradient-to-b from-transparent via-transparent to-black/80 z-10">
                        </div>
                        <img src="{{ asset('storage/' . $center->back_office_photo) }}" alt="Back Office"
                            class="w-full h-64 object-cover group-hover:scale-105 transition-transform duration-300" />
                        <div class="absolute bottom-0 left-0 right-0 p-4 z-20">
                            <h4 class="text-white font-bold text-lg mb-1 flex items-center gap-2">
                                <x-icon name="o-cog" class="w-5 h-5" />
                                Back Office
                            </h4>
                        </div>
                    </div>
                @endif
            </div>

            @if (!$center->front_office_photo && !$center->back_office_photo)
                <div class="text-center py-12">
                    <x-icon name="o-photo" class="w-16 h-16 text-gray-300 mx-auto mb-4" />
                    <h4 class="text-lg font-semibold text-gray-600 mb-2">No Office Photos Available</h4>
                    <p class="text-gray-500">Office photos will be displayed here once uploaded</p>
                </div>
            @endif
        </x-card>
    @endif

    <!-- Students Table Section -->
    <x-card>
        <x-slot:title>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <x-icon name="o-academic-cap" class="w-5 h-5" />
                    Attached Students
                </div>
                <div class="flex gap-3">
                    <x-input placeholder="Search students..." icon="o-magnifying-glass"
                        wire:model.live.debounce="search" class="w-64" />
                    <x-button label="Add Student" icon="o-plus" class="btn-primary btn-sm" />
                </div>
            </div>
        </x-slot:title>

        <x-table :headers="$studentHeaders" :rows="$students" with-pagination :sort-by="$sortBy">
            @scope('cell_name', $student)
                <div class="flex items-center gap-2">
                    <span class="badge badge-xs {{ $student->status === 'active' ? 'badge-success' : 'badge-error' }}">
                        {{ $student->status === 'active' ? 'Active' : 'Inactive' }}
                    </span>
                    <span class="font-medium">{{ $student->name }}</span>
                </div>
            @endscope

            @scope('cell_phone', $student)
                @if ($student->phone)
                    <span class="text-sm">{{ $student->phone }}</span>
                @else
                    <span class="text-xs text-gray-400">-</span>
                @endif
            @endscope

            @scope('cell_course_name', $student)
                @if ($student->course)
                    <span class="text-sm font-medium">{{ $student->course->name }}</span>
                @else
                    <span class="text-xs text-gray-400">-</span>
                @endif
            @endscope

            @scope('cell_fee', $student)
                @if ($student->fee)
                    <span class="text-sm font-medium">₹{{ number_format($student->fee, 2) }}</span>
                @else
                    <span class="text-xs text-gray-400">-</span>
                @endif
            @endscope

            @scope('cell_join_date', $student)
                @if ($student->join_date)
                    <span class="text-sm">{{ $student->join_date->format('M d, Y') }}</span>
                @else
                    <span class="text-xs text-gray-400">-</span>
                @endif
            @endscope

            @scope('cell_status', $student)
                <span class="badge badge-xs {{ $student->status === 'active' ? 'badge-success' : 'badge-error' }}">
                    {{ $student->status === 'active' ? 'Active' : 'Inactive' }}
                </span>
            @endscope

            @scope('actions', $student)
                <div class="flex gap-1">
                    <x-button icon="o-eye" class="btn-xs btn-ghost" title="View Details" />
                    <x-button icon="o-pencil" class="btn-xs btn-ghost" title="Edit Student" />
                </div>
            @endscope

            <x-slot:empty>
                <x-empty icon="o-academic-cap" message="No students found for this center" />
            </x-slot>
        </x-table>
    </x-card>
</div>
