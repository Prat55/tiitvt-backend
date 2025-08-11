<?php

use App\Models\Student;
use Illuminate\View\View;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Url;
use Livewire\Attributes\Title;
use Livewire\Attributes\{Layout};

new class extends Component {
    use WithPagination;
    #[Title('All Students')]
    public $headers;
    #[Url]
    public string $search = '';

    public $sortBy = ['column' => 'first_name', 'direction' => 'asc'];

    // boot
    public function boot(): void
    {
        $this->headers = [['key' => 'tiitvt_reg_no', 'label' => 'Reg No', 'class' => 'w-32'], ['key' => 'full_name', 'label' => 'Student Name', 'class' => 'w-48'], ['key' => 'mobile', 'label' => 'Mobile', 'class' => 'w-32'], ['key' => 'center_name', 'label' => 'Center', 'class' => 'w-40'], ['key' => 'course_name', 'label' => 'Course', 'class' => 'w-40']];
    }

    public function rendering(View $view): void
    {
        $view->students = Student::with(['center', 'course'])
            ->orderBy(...array_values($this->sortBy))
            ->whereAny(['tiitvt_reg_no', 'first_name', 'fathers_name', 'mobile', 'email', 'telephone_no'], 'like', "%$this->search%")
            ->orWhereHas('center', function ($query) {
                $query->where('name', 'like', "%$this->search%");
            })
            ->orWhereHas('course', function ($query) {
                $query->where('name', 'like', "%$this->search%");
            })
            ->paginate(20);
        $view->title('All Students');
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
            <x-button label="Add Student" icon="o-plus" class="btn-primary inline-flex" responsive
                link="{{ route('admin.student.create') }}" />
        </div>
    </div>
    <hr class="mb-5">
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
        @scope('cell_center_name', $student)
            @if ($student->center)
                <span class="text-sm font-medium">{{ $student->center->name }}</span>
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
        @scope('actions', $student)
            <div class="flex gap-1">
                <x-button icon="o-eye" link="{{ route('admin.student.show', $student->id) }}" class="btn-xs btn-ghost"
                    title="View Details" />
                <x-button icon="o-pencil" link="{{ route('admin.student.edit', $student->id) }}" class="btn-xs btn-ghost"
                    title="Edit Student" />
            </div>
        @endscope
        <x-slot:empty>
            <x-empty icon="o-academic-cap" message="No students found" />
        </x-slot>
    </x-table>
</div>
