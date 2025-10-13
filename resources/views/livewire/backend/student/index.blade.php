<?php

use Mary\Traits\Toast;
use App\Enums\RolesEnum;
use Illuminate\View\View;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Storage;
use App\Models\{Student, Course, Center};
use Livewire\Attributes\{Layout, Url, Title};

new class extends Component {
    use WithPagination, Toast;
    #[Title('All Students')]
    public $headers;
    #[Url]
    public string $search = '';
    public $filterDrawer = false;

    public $sortBy = ['column' => 'tiitvt_reg_no', 'direction' => 'desc'];

    // Filter properties
    #[Url]
    public $selectedCenter = null;
    #[Url]
    public $selectedCourse = null;

    // Cached data to avoid repeated queries
    public $centers = [];
    public $courses = [];

    // boot
    public function boot(): void
    {
        $this->headers = [['key' => 'tiitvt_reg_no', 'label' => 'Reg No', 'class' => 'w-32'], ['key' => 'first_name', 'label' => 'Student Name', 'class' => 'w-48'], ['key' => 'mobile', 'label' => 'Mobile', 'class' => 'w-32']];

        // Only show center column to admin users
        if (hasAuthRole(RolesEnum::Admin->value)) {
            $this->headers[] = ['key' => 'center_name', 'label' => 'Center', 'class' => 'w-40', 'sortable' => false];
        }

        $this->headers[] = ['key' => 'course_name', 'label' => 'Course', 'class' => 'w-40', 'sortable' => false];
        $this->headers[] = ['key' => 'enrollment_date', 'label' => 'Enrolled', 'class' => 'w-32'];
    }

    // Mount method to cache filter options
    public function mount(): void
    {
        // Cache centers and courses data to avoid repeated queries
        $this->centers = Center::select('id', 'name')
            ->orderBy('name')
            ->get()
            ->map(function ($center) {
                return ['name' => $center->name, 'id' => $center->id];
            })
            ->toArray();

        $this->courses = Course::select('id', 'name')
            ->orderBy('name')
            ->get()
            ->map(function ($course) {
                return ['name' => $course->name, 'id' => $course->id];
            })
            ->toArray();
    }

    public function deleteStudent($id)
    {
        $student = Student::findOrFail($id);

        // Delete student images
        if ($student->student_signature_image) {
            $imagePath = str_replace('/storage/', '', $student->student_signature_image);
            Storage::disk('public')->delete($imagePath);
        }

        if ($student->student_image) {
            $imagePath = str_replace('/storage/', '', $student->student_image);
            Storage::disk('public')->delete($imagePath);
        }

        if ($student->student_qr_code) {
            $qrCodePath = str_replace('/storage/', '', $student->student_qr_code);
            Storage::disk('public')->delete($qrCodePath);
        }

        $student->delete();
        $this->success('Student deleted successfully!', position: 'toast-bottom');
    }

    public function rendering(View $view): void
    {
        $query = Student::with(['center', 'courses']);

        // Apply center filter for center users
        if (hasAuthRole(RolesEnum::Center->value)) {
            $query->where('center_id', auth()->user()->center->id);
        }

        // Apply filters
        if ($this->selectedCenter) {
            $query->where('center_id', $this->selectedCenter);
        }

        if ($this->selectedCourse) {
            $query->where('course_id', $this->selectedCourse);
        }

        $view->students = $query->orderBy(...array_values($this->sortBy))->search($this->search)->paginate(20);

        $view->title('All Students');
    }

    // Clear all filters
    public function clearFilters(): void
    {
        $this->selectedCenter = null;
        $this->selectedCourse = null;
        $this->search = '';
        $this->resetPage();
    }

    // Check if any filters are active
    public function hasActiveFilters(): bool
    {
        return !empty($this->selectedCenter) || !empty($this->selectedCourse) || !empty($this->search);
    }

    // Reset pagination when filters change
    public function updatedSelectedCenter(): void
    {
        $this->resetPage();
    }

    public function updatedSelectedCourse(): void
    {
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }
};
?>

<div>
    <div class="flex justify-between items-start lg:items-center flex-col lg:flex-row mt-3 mb-5 gap-2">
        <div>
            <h1 class="text-2xl font-bold">
                All Students
            </h1>
            <div class="breadcrumbs text-sm">
                <ul class="flex">
                    <li>
                        <a href="{{ route('admin.index') }}" wire:navigate>
                            Dashboard
                        </a>
                    </li>
                    <li>
                        All Students
                    </li>
                </ul>
            </div>
        </div>
        <div class="flex gap-3">
            <x-input placeholder="Search students, registration no, phone, email..." icon="o-magnifying-glass"
                wire:model.live.debounce="search" />

            <x-button icon="o-plus" class="btn-primary inline-flex" responsive
                link="{{ route('admin.student.create') }}" tooltip-left="Add Student" />

            <x-button tooltip-left="Filter Students" class="btn-secondary" icon="o-funnel"
                wire:click="$toggle('filterDrawer')" />

            @if ($this->hasActiveFilters())
                <x-button icon="o-x-mark" class="btn-outline btn-primary" wire:click="clearFilters"
                    tooltip-left="Clear all filters" />
            @endif
        </div>
    </div>

    <hr class="mb-5">

    <x-table :headers="$headers" :rows="$students" with-pagination :sort-by="$sortBy">
        @scope('cell_tiitvt_reg_no', $student)
            <span class="font-mono text-sm font-medium">{{ $student->tiitvt_reg_no }}</span>
        @endscope
        @scope('cell_first_name', $student)
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
        @if (hasAuthRole(RolesEnum::Admin->value))
            @scope('cell_center_name', $student)
                @if ($student->center)
                    <span class="text-sm font-medium">{{ $student->center->name }}</span>
                @else
                    <span class="text-xs text-gray-400">-</span>
                @endif
            @endscope
        @endif
        @scope('cell_course_name', $student)
            @if ($student->course)
                <span class="text-sm font-medium">{{ $student->course->name }}</span>
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
                <x-button icon="o-eye" link="{{ route('admin.student.show', $student->id) }}" class="btn-xs btn-ghost"
                    title="View Details" />
                <x-button icon="o-pencil" link="{{ route('admin.student.edit', $student->id) }}" class="btn-xs btn-ghost"
                    title="Edit Student" />
                <x-button icon="o-trash" class="btn-xs btn-error" title="Delete Student"
                    wire:click="deleteStudent({{ $student->id }})"
                    wire:confirm="Are you sure you want to delete this student?" />
            </div>
        @endscope

        <x-slot:empty>
            <x-empty icon="o-academic-cap" message="No students found" />
        </x-slot>
    </x-table>

    <x-drawer wire:model="filterDrawer" class="w-11/12 lg:w-1/3" right title="Filter Students">
        <div class="space-y-4">
            <div class="space-y-4">
                <x-choices-offline label="Filter by Center" wire:model.live="selectedCenter" :options="$centers"
                    placeholder="All Centers" single clearable searchable />

                <x-choices-offline label="Filter by Course" wire:model.live="selectedCourse" :options="$courses"
                    placeholder="All Courses" single clearable searchable />
            </div>
        </div>

        <x-slot:actions>
            <x-button label="Clear All" icon="o-x-mark" class="btn-outline" wire:click="clearFilters" />
            <x-button label="Close" @click="$wire.filterDrawer = false" />
        </x-slot:actions>
    </x-drawer>
</div>
