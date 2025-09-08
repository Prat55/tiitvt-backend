<?php

use App\Models\Exam;
use App\Enums\ExamStatusEnum;
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

    #[Url]
    public string $statusFilter = '';

    #[Url]
    public string $dateFilter = '';

    public $sortBy = ['column' => 'date', 'direction' => 'desc'];

    public $perPage = 20;

    public $showFilters = false;

    public function boot(): void
    {
        $this->headers = [['key' => 'id', 'label' => '#', 'class' => 'w-16'], ['key' => 'course.name', 'label' => 'Course', 'class' => 'w-48'], ['key' => 'enrolled_students_count', 'label' => 'Enrolled', 'class' => 'w-24'], ['key' => 'completed_students_count', 'label' => 'Completed', 'class' => 'w-24'], ['key' => 'date', 'label' => 'Date', 'class' => 'w-32'], ['key' => 'time', 'label' => 'Time', 'class' => 'w-32'], ['key' => 'duration', 'label' => 'Duration', 'class' => 'w-24'], ['key' => 'status', 'label' => 'Status', 'class' => 'w-32']];
    }

    public function rendering(View $view): void
    {
        $view->exams = Exam::with(['course'])
            ->withStudentCounts()
            ->when($this->search, fn($query) => $query->search($this->search))
            ->when($this->statusFilter, fn($query) => $query->byStatus(ExamStatusEnum::from($this->statusFilter)))
            ->when($this->dateFilter, function ($query) {
                if ($this->dateFilter === 'today') {
                    $query->where('date', today());
                } elseif ($this->dateFilter === 'upcoming') {
                    $query->where('date', '>', today());
                } elseif ($this->dateFilter === 'past') {
                    $query->where('date', '<', today());
                }
            })
            ->orderBy(...array_values($this->sortBy))
            ->paginate($this->perPage);
    }

    public function delete(Exam $exam): void
    {
        try {
            // Check if exam has students or results
            if ($exam->examStudents()->exists() || $exam->examResults()->exists()) {
                $this->error('Cannot delete exam. It has enrolled students or results.');
                return;
            }

            $exam->delete();
            $this->success('Exam deleted successfully.');
        } catch (\Exception $e) {
            $this->error('Failed to delete exam: ' . $e->getMessage());
        }
    }

    public function toggleFilters(): void
    {
        $this->showFilters = !$this->showFilters;
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->statusFilter = '';
        $this->dateFilter = '';
        $this->resetPage();
    }

    public function getStatusOptions(): array
    {
        return [
            '' => 'All Statuses',
            ExamStatusEnum::SCHEDULED->value => ExamStatusEnum::SCHEDULED->label(),
            ExamStatusEnum::COMPLETED->value => ExamStatusEnum::COMPLETED->label(),
            ExamStatusEnum::CANCELLED->value => ExamStatusEnum::CANCELLED->label(),
        ];
    }

    public function getDateFilterOptions(): array
    {
        return [
            '' => 'All Dates',
            'today' => 'Today',
            'upcoming' => 'Upcoming',
            'past' => 'Past',
        ];
    }

    public function getPerPageOptions(): array
    {
        return [10, 20, 50, 100];
    }
};
?>

<div>
    {{-- Header Section --}}
    <div class="flex justify-between items-start lg:items-center flex-col lg:flex-row mt-3 mb-5 gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                All Exams
            </h1>
            <div class="breadcrumbs text-sm text-gray-600 dark:text-gray-400 mt-1">
                <ul class="flex items-center space-x-2">
                    <li>
                        <a href="{{ route('admin.index') }}" wire:navigate class="hover:text-primary transition-colors">
                            Dashboard
                        </a>
                    </li>
                    <li class="font-medium">All Exams</li>
                </ul>
            </div>
        </div>

        <div class="flex flex-col sm:flex-row gap-3">
            <x-button label="Schedule Exam" icon="o-calendar" class="btn-primary btn-sm"
                link="{{ route('admin.exam.schedule') }}" tooltip-left="Schedule Exam" responsive />
        </div>
    </div>
    <hr class="mb-6">

    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <x-card class="bg-gradient-to-r from-blue-500 to-blue-600 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-blue-100 text-sm font-medium">Total Exams</p>
                    <p class="text-2xl font-bold">{{ $exams->total() }}</p>
                </div>
                <div class="text-blue-100">
                    <x-icon name="o-academic-cap" class="w-8 h-8" />
                </div>
            </div>
        </x-card>

        <x-card class="bg-gradient-to-r from-green-500 to-green-600 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-green-100 text-sm font-medium">Scheduled Exams</p>
                    <p class="text-2xl font-bold">{{ $exams->where('status', ExamStatusEnum::SCHEDULED)->count() }}</p>
                </div>
                <div class="text-green-100">
                    <x-icon name="o-clock" class="w-8 h-8" />
                </div>
            </div>
        </x-card>

        <x-card class="bg-gradient-to-r from-purple-500 to-purple-600 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-purple-100 text-sm font-medium">Total Students</p>
                    <p class="text-2xl font-bold">{{ $exams->sum('enrolled_students_count') }}</p>
                </div>
                <div class="text-purple-100">
                    <x-icon name="o-users" class="w-8 h-8" />
                </div>
            </div>
        </x-card>

        <x-card class="bg-gradient-to-r from-orange-500 to-orange-600 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-orange-100 text-sm font-medium">Completed</p>
                    <p class="text-2xl font-bold">{{ $exams->where('status', ExamStatusEnum::COMPLETED)->count() }}</p>
                </div>
                <div class="text-orange-100">
                    <x-icon name="o-check-circle" class="w-8 h-8" />
                </div>
            </div>
        </x-card>
    </div>

    {{-- Main Table --}}
    <x-card shadow>
        <x-table :headers="$headers" :rows="$exams" with-pagination :sort-by="$sortBy">
            {{-- Course Column --}}
            @scope('cell_course', $exam)
                <div class="flex items-center gap-3">
                    @if ($exam->course)
                        <div class="w-8 h-8 bg-primary/10 rounded-full flex items-center justify-center">
                            <x-icon name="o-academic-cap" class="w-4 h-4 text-primary" />
                        </div>
                        <div>
                            <span class="font-medium text-gray-900 dark:text-white">{{ $exam->course->name }}</span>
                            @if ($exam->course->is_active)
                                <span class="badge badge-success badge-xs ml-2">Active</span>
                            @endif
                        </div>
                    @else
                        <div class="flex items-center gap-2 text-gray-500">
                            <x-icon name="o-exclamation-triangle" class="w-4 h-4" />
                            <span class="text-sm">No Course</span>
                        </div>
                    @endif
                </div>
            @endscope

            {{-- Enrolled Students Column --}}
            @scope('cell_enrolled_students_count', $exam)
                <div class="flex items-center gap-2">
                    <x-badge :label="$exam->enrolled_students_count ?? 0" icon="o-users" class="badge-soft badge-primary" />
                </div>
            @endscope

            {{-- Completed Students Column --}}
            @scope('cell_completed_students_count', $exam)
                <div class="flex items-center gap-2">
                    @if (($exam->completed_students_count ?? 0) > 0)
                        <x-badge :label="$exam->completed_students_count ?? 0" icon="o-check-circle" class="badge-soft badge-success" />
                    @else
                        <span class="text-gray-500 text-sm">0</span>
                    @endif
                </div>
            @endscope

            {{-- Date Column --}}
            @scope('cell_date', $exam)
                <div class="flex flex-col">
                    <span class="font-medium text-gray-900 dark:text-white">
                        {{ $exam->date ? $exam->date->format('M d, Y') : 'N/A' }}
                    </span>
                    @if ($exam->date)
                        <span class="text-xs text-gray-500">
                            {{ $exam->date->diffForHumans() }}
                        </span>
                    @endif
                </div>
            @endscope

            {{-- Time Column --}}
            @scope('cell_time', $exam)
                <div class="flex flex-col">
                    @if ($exam->start_time && $exam->end_time)
                        <div class="flex items-center gap-1">
                            <x-icon name="o-clock" class="w-3 h-3 text-gray-500" />
                            <span class="text-sm font-medium">
                                {{ $exam->start_time->format('g:i A') }} - {{ $exam->end_time->format('g:i A') }}
                            </span>
                        </div>
                    @else
                        <span class="text-gray-500 text-sm">N/A</span>
                    @endif
                </div>
            @endscope

            {{-- Duration Column --}}
            @scope('cell_duration', $exam)
                <div class="flex items-center gap-2">
                    <x-icon name="o-clock" class="w-4 h-4 text-gray-500" />
                    <span class="text-sm font-medium">
                        {{ $exam->duration ? $exam->duration . ' min' : 'N/A' }}
                    </span>
                </div>
            @endscope

            {{-- Status Column --}}
            @scope('cell_status', $exam)
                <div class="flex items-center gap-2">
                    @if ($exam->status)
                        <span class="{{ $exam->status->badge() }} badge-sm">
                            {{ $exam->status->label() }}
                        </span>
                        @if ($exam->status === ExamStatusEnum::SCHEDULED)
                            <div class="w-2 h-2 bg-blue-500 rounded-full animate-pulse"></div>
                        @endif
                    @else
                        <span class="text-gray-500 text-sm">N/A</span>
                    @endif
                </div>
            @endscope

            {{-- Actions Column --}}
            @scope('actions', $exam)
                <div class="flex items-center gap-1">
                    <x-button icon="o-eye" link="{{ route('admin.exam.show', $exam->id) }}"
                        class="btn-xs btn-ghost hover:btn-primary transition-colors" tooltip="View Details" />
                    <x-button icon="o-chart-bar" link="{{ route('admin.exam.results', $exam->id) }}"
                        class="btn-xs btn-ghost hover:btn-info transition-colors" tooltip="View Results" />
                    <x-button icon="o-pencil" link="{{ route('admin.exam.edit', $exam->id) }}"
                        class="btn-xs btn-ghost hover:btn-warning transition-colors" tooltip="Edit Exam" />
                    <x-button icon="o-trash" class="btn-xs btn-ghost hover:btn-error transition-colors"
                        tooltip="Delete Exam" wire:click="delete({{ $exam->id }})" />
                </div>
            @endscope

            {{-- Empty State --}}
            <x-slot:empty>
                <div class="text-center py-12">
                    <x-icon name="o-academic-cap" class="w-16 h-16 text-gray-400 mx-auto mb-4" />
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No exams found</h3>
                    <p class="text-gray-500 dark:text-gray-400 mb-4">
                        @if ($search || $statusFilter || $dateFilter)
                            Try adjusting your filters or search terms.
                        @else
                            Get started by scheduling your first exam.
                        @endif
                    </p>
                    @if (!$search && !$statusFilter && !$dateFilter)
                        <x-button label="Schedule Exam" icon="o-calendar" class="btn-primary" tooltip="Schedule Exam"
                            link="{{ route('admin.exam.schedule') }}" responsive />
                    @endif
                </div>
            </x-slot>
        </x-table>
    </x-card>

    {{-- Quick Actions Footer --}}
    <div class="mt-8 text-center">
        <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
            <x-button label="Schedule New Exam" icon="o-calendar" class="btn-primary"
                link="{{ route('admin.exam.schedule') }}" />
            <x-button label="View All Results" icon="o-chart-bar" class="btn-outline"
                link="{{ route('admin.exam.results') }}" />
            <x-button label="Export Data" icon="o-arrow-down-tray" class="btn-outline"
                wire:click="$dispatch('exportExams')" />
        </div>
    </div>
</div>
