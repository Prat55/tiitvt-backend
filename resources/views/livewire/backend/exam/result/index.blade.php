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

    public $sortBy = ['column' => 'exam_date', 'direction' => 'desc'];
    public $perPage = 20;
    public $showFilters = false;

    // Modal properties
    public $showStudentsModal = false;
    public $selectedExam = null;
    public $examStudents = [];

    public function boot(): void
    {
        $this->headers = [['key' => 'exam_info', 'label' => 'Exam Information', 'class' => 'w-64', 'sortable' => false], ['key' => 'total_students', 'label' => 'Total Students', 'class' => 'w-32', 'sortable' => false], ['key' => 'passed_count', 'label' => 'Passed', 'class' => 'w-24', 'sortable' => false], ['key' => 'failed_count', 'label' => 'Failed', 'class' => 'w-24', 'sortable' => false], ['key' => 'pending_count', 'label' => 'Pending', 'class' => 'w-24', 'sortable' => false], ['key' => 'exam_date', 'label' => 'Exam Date', 'class' => 'w-32'], ['key' => 'actions', 'label' => 'Actions', 'class' => 'w-24', 'sortable' => false]];
    }

    public function rendering(View $view): void
    {
        $view->exams = Exam::with(['course'])->get();
        $view->categories = Category::all();

        // Get grouped exam results
        $examResultsQuery = ExamResult::with(['student', 'exam.course', 'category'])
            ->when($this->search, function ($query) {
                $query->whereHas('student', function ($q) {
                    $q->where('first_name', 'like', '%' . $this->search . '%')
                        ->orWhere('surname', 'like', '%' . $this->search . '%')
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
            ->when(getUserCenterId() !== null, function ($query) {
                // If user is a center user, filter by their center's students
                $query->whereHas('student', function ($q) {
                    $q->where('center_id', getUserCenterId());
                });
            });

        // Group by exam_id and get aggregated data
        $groupedResults = $examResultsQuery
            ->get()
            ->groupBy('exam_id')
            ->map(function ($results, $examId) {
                $firstResult = $results->first();
                $exam = $firstResult->exam;

                // Group by student_id to get unique students
                $uniqueStudents = $results->groupBy('student_id');

                // Calculate overall scores for each student
                $studentScores = $uniqueStudents->map(function ($studentResults) {
                    return [
                        'total_score' => $studentResults->sum('score'),
                        'avg_percentage' => $studentResults->avg('percentage'),
                        'total_questions' => $studentResults->sum('total_questions'),
                        'answered_questions' => $studentResults->sum('answered_questions'),
                        'categories_count' => $studentResults->count(),
                        'results' => $studentResults,
                    ];
                });

                return [
                    'exam_id' => $examId,
                    'exam' => $exam,
                    'course' => $exam->course,
                    'exam_date' => $exam->date,
                    'exam_time' => $exam->start_time,
                    'total_students' => $uniqueStudents->count(), // Unique students count
                    'total_results' => $results->count(), // Total results count
                    'passed_count' => $results->where('result', 'passed')->count(),
                    'failed_count' => $results->where('result', 'failed')->count(),
                    'pending_count' => $results->whereNull('result')->count(),
                    'student_scores' => $studentScores,
                    'avg_percentage' => $results->whereNotNull('percentage')->avg('percentage'),
                    'avg_score' => $results->whereNotNull('score')->avg('score'),
                ];
            })
            ->values();

        // Apply sorting
        if ($this->sortBy['column'] === 'exam_date') {
            $groupedResults = $groupedResults->sortBy('exam_date', SORT_REGULAR, $this->sortBy['direction'] === 'desc');
        } elseif ($this->sortBy['column'] === 'total_students') {
            $groupedResults = $groupedResults->sortBy('total_students', SORT_REGULAR, $this->sortBy['direction'] === 'desc');
        }

        // Convert to array for pagination
        $groupedResultsArray = $groupedResults->values()->all();

        // Calculate statistics from full dataset (before pagination)
        $totalExams = count($groupedResultsArray);
        $totalStudents = collect($groupedResultsArray)->sum('total_students');
        $totalPassed = collect($groupedResultsArray)->sum('passed_count');
        $totalFailed = collect($groupedResultsArray)->sum('failed_count');

        // Get current page from Livewire pagination
        $currentPage = $this->getPage();
        $perPage = $this->perPage;
        $offset = ($currentPage - 1) * $perPage;
        $paginatedResults = array_slice($groupedResultsArray, $offset, $perPage);

        // Create a paginator instance compatible with Livewire using constructor
        $paginator = new \Illuminate\Pagination\LengthAwarePaginator($paginatedResults, count($groupedResultsArray), $perPage, $currentPage, [
            'path' => request()->url(),
            'pageName' => 'page',
        ]);
        $paginator->withQueryString();

        $view->examResults = $paginator;
        $view->totalExams = $totalExams;
        $view->totalStudents = $totalStudents;
        $view->totalPassed = $totalPassed;
        $view->totalFailed = $totalFailed;
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

    public function showStudentsForExam($examId): void
    {
        $this->selectedExam = Exam::with(['course', 'center'])->find($examId);

        if (!$this->selectedExam) {
            $this->error('Exam not found.');
            return;
        }

        // Get all exam results for this exam
        $examResults = ExamResult::with(['student', 'category'])
            ->where('exam_id', $examId)
            ->when(getUserCenterId() !== null, function ($query) {
                // If user is a center user, filter by their center's students
                $query->whereHas('student', function ($q) {
                    $q->where('center_id', getUserCenterId());
                });
            })
            ->get();

        // Group by student ID and calculate overall scores
        $this->examStudents = $examResults
            ->groupBy('student_id')
            ->map(function ($studentResults, $studentId) {
                $student = $studentResults->first()->student;

                // Calculate overall scores
                $totalScore = $studentResults->sum('score');
                $avgPercentage = $studentResults->avg('percentage');
                $totalQuestions = $studentResults->sum('total_questions');
                $answeredQuestions = $studentResults->sum('answered_questions');
                $categoriesCount = $studentResults->count();

                // Determine overall result - failed if ANY category is failed
                $hasAnyFailed = $studentResults->some(function ($result) {
                    return $result->result === 'failed';
                });

                if ($hasAnyFailed) {
                    $overallResult = 'failed';
                } else {
                    // Check if all are passed
                    $allPassed = $studentResults->every(function ($result) {
                        return $result->result === 'passed';
                    });
                    $overallResult = $allPassed ? 'passed' : 'pending';
                }

                return [
                    'student' => $student,
                    'total_score' => $totalScore,
                    'avg_percentage' => $avgPercentage,
                    'total_questions' => $totalQuestions,
                    'answered_questions' => $answeredQuestions,
                    'categories_count' => $categoriesCount,
                    'overall_result' => $overallResult,
                    'submitted_at' => $studentResults->first()->submitted_at,
                ];
            })
            ->values();

        $this->showStudentsModal = true;
    }

    public function closeStudentsModal(): void
    {
        $this->showStudentsModal = false;
        $this->selectedExam = null;
        $this->examStudents = [];
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
                <x-icon name="o-academic-cap" class="w-8 h-8" />
            </div>
            <div class="stat-title">Total Exams</div>
            <div class="stat-value text-primary">{{ $totalExams }}</div>
        </div>

        <div class="stat bg-base-100 shadow-sm rounded-lg">
            <div class="stat-figure text-info">
                <x-icon name="o-users" class="w-8 h-8" />
            </div>
            <div class="stat-title">Total Students</div>
            <div class="stat-value text-info">
                {{ $totalStudents }}
            </div>
        </div>

        <div class="stat bg-base-100 shadow-sm rounded-lg">
            <div class="stat-figure text-success">
                <x-icon name="o-check-circle" class="w-8 h-8" />
            </div>
            <div class="stat-title">Passed</div>
            <div class="stat-value text-success">
                {{ $totalPassed }}
            </div>
        </div>

        <div class="stat bg-base-100 shadow-sm rounded-lg">
            <div class="stat-figure text-error">
                <x-icon name="o-x-circle" class="w-8 h-8" />
            </div>
            <div class="stat-title">Failed</div>
            <div class="stat-value text-error">
                {{ $totalFailed }}
            </div>
        </div>
    </div>

    {{-- Results Table --}}
    <x-card shadow>
        <x-table :headers="$headers" :rows="$examResults" with-pagination :sort-by="$sortBy" per-page="perPage"
            :per-page-values="[20, 50, 100]">
            {{-- Exam Information Column --}}
            @scope('cell_exam_info', $examGroup)
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-primary/10 rounded-full flex items-center justify-center">
                        <x-icon name="o-academic-cap" class="w-6 h-6 text-primary" />
                    </div>
                    <div>
                        <div class="font-medium text-lg">{{ $examGroup['course']->name }}</div>
                        <div class="text-sm text-gray-500">Exam ID: {{ $examGroup['exam']->exam_id }}</div>
                        <div class="text-sm text-gray-500">
                            Duration: {{ $examGroup['exam']->duration }} minutes
                        </div>
                        @if ($examGroup['exam']->center)
                            <div class="text-sm text-gray-500">
                                Center: {{ $examGroup['exam']->center->name }}
                            </div>
                        @endif
                    </div>
                </div>
            @endscope

            {{-- Total Students Column --}}
            @scope('cell_total_students', $examGroup)
                <div class="flex items-center gap-2">
                    <x-icon name="o-users" class="w-5 h-5 text-blue-500" />
                    <span class="font-bold text-lg">{{ $examGroup['total_students'] }}</span>
                </div>
            @endscope

            {{-- Passed Count Column --}}
            @scope('cell_passed_count', $examGroup)
                <div class="flex items-center gap-2">
                    <x-icon name="o-check-circle" class="w-5 h-5 text-green-500" />
                    <span class="font-bold text-lg text-green-600">{{ $examGroup['passed_count'] }}</span>
                </div>
            @endscope

            {{-- Failed Count Column --}}
            @scope('cell_failed_count', $examGroup)
                <div class="flex items-center gap-2">
                    <x-icon name="o-x-circle" class="w-5 h-5 text-red-500" />
                    <span class="font-bold text-lg text-red-600">{{ $examGroup['failed_count'] }}</span>
                </div>
            @endscope

            {{-- Pending Count Column --}}
            @scope('cell_pending_count', $examGroup)
                <div class="flex items-center gap-2">
                    <x-icon name="o-clock" class="w-5 h-5 text-yellow-500" />
                    <span class="font-bold text-lg text-yellow-600">{{ $examGroup['pending_count'] }}</span>
                </div>
            @endscope

            {{-- Exam Date Column --}}
            @scope('cell_exam_date', $examGroup)
                <div class="flex flex-col">
                    <span class="font-medium text-gray-900 dark:text-white">
                        {{ $examGroup['exam_date']?->format('M d, Y') ?? 'N/A' }}
                    </span>
                    @if ($examGroup['exam_time'])
                        <span class="text-xs text-gray-500">
                            {{ \Carbon\Carbon::parse($examGroup['exam_time'])->format('g:i A') }}
                        </span>
                    @endif
                </div>
            @endscope

            {{-- Actions Column --}}
            @scope('actions', $examGroup)
                <div class="flex items-center gap-2">
                    <x-button icon="o-eye" class="btn-xs btn-primary" tooltip="View Students"
                        wire:click="showStudentsForExam({{ $examGroup['exam_id'] }})" />

                    @if ($examGroup['avg_percentage'])
                        <div class="text-xs text-gray-500">
                            Avg: {{ number_format($examGroup['avg_percentage'], 1) }}%
                        </div>
                    @endif
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

    {{-- Students Modal --}}
    @if ($showStudentsModal && $selectedExam)
        <x-modal wire:model="showStudentsModal" class="backdrop-blur" box-class="max-w-6xl">
            <div class="p-6"></div>
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white">
                        Students Results - {{ $selectedExam->course->name }}
                    </h3>
                    <p class="text-gray-600 dark:text-gray-400">
                        Exam ID: {{ $selectedExam->exam_id }} |
                        Date: {{ $selectedExam->date?->format('M d, Y') }} |
                        Duration: {{ $selectedExam->duration }} minutes
                    </p>
                </div>
            </div>

            {{-- Students Table --}}
            <div class="overflow-x-auto">
                <table class="table table-zebra w-full">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Categories</th>
                            <th>Total Score</th>
                            <th>Avg Percentage</th>
                            <th>Overall Result</th>
                            <th>Submitted</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($examStudents as $studentData)
                            @php
                                $student = $studentData['student'];
                            @endphp
                            <tr>
                                <td>
                                    <div class="flex items-center gap-3">
                                        <x-avatar placeholder="{{ $student->getInitials() }}"
                                            title="{{ $student->first_name }}{{ $student->fathers_name ? ' ' . $student->fathers_name : '' }}{{ $student->surname ? ' ' . $student->surname : '' }}"
                                            subtitle="{{ $student->email }}" class="!w-10" />
                                    </div>
                                </td>
                                <td>
                                    <x-badge value="{{ $studentData['categories_count'] }} Categories" icon="o-tag"
                                        class="badge-info badge-sm" />
                                </td>
                                <td>
                                    <div class="flex items-center gap-2">
                                        <x-icon name="o-star" class="w-4 h-4 text-yellow-500" />
                                        <span
                                            class="font-mono font-bold">{{ $studentData['total_score'] ?? 'N/A' }}</span>
                                    </div>
                                </td>
                                <td>
                                    @if ($studentData['avg_percentage'])
                                        <div class="radial-progress"
                                            style="--value:{{ $studentData['avg_percentage'] }}; --size:2rem; --thickness:3px;">
                                            <span
                                                class="text-xs font-medium">{{ number_format($studentData['avg_percentage'], 1) }}%</span>
                                        </div>
                                    @else
                                        <span class="text-gray-500">N/A</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($studentData['overall_result'] === 'passed')
                                        <span class="badge badge-success badge-sm">
                                            <x-icon name="o-check-circle" class="w-3 h-3 mr-1" />
                                            Passed
                                        </span>
                                    @elseif ($studentData['overall_result'] === 'failed')
                                        <span class="badge badge-error badge-sm">
                                            <x-icon name="o-x-circle" class="w-3 h-3 mr-1" />
                                            Failed
                                        </span>
                                    @else
                                        <span class="badge badge-info badge-sm">
                                            <x-icon name="o-clock" class="w-3 h-3 mr-1" />
                                            Pending
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    @if ($studentData['submitted_at'])
                                        <div class="flex flex-col">
                                            <span class="font-medium text-sm">
                                                {{ $studentData['submitted_at']->format('M d, Y') }}
                                            </span>
                                            <span class="text-xs text-gray-500">
                                                {{ $studentData['submitted_at']->format('g:i A') }}
                                            </span>
                                        </div>
                                    @else
                                        <span class="text-gray-500">Not submitted</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="flex items-center gap-1">
                                        <x-button icon="o-eye"
                                            link="{{ route('admin.exam.result.show', [$selectedExam->exam_id, encodeTiitvtRegNo($student->tiitvt_reg_no)]) }}"
                                            class="btn-xs btn-ghost hover:btn-primary transition-colors"
                                            tooltip="View Details" />
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-8 text-gray-500">
                                    No students found for this exam.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Summary Stats --}}
            <div class="mt-6 grid grid-cols-4 gap-4">
                <div class="stat bg-base-200 rounded-lg">
                    <div class="stat-title">Total Students</div>
                    <div class="stat-value text-primary">{{ $examStudents->count() }}</div>
                </div>

                <div class="stat bg-base-200 rounded-lg">
                    <div class="stat-title">Passed</div>
                    <div class="stat-value text-success">
                        {{ $examStudents->where('overall_result', 'passed')->count() }}
                    </div>
                </div>

                <div class="stat bg-base-200 rounded-lg">
                    <div class="stat-title">Failed</div>
                    <div class="stat-value text-error">
                        {{ $examStudents->where('overall_result', 'failed')->count() }}
                    </div>
                </div>

                <div class="stat bg-base-200 rounded-lg">
                    <div class="stat-title">Pending</div>
                    <div class="stat-value text-info">
                        {{ $examStudents->where('overall_result', 'pending')->count() }}
                    </div>
                </div>
            </div>
        </x-modal>
    @endif
</div>
