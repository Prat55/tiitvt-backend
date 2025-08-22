<?php

use App\Models\Exam;
use Mary\Traits\Toast;
use Illuminate\View\View;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Livewire\Attributes\{Title, Url};

new class extends Component {
    use Toast, WithPagination;

    #[Title('All Exams')]
    public $headers;

    #[Url]
    public string $search = '';

    public $sortBy = ['column' => 'date', 'direction' => 'desc'];

    public function boot(): void
    {
        $this->headers = [['key' => 'id', 'label' => '#', 'class' => 'w-16'], ['key' => 'exam_id', 'label' => 'Exam ID', 'class' => 'w-32'], ['key' => 'course.name', 'label' => 'Course', 'class' => 'w-48'], ['key' => 'student.name', 'label' => 'Student', 'class' => 'w-40'], ['key' => 'date', 'label' => 'Date', 'class' => 'w-32'], ['key' => 'time', 'label' => 'Time', 'class' => 'w-32'], ['key' => 'duration', 'label' => 'Duration', 'class' => 'w-24'], ['key' => 'status', 'label' => 'Status', 'class' => 'w-32'], ['key' => 'actions', 'label' => 'Actions', 'class' => 'w-24']];
    }

    public function rendering(View $view): void
    {
        $view->exams = Exam::with(['course', 'student'])
            ->orderBy(...array_values($this->sortBy))
            ->search($this->search)
            ->paginate(20);
    }

    public function delete(Exam $exam): void
    {
        try {
            $exam->delete();
            $this->success('Exam deleted successfully.');
        } catch (\Exception $e) {
            $this->error('Failed to delete exam. It may have associated records.');
        }
    }
};
?>

<div>
    <div class="flex justify-between items-start lg:items-center flex-col lg:flex-row mt-3 mb-5 gap-2">
        <div>
            <h1 class="text-2xl font-bold">
                All Exams
            </h1>
            <div class="breadcrumbs text-sm">
                <ul class="flex">
                    <li>
                        <a href="{{ route('admin.index') }}" wire:navigate>
                            Dashboard
                        </a>
                    </li>
                    <li>
                        All Exams
                    </li>
                </ul>
            </div>
        </div>

        <div class="flex gap-3">
            <x-input placeholder="Search by course name or student..." icon="o-magnifying-glass"
                wire:model.live.debounce="search" />
            <x-button label="Schedule Exam" icon="o-calendar" class="btn-primary inline-flex" responsive
                link="{{ route('admin.exam.schedule') }}" />
        </div>
    </div>
    <hr class="mb-5">

    <x-card shadow>
        <x-table :headers="$headers" :rows="$exams" with-pagination :sort-by="$sortBy">
            @scope('cell_exam_id', $exam)
                <div class="flex items-center gap-2">
                    <span class="font-mono text-sm bg-base-200 px-2 py-1 rounded">{{ $exam->exam_id }}</span>
                    <x-button icon="o-clipboard" class="btn-xs btn-ghost" title="Copy Exam ID"
                        wire:click="$dispatch('copyToClipboard', { text: '{{ $exam->exam_id }}' })" />
                </div>
            @endscope

            @scope('cell_course', $exam)
                <div class="flex items-center gap-2">
                    @if ($exam->course)
                        <span class="font-medium">{{ $exam->course->name }}</span>
                    @else
                        <span class="text-gray-500 text-sm">No Course</span>
                    @endif
                </div>
            @endscope

            @scope('cell_student', $exam)
                <div class="flex items-center gap-2">
                    @if ($exam->student)
                        <span class="font-medium">{{ $exam->student->full_name }}</span>
                        <span class="text-xs text-gray-500">{{ $exam->student->tiitvt_reg_no }}</span>
                    @else
                        <span class="text-gray-500 text-sm">No Student</span>
                    @endif
                </div>
            @endscope

            @scope('cell_date', $exam)
                <span class="text-sm">{{ $exam->date ? $exam->date->format('M d, Y') : 'N/A' }}</span>
            @endscope

            @scope('cell_time', $exam)
                <div class="text-sm">
                    @if ($exam->start_time && $exam->end_time)
                        {{ \Carbon\Carbon::parse($exam->start_time)->format('H:i') }} -
                        {{ \Carbon\Carbon::parse($exam->end_time)->format('H:i') }}
                    @else
                        <span class="text-gray-500">N/A</span>
                    @endif
                </div>
            @endscope

            @scope('cell_duration', $exam)
                <span class="text-sm">{{ $exam->duration ? $exam->duration . ' min' : 'N/A' }}</span>
            @endscope

            @scope('cell_status', $exam)
                @if ($exam->status)
                    <span class="{{ $exam->status->badge() }}">
                        {{ $exam->status->label() }}
                    </span>
                @else
                    <span class="badge badge-ghost">Unknown</span>
                @endif
            @endscope

            @scope('actions', $exam)
                <div class="flex gap-1">
                    <x-button icon="o-eye" link="{{ route('admin.exam.show', $exam->id) }}" class="btn-xs btn-ghost"
                        title="View Details" />
                    <x-button icon="o-pencil" link="{{ route('admin.exam.edit', $exam->id) }}" class="btn-xs btn-ghost"
                        title="Edit Exam" />
                    <x-button icon="o-trash" class="btn-xs btn-ghost text-error" title="Delete Exam"
                        wire:click="delete({{ $exam->id }})" />
                </div>
            @endscope

            <x-slot:empty>
                <x-empty icon="o-no-symbol" message="No exams found" />
            </x-slot>
        </x-table>
    </x-card>
</div>
