<?php

use App\Models\{Exam, ExamResult, Student, Category};
use App\Enums\{ExamResultStatusEnum, ExamStatusEnum};
use Mary\Traits\Toast;
use Illuminate\View\View;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Livewire\Attributes\{Title, Url};

new class extends Component {
    use Toast, WithPagination;

    #[Title('Exam Results')]
    public $headers;

    #[Url]
    public string $search = '';

    #[Url]
    public string $statusFilter = '';

    #[Url]
    public string $examFilter = '';

    #[Url]
    public string $categoryFilter = '';

    public $sortBy = ['column' => 'submitted_at', 'direction' => 'desc'];
    public $perPage = 20;
    public $showFilters = false;

    public function boot(): void
    {
        $this->headers = [['key' => 'student.name', 'label' => 'Student', 'class' => 'w-48', 'sortable' => false], ['key' => 'exam.course.name', 'label' => 'Course', 'class' => 'w-40', 'sortable' => false], ['key' => 'category.name', 'label' => 'Category', 'class' => 'w-32', 'sortable' => false], ['key' => 'score', 'label' => 'Score', 'class' => 'w-20'], ['key' => 'percentage', 'label' => 'Percentage', 'class' => 'w-24'], ['key' => 'result', 'label' => 'Result', 'class' => 'w-24'], ['key' => 'submitted_at', 'label' => 'Submitted', 'class' => 'w-32']];
    }

    public function rendering(View $view): void
    {
        $view->exams = Exam::with(['course'])->get();
        $view->categories = Category::all();

        $view->examResults = ExamResult::with(['student', 'exam.course', 'category'])
            ->when($this->search, function ($query) {
                $query->whereHas('student', function ($q) {
                    $q->where('first_name', 'like', '%' . $this->search . '%')
                        ->orWhere('last_name', 'like', '%' . $this->search . '%')
                        ->orWhere('email', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->statusFilter, function ($query) {
                if ($this->statusFilter === 'passed') {
                    $query->where('result', 'passed');
                } elseif ($this->statusFilter === 'failed') {
                    $query->where('result', 'failed');
                } elseif ($this->statusFilter === 'pending') {
                    $query->whereNull('result');
                }
            })
            ->when($this->examFilter, function ($query) {
                $query->where('exam_id', $this->examFilter);
            })
            ->when($this->categoryFilter, function ($query) {
                $query->where('category_id', $this->categoryFilter);
            })
            ->orderBy(...array_values($this->sortBy))
            ->paginate($this->perPage);
    }

    public function toggleFilters(): void
    {
        $this->showFilters = !$this->showFilters;
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->statusFilter = '';
        $this->examFilter = '';
        $this->categoryFilter = '';
        $this->resetPage();
    }

    public function exportResults()
    {
        try {
            $results = ExamResult::with(['student', 'exam.course', 'category'])
                ->when($this->search, function ($query) {
                    $query->whereHas('student', function ($q) {
                        $q->where('first_name', 'like', '%' . $this->search . '%')
                            ->orWhere('last_name', 'like', '%' . $this->search . '%')
                            ->orWhere('email', 'like', '%' . $this->search . '%');
                    });
                })
                ->when($this->statusFilter, function ($query) {
                    if ($this->statusFilter === 'passed') {
                        $query->where('result', 'passed');
                    } elseif ($this->statusFilter === 'failed') {
                        $query->where('result', 'failed');
                    } elseif ($this->statusFilter === 'pending') {
                        $query->whereNull('result');
                    }
                })
                ->when($this->examFilter, function ($query) {
                    $query->where('exam_id', $this->examFilter);
                })
                ->when($this->categoryFilter, function ($query) {
                    $query->where('category_id', $this->categoryFilter);
                })
                ->get();

            $filename = 'exam_results_' . now()->format('Y-m-d_H-i-s') . '.csv';

            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ];

            $callback = function () use ($results) {
                $file = fopen('php://output', 'w');

                // CSV Headers
                fputcsv($file, ['Student Name', 'Email', 'Course', 'Category', 'Score', 'Percentage', 'Result', 'Total Questions', 'Answered Questions', 'Skipped Questions', 'Total Points', 'Points Earned', 'Time Taken (minutes)', 'Submitted At']);

                // CSV Data
                foreach ($results as $result) {
                    fputcsv($file, [$result->student->first_name . ' ' . $result->student->last_name, $result->student->email, $result->exam->course->name, $result->category->name, $result->score, $result->percentage, $result->result ?? 'Pending', $result->total_questions, $result->answered_questions, $result->skipped_questions, $result->total_points, $result->points_earned, $result->time_taken_minutes, $result->submitted_at?->format('Y-m-d H:i:s')]);
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        } catch (\Exception $e) {
            $this->error('Failed to export results: ' . $e->getMessage());
        }
    }
};
?>

<div>
    {{-- Header Section --}}
    <div class="flex justify-between items-start lg:items-center flex-col lg:flex-row mt-3 mb-5 gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                Exam Results
            </h1>
            <div class="breadcrumbs text-sm text-gray-600 dark:text-gray-400 mt-1">
                <ul class="flex items-center space-x-2">
                    <li>
                        <a href="{{ route('admin.index') }}" wire:navigate class="hover:text-primary transition-colors">
                            Dashboard
                        </a>
                    </li>
                    <li class="font-medium">Exam Results</li>
                </ul>
            </div>
        </div>

        <div class="flex flex-col sm:flex-row gap-3 items-center">
            <x-input wire:model.live.debounce.300ms="search" icon="o-magnifying-glass" placeholder="Search. . ." />
            <x-button tooltip="Export CSV" icon="o-arrow-down-tray" class="btn-success" wire:click="exportResults" />
        </div>
    </div>

    <hr class="mb-6">

    {{-- Statistics Summary --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="stat bg-base-100 shadow-sm rounded-lg">
            <div class="stat-figure text-primary">
                <x-icon name="o-users" class="w-8 h-8" />
            </div>
            <div class="stat-title">Total Results</div>
            <div class="stat-value text-primary">{{ $examResults->total() }}</div>
        </div>

        <div class="stat bg-base-100 shadow-sm rounded-lg">
            <div class="stat-figure text-success">
                <x-icon name="o-check-circle" class="w-8 h-8" />
            </div>
            <div class="stat-title">Passed</div>
            <div class="stat-value text-success">
                {{ $examResults->where('result', 'passed')->count() }}
            </div>
        </div>

        <div class="stat bg-base-100 shadow-sm rounded-lg">
            <div class="stat-figure text-error">
                <x-icon name="o-x-circle" class="w-8 h-8" />
            </div>
            <div class="stat-title">Failed</div>
            <div class="stat-value text-error">
                {{ $examResults->where('result', 'failed')->count() }}
            </div>
        </div>

        <div class="stat bg-base-100 shadow-sm rounded-lg">
            <div class="stat-figure text-warning">
                <x-icon name="o-clock" class="w-8 h-8" />
            </div>
            <div class="stat-title">Pending</div>
            <div class="stat-value text-warning">
                {{ $examResults->whereNull('result')->count() }}
            </div>
        </div>
    </div>

    {{-- Results Table --}}
    <x-card shadow>
        <x-table :headers="$headers" :rows="$examResults" with-pagination :sort-by="$sortBy">
            {{-- Student Column --}}
            @scope('cell_student.name', $result)
                <div class="flex items-center gap-3">
                    <x-avatar placeholder="{{ $result->student?->getInitials() ?? 'Unknown' }}"
                        title="{{ $result->student?->first_name ?? 'Unknown' }} {{ $result->student?->surname ?? 'Student' }}"
                        subtitle="{{ $result->student?->email ?? 'N/A' }}" class="!w-10" />
                </div>
            @endscope

            {{-- Course Column --}}
            @scope('cell_exam.course.name', $result)
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 bg-primary/10 rounded-full flex items-center justify-center">
                        <x-icon name="o-academic-cap" class="w-4 h-4 text-primary" />
                    </div>
                    <div>
                        <div class="font-medium">{{ $result->exam->course->name }}</div>
                        <div class="text-sm text-gray-500">ID: {{ $result->exam->exam_id }}</div>
                    </div>
                </div>
            @endscope

            {{-- Category Column --}}
            @scope('cell_category.name', $result)
                <div class="flex items-center gap-2">
                    <x-badge value="{{ $result->category->name }}" icon="o-tag" class="badge-soft badge-outline" />
                </div>
            @endscope

            {{-- Score Column --}}
            @scope('cell_score', $result)
                <div class="flex items-center gap-2">
                    <x-icon name="o-star" class="w-4 h-4 text-yellow-500" />
                    <span class="font-mono font-bold text-lg">{{ $result->score }}</span>
                </div>
            @endscope

            {{-- Percentage Column --}}
            @scope('cell_percentage', $result)
                <div class="flex items-center gap-2">
                    <div class="radial-progress"
                        style="--value:{{ $result->percentage }}; --size:2.5rem; --thickness:4px;">
                        <span class="text-sm font-medium">{{ number_format($result->percentage, 1) }}%</span>
                    </div>
                </div>
            @endscope

            {{-- Result Column --}}
            @scope('cell_result', $result)
                <div class="flex items-center gap-2">
                    @if ($result->result === 'passed')
                        <span class="badge badge-success badge-sm">
                            <x-icon name="o-check-circle" class="w-3 h-3 mr-1" />
                            Passed
                        </span>
                    @elseif ($result->result === 'failed')
                        <span class="badge badge-error badge-sm">
                            <x-icon name="o-x-circle" class="w-3 h-3 mr-1" />
                            Failed
                        </span>
                    @else
                        <span class="badge badge-warning badge-sm">
                            <x-icon name="o-clock" class="w-3 h-3 mr-1" />
                            Pending
                        </span>
                    @endif
                </div>
            @endscope

            {{-- Submitted At Column --}}
            @scope('cell_submitted_at', $result)
                <div class="flex flex-col">
                    <span class="font-medium text-gray-900 dark:text-white">
                        {{ $result->submitted_at?->format('M d, Y') ?? 'N/A' }}
                    </span>
                    @if ($result->submitted_at)
                        <span class="text-xs text-gray-500">
                            {{ $result->submitted_at->format('g:i A') }}
                        </span>
                    @endif
                </div>
            @endscope

            {{-- Actions Column --}}
            @scope('actions', $result)
                <div class="flex items-center gap-1">
                    <x-button icon="o-eye" link="{{ route('admin.exam.result.show', $result) }}"
                        class="btn-xs btn-ghost hover:btn-primary transition-colors" tooltip="View Details" />
                </div>
            @endscope

            {{-- Empty State --}}
            <x-slot:empty>
                <div class="text-center py-12">
                    <x-icon name="o-chart-bar" class="w-16 h-16 text-gray-400 mx-auto mb-4" />
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No exam results found</h3>
                    <p class="text-gray-500 dark:text-gray-400 mb-4">
                        @if ($search || $statusFilter || $examFilter || $categoryFilter)
                            Try adjusting your filters or search terms.
                        @else
                            No students have submitted exam results yet.
                        @endif
                    </p>
                    @if ($search || $statusFilter || $examFilter || $categoryFilter)
                        <x-button label="Clear Filters" class="btn-ghost btn-sm" wire:click="clearFilters" />
                    @endif
                </div>
            </x-slot>
        </x-table>
    </x-card>
</div>
