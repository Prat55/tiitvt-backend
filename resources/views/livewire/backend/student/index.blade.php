<?php

use Mary\Traits\Toast;
use App\Models\Student;
use App\Enums\RolesEnum;
use Illuminate\View\View;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Url;
use Livewire\Attributes\Title;
use Livewire\Attributes\{Layout};
use Illuminate\Support\Facades\Storage;

new class extends Component {
    use WithPagination, Toast;
    #[Title('All Students')]
    public $headers;
    #[Url]
    public string $search = '';

    public $sortBy = ['column' => 'first_name', 'direction' => 'asc'];

    // boot
    public function boot(): void
    {
        $this->headers = [['key' => 'tiitvt_reg_no', 'label' => 'Reg No', 'class' => 'w-32'], ['key' => 'full_name', 'label' => 'Student Name', 'class' => 'w-48'], ['key' => 'mobile', 'label' => 'Mobile', 'class' => 'w-32']];

        // Only show center column to admin users
        if (hasAuthRole(RolesEnum::Admin->value)) {
            $this->headers[] = ['key' => 'center_name', 'label' => 'Center', 'class' => 'w-40', 'sortable' => false];
        }

        $this->headers[] = ['key' => 'course_name', 'label' => 'Course', 'class' => 'w-40', 'sortable' => false];
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
        $query = Student::with(['center', 'course']);

        if (hasAuthRole(RolesEnum::Center->value)) {
            $query->where('center_id', auth()->user()->center->id);
        }

        $view->students = $query->orderBy(...array_values($this->sortBy))->search($this->search)->paginate(20);

        // dd($view->students);

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
                link="{{ route('admin.student.create') }}" tooltip-left="Add Student" />
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
</div>
