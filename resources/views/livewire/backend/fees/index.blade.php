<?php

use Mary\Traits\Toast;
use App\Enums\RolesEnum;
use Illuminate\View\View;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Enums\InstallmentStatusEnum;
use App\Models\{Student, Center};
use Livewire\Attributes\{Layout, Url, Title};

new class extends Component {
    use WithPagination, Toast;
    #[Title('Fees Overview')]
    public $headers;
    #[Url]
    public string $search = '';
    public $filterDrawer = false;

    public $sortBy = ['column' => 'id', 'direction' => 'desc'];
    public $perPage = 20;

    // Filter properties
    #[Url]
    public $selectedCenter = null;
    #[Url]
    public $feeStatus = null; // 'paid', 'partial', 'unpaid'

    // Cached data
    public $centers = [];

    public function boot(): void
    {
        $this->headers = [['key' => 'tiitvt_reg_no', 'label' => 'Reg No', 'class' => 'w-32'], ['key' => 'first_name', 'label' => 'Student Name', 'class' => 'w-48']];

        // Only show center column to admin users
        if (hasAuthRole(RolesEnum::Admin->value)) {
            $this->headers[] = ['key' => 'center_name', 'label' => 'Center', 'class' => 'w-40', 'sortable' => false];
        }

        $this->headers = array_merge($this->headers, [['key' => 'course_fees', 'label' => 'Course Fees', 'class' => 'w-32 text-right'], ['key' => 'total_paid', 'label' => 'Paid', 'class' => 'w-32 text-right', 'sortable' => false], ['key' => 'remaining', 'label' => 'Remaining', 'class' => 'w-32 text-right', 'sortable' => false], ['key' => 'status', 'label' => 'Status', 'class' => 'w-24', 'sortable' => false]]);
    }

    public function mount(): void
    {
        // Only load centers for admin users
        if (hasAuthRole(RolesEnum::Admin->value)) {
            $this->centers = Center::select('id', 'name')->orderBy('name')->get()->map(fn($center) => ['name' => $center->name, 'id' => $center->id])->toArray();
        }
    }

    public function rendering(View $view): void
    {
        $query = Student::with(['center', 'installments'])
            ->whereNotNull('course_fees')
            ->where('course_fees', '>', 0);

        // Center users only see their own students
        if (hasAuthRole(RolesEnum::Center->value)) {
            $query->where('center_id', auth()->user()->center->id);
        }

        // Apply center filter (admin only)
        if ($this->selectedCenter) {
            $query->where('center_id', $this->selectedCenter);
        }

        // Get paginated results
        $students = $query->orderBy(...array_values($this->sortBy))->search($this->search)->paginate($this->perPage);

        // Calculate fee data for each student
        $students->getCollection()->transform(function ($student) {
            $downPayment = $student->down_payment ?? 0;
            $paidFromPayments = $student->installments->where('status', InstallmentStatusEnum::Paid)->sum('paid_amount');
            $student->total_paid = $downPayment + $paidFromPayments;
            $student->remaining = max(0, $student->course_fees - $student->total_paid);
            return $student;
        });

        // Apply fee status filter after calculation
        if ($this->feeStatus) {
            $filtered = $students->getCollection()->filter(function ($student) {
                return match ($this->feeStatus) {
                    'paid' => $student->remaining <= 0,
                    'partial' => $student->total_paid > 0 && $student->remaining > 0,
                    'unpaid' => $student->total_paid <= 0,
                    default => true,
                };
            });
            $students->setCollection($filtered);
        }

        $view->students = $students;

        // Summary stats
        $allStudents = Student::with(['installments'])
            ->whereNotNull('course_fees')
            ->where('course_fees', '>', 0);

        if (hasAuthRole(RolesEnum::Center->value)) {
            $allStudents->where('center_id', auth()->user()->center->id);
        }
        if ($this->selectedCenter) {
            $allStudents->where('center_id', $this->selectedCenter);
        }

        $allStudents = $allStudents->get();
        $totalFees = $allStudents->sum('course_fees');
        $totalPaid = $allStudents->sum(function ($s) {
            return ($s->down_payment ?? 0) + $s->installments->where('status', InstallmentStatusEnum::Paid)->sum('paid_amount');
        });

        $view->stats = [
            'totalFees' => $totalFees,
            'totalPaid' => $totalPaid,
            'totalRemaining' => max(0, $totalFees - $totalPaid),
            'studentCount' => $allStudents->count(),
            'fullyPaid' => $allStudents->filter(fn($s) => ($s->down_payment ?? 0) + $s->installments->where('status', InstallmentStatusEnum::Paid)->sum('paid_amount') >= $s->course_fees)->count(),
        ];

        $view->title('Fees Overview');
    }

    public function clearFilters(): void
    {
        $this->selectedCenter = null;
        $this->feeStatus = null;
        $this->search = '';
        $this->resetPage();
    }

    public function hasActiveFilters(): bool
    {
        return !empty($this->selectedCenter) || !empty($this->feeStatus) || !empty($this->search);
    }

    public function updatedSelectedCenter(): void
    {
        $this->resetPage();
    }
    public function updatedFeeStatus(): void
    {
        $this->resetPage();
    }
    public function updatedSearch(): void
    {
        $this->resetPage();
    }
}; ?>

<div>
    <!-- Header -->
    <div class="flex justify-between items-start lg:items-center flex-col lg:flex-row mt-3 mb-5 gap-2">
        <div>
            <h1 class="text-2xl font-bold">
                Fees Overview
            </h1>
            <div class="breadcrumbs text-sm">
                <ul class="flex">
                    <li>
                        <a href="{{ route('admin.index') }}" wire:navigate>
                            Dashboard
                        </a>
                    </li>
                    <li>
                        Fees Overview
                    </li>
                </ul>
            </div>
        </div>
        <div class="flex gap-3">
            <x-input placeholder="Search students, reg no..." icon="o-magnifying-glass"
                wire:model.live.debounce="search" />

            <x-button tooltip-left="Filter" class="btn-secondary" icon="o-funnel" wire:click="$toggle('filterDrawer')" />

            @if ($this->hasActiveFilters())
                <x-button icon="o-x-mark" class="btn-outline btn-primary" wire:click="clearFilters"
                    tooltip-left="Clear all filters" />
            @endif
        </div>
    </div>

    <hr class="mb-5">

    <!-- Summary Stats -->
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-3 mb-5">
        <div class="stats shadow bg-base-100">
            <div class="stat px-4 py-3">
                <div class="stat-title text-xs">Students</div>
                <div class="stat-value text-info text-lg">{{ number_format($stats['studentCount']) }}</div>
            </div>
        </div>
        <div class="stats shadow bg-base-100">
            <div class="stat px-4 py-3">
                <div class="stat-title text-xs">Total Fees</div>
                <div class="stat-value text-lg">₹{{ number_format($stats['totalFees'], 2) }}</div>
            </div>
        </div>
        <div class="stats shadow bg-base-100">
            <div class="stat px-4 py-3">
                <div class="stat-title text-xs">Total Collected</div>
                <div class="stat-value text-success text-lg">₹{{ number_format($stats['totalPaid'], 2) }}</div>
            </div>
        </div>
        <div class="stats shadow bg-base-100">
            <div class="stat px-4 py-3">
                <div class="stat-title text-xs">Total Remaining</div>
                <div class="stat-value text-warning text-lg">₹{{ number_format($stats['totalRemaining'], 2) }}</div>
            </div>
        </div>
        <div class="stats shadow bg-base-100">
            <div class="stat px-4 py-3">
                <div class="stat-title text-xs">Fully Paid</div>
                <div class="stat-value text-success text-lg">{{ $stats['fullyPaid'] }}/{{ $stats['studentCount'] }}
                </div>
            </div>
        </div>
    </div>

    <!-- Table -->
    <x-table :headers="$headers" :rows="$students" with-pagination :sort-by="$sortBy" per-page="perPage" :per-page-values="[20, 50, 100]">
        @scope('cell_tiitvt_reg_no', $student)
            <span class="font-mono text-sm font-medium">{{ $student->tiitvt_reg_no }}</span>
        @endscope
        @scope('cell_first_name', $student)
            <div>
                <div class="font-medium">{{ $student->full_name }}</div>
                @if ($student->fathers_name)
                    <div class="text-xs text-gray-500">{{ $student->fathers_name }}</div>
                @endif
            </div>
        @endscope
        @if (hasAuthRole(RolesEnum::Admin->value))
            @scope('cell_center_name', $student)
                @if ($student->center)
                    <span class="text-sm font-medium">{{ $student->center->name }}</span>
                @else
                    <span class="text-xs text-gray-400">-</span>
                @endif
            @endscope
        @endif
        @scope('cell_course_fees', $student)
            <span class="text-sm font-medium">₹{{ number_format($student->course_fees, 2) }}</span>
        @endscope
        @scope('cell_total_paid', $student)
            <span class="text-sm font-medium text-success">₹{{ number_format($student->total_paid, 2) }}</span>
        @endscope
        @scope('cell_remaining', $student)
            @if ($student->remaining > 0)
                <span class="text-sm font-medium text-warning">₹{{ number_format($student->remaining, 2) }}</span>
            @else
                <span class="text-sm font-medium text-success">₹0.00</span>
            @endif
        @endscope
        @scope('cell_status', $student)
            @if ($student->remaining <= 0)
                <x-badge value="Paid" class="badge-success badge-sm" />
            @elseif ($student->total_paid > 0)
                <x-badge value="Partial" class="badge-warning badge-sm" />
            @else
                <x-badge value="Unpaid" class="badge-error badge-sm" />
            @endif
        @endscope
        @scope('actions', $student)
            <x-button icon="o-eye" link="{{ route('admin.student.show', $student->id) }}?tab=fees"
                class="btn-xs btn-ghost" title="View Details" />
        @endscope

        <x-slot:empty>
            <x-empty icon="o-banknotes" message="No fee records found" />
        </x-slot:empty>
    </x-table>

    <!-- Filter Drawer -->
    <x-drawer wire:model="filterDrawer" class="w-11/12 lg:w-1/3" right title="Filter Fees">
        <div class="space-y-4">
            @if (hasAuthRole(RolesEnum::Admin->value))
                <x-choices-offline label="Filter by Center" wire:model.live="selectedCenter" :options="$centers"
                    placeholder="All Centers" single clearable searchable />
            @endif

            <x-select label="Fee Status" wire:model.live="feeStatus" placeholder="All Statuses" :options="[
                ['id' => 'paid', 'name' => 'Fully Paid'],
                ['id' => 'partial', 'name' => 'Partially Paid'],
                ['id' => 'unpaid', 'name' => 'Unpaid'],
            ]" />
        </div>

        <x-slot:actions>
            <x-button label="Clear All" icon="o-x-mark" class="btn-outline" wire:click="clearFilters" />
            <x-button label="Close" @click="$wire.filterDrawer = false" />
        </x-slot:actions>
    </x-drawer>
</div>
