<?php

use App\Models\Center;
use Mary\Traits\Toast;
use App\Models\Student;
use Illuminate\View\View;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Attributes\{Layout};

new class extends Component {
    use WithPagination, Toast;

    #[Title('Center Details')]
    public $center;
    public $headers;

    #[Url]
    public string $search = '';
    public $sortBy = ['column' => 'first_name', 'direction' => 'asc'];

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
        $this->headers = [['key' => 'id', 'label' => '#', 'class' => 'w-1'], ['key' => 'tiitvt_reg_no', 'label' => 'Reg No', 'class' => 'w-32'], ['key' => 'full_name', 'label' => 'Student Name', 'class' => 'w-48'], ['key' => 'mobile', 'label' => 'Mobile', 'class' => 'w-32'], ['key' => 'email', 'label' => 'Email', 'class' => 'w-48'], ['key' => 'course_name', 'label' => 'Course', 'class' => 'w-40'], ['key' => 'course_fees', 'label' => 'Fees', 'class' => 'w-24'], ['key' => 'enrollment_date', 'label' => 'Enrolled', 'class' => 'w-32']];
    }

    public function rendering(View $view): void
    {
        $view->students = $this->center
            ->students()
            ->with('course')
            ->orderBy(...array_values($this->sortBy))
            ->whereAny(['tiitvt_reg_no', 'first_name', 'fathers_name', 'mobile', 'email', 'telephone_no'], 'like', "%$this->search%")
            ->orWhereHas('course', function ($query) {
                $query->where('name', 'like', "%$this->search%");
            })
            ->paginate(20);

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
                <ul class="flex sm:flex-nowrap flex-wrap">
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
                        <span>{{ $center->name }}</span>
                    </li>
                </ul>
            </div>
        </div>

        <div class="flex gap-3 justify-end sm:w-auto w-full">
            <x-button label="Edit Center" icon="o-pencil" class="btn-primary inline-flex" responsive
                link="{{ route('admin.center.edit', $center->uid) }}" />
            <x-button label="Back to Centers" icon="o-arrow-left" class="btn-primary inline-flex btn-outline" responsive
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
                        <x-icon name="o-user" class="w-5 h-5" />
                        Contact Information
                    </div>
                </x-slot:title>

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

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="stat">
                    <div class="stat-title">Total Students</div>
                    <div class="stat-value text-primary">{{ $center->students()->count() }}</div>
                </div>

                <div class="stat">
                    <div class="stat-title">Total Revenue</div>
                    <div class="stat-value text-info">
                        ₹{{ number_format($center->students()->sum('course_fees'), 2) }}
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
        </x-card>
    @endif

    <!-- Students Table Section -->
    <x-card>
        <x-slot:title>
            <div class="flex items-center justify-between md:flex-nowrap flex-wrap">
                <div class="flex items-center gap-2">
                    <x-icon name="o-academic-cap" class="w-5 h-5" />
                    Attached Students
                </div>

                <div class="flex gap-3 md:w-auto w-full md:mt-0 mt-2">
                    <x-input placeholder="Search students, registration no, phone, email..." icon="o-magnifying-glass"
                        wire:model.live.debounce="search" responsive />
                    <x-button label="Add Student" icon="o-plus" class="btn-primary" responsive
                        link="{{ route('admin.student.create') }}" />
                </div>
            </div>
        </x-slot:title>

        <x-table :headers="$headers" :rows="$students" with-pagination :sort-by="$sortBy">
            @scope('cell_tiitvt_reg_no', $student)
                <span class="font-mono text-sm font-medium">{{ $student->tiitvt_reg_no }}</span>
            @endscope

            @scope('cell_full_name', $student)
                <div class="flex items-center gap-2">
                    <div>
                        <div class="font-medium">{{ $student->full_name }}</div>
                        @if ($student->fathers_name)
                            <div class="text-xs text-gray-500">{{ $student->fathers_name }}</div>
                        @endif
                    </div>
                </div>
            @endscope

            @scope('cell_mobile', $student)
                @if ($student->mobile)
                    <span class="text-sm">{{ $student->mobile }}</span>
                @else
                    <span class="text-xs text-gray-400">-</span>
                @endif
            @endscope

            @scope('cell_email', $student)
                @if ($student->email)
                    <span class="text-sm">{{ $student->email }}</span>
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

            @scope('cell_course_fees', $student)
                @if ($student->course_fees)
                    <span class="text-sm font-medium">₹{{ number_format($student->course_fees, 2) }}</span>
                @else
                    <span class="text-xs text-gray-400">-</span>
                @endif
            @endscope

            @scope('cell_enrollment_date', $student)
                @if ($student->enrollment_date)
                    <span class="text-sm">{{ $student->enrollment_date->format('M d, Y') }}</span>
                @else
                    <span class="text-xs text-gray-400">-</span>
                @endif
            @endscope

            @scope('actions', $student)
                <div class="flex gap-1">
                    <x-button icon="o-eye" link="{{ route('admin.student.show', $student->id) }}"
                        class="btn-xs btn-ghost" title="View Details" />
                    <x-button icon="o-pencil" link="{{ route('admin.student.edit', $student->id) }}"
                        class="btn-xs btn-ghost" title="Edit Student" />
                </div>
            @endscope

            <x-slot:empty>
                <x-empty icon="o-academic-cap" message="No students found for this center" />
            </x-slot>
        </x-table>
    </x-card>
</div>
