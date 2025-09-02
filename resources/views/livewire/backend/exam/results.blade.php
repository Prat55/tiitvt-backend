<?php

use App\Models\{Exam, ExamStudent, ExamResult, Student, Category};
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

    public $sortBy = ['column' => 'created_at', 'direction' => 'desc'];
    public $perPage = 20;
    public $showFilters = false;
    public $selectedExam = null;
    public $selectedStudent = null;
    public $showResultModal = false;
    public $showRescheduleModal = false;
    public $showNewExamModal = false;
    public $rescheduleData = [];
    public $newExamData = [];
    public $selectedStudents = [];
    public $selectAll = false;

    public function boot(): void
    {
        $this->headers = [['key' => 'select', 'label' => '', 'class' => 'w-8'], ['key' => 'student.name', 'label' => 'Student Name', 'class' => 'w-48'], ['key' => 'exam.exam_id', 'label' => 'Exam ID', 'class' => 'w-32'], ['key' => 'exam.course.name', 'label' => 'Course', 'class' => 'w-48'], ['key' => 'completion_status', 'label' => 'Status', 'class' => 'w-32'], ['key' => 'examResult.score', 'label' => 'Score', 'class' => 'w-24'], ['key' => 'examResult.grade', 'label' => 'Grade', 'class' => 'w-24'], ['key' => 'actions', 'label' => 'Actions', 'class' => 'w-48']];
    }

    public function rendering(View $view): void
    {
        $view->exams = Exam::with(['course'])->get();
        $view->categories = Category::all();

        $view->examStudents = ExamStudent::with(['student', 'exam.course', 'examResult'])
            ->when($this->search, function ($query) {
                $query->whereHas('student', function ($q) {
                    $q->search($this->search);
                });
            })
            ->when($this->statusFilter, function ($query) {
                if ($this->statusFilter === 'completed') {
                    $query->whereHas('examResult');
                } elseif ($this->statusFilter === 'not_started') {
                    $query->whereDoesntHave('examResult');
                } elseif ($this->statusFilter === 'failed') {
                    $query->whereHas('examResult', function ($q) {
                        $q->where('result_status', ExamResultStatusEnum::Failed->value);
                    });
                } elseif ($this->statusFilter === 'passed') {
                    $query->whereHas('examResult', function ($q) {
                        $q->where('result_status', ExamResultStatusEnum::Passed->value);
                    });
                }
            })
            ->when($this->examFilter, function ($query) {
                $query->where('exam_id', $this->examFilter);
            })
            ->orderBy(...array_values($this->sortBy))
            ->paginate($this->perPage);
    }

    public function viewResult(ExamStudent $examStudent): void
    {
        $this->selectedStudent = $examStudent;
        $this->showResultModal = true;
    }

    public function rescheduleExam(ExamStudent $examStudent): void
    {
        $this->selectedStudent = $examStudent;
        $this->rescheduleData = [
            'exam_id' => $examStudent->exam_id,
            'student_id' => $examStudent->student_id,
            'new_date' => now()->addDays(7)->format('Y-m-d'),
            'new_start_time' => '09:00',
            'new_end_time' => '12:00',
        ];
        $this->showRescheduleModal = true;
    }

    public function validateRescheduleData(): bool
    {
        if (empty($this->rescheduleData['new_date'])) {
            $this->error('Please select a date.');
            return false;
        }

        if (empty($this->rescheduleData['new_start_time'])) {
            $this->error('Please select a start time.');
            return false;
        }

        if (empty($this->rescheduleData['new_end_time'])) {
            $this->error('Please select an end time.');
            return false;
        }

        $startTime = strtotime($this->rescheduleData['new_start_time']);
        $endTime = strtotime($this->rescheduleData['new_end_time']);

        if ($startTime >= $endTime) {
            $this->error('End time must be after start time.');
            return false;
        }

        return true;
    }

    public function validateNewExamData(): bool
    {
        if (empty($this->newExamData['date'])) {
            $this->error('Please select a date.');
            return false;
        }

        if (empty($this->newExamData['start_time'])) {
            $this->error('Please select a start time.');
            return false;
        }

        if (empty($this->newExamData['end_time'])) {
            $this->error('Please select an end time.');
            return false;
        }

        $startTime = strtotime($this->newExamData['start_time']);
        $endTime = strtotime($this->newExamData['end_time']);

        if ($startTime >= $endTime) {
            $this->error('End time must be after start time.');
            return false;
        }

        return true;
    }

    public function createNewExam(ExamStudent $examStudent): void
    {
        $this->selectedStudent = $examStudent;
        $exam = $examStudent->exam;
        $this->newExamData = [
            'course_id' => $exam->course_id,
            'duration' => $exam->duration,
            'date' => now()->addDays(7)->format('Y-m-d'),
            'start_time' => '09:00',
            'end_time' => '12:00',
            'categories' => $exam->examCategories->pluck('category_id')->toArray(),
        ];
        $this->showNewExamModal = true;
    }

    public function confirmReschedule(): void
    {
        try {
            if (!$this->validateRescheduleData()) {
                return;
            }

            $examStudent = $this->selectedStudent;
            $exam = $examStudent->exam;

            // Update exam schedule
            $exam->update([
                'date' => $this->rescheduleData['new_date'],
                'start_time' => $this->rescheduleData['new_start_time'],
                'end_time' => $this->rescheduleData['new_end_time'],
            ]);

            // Generate new credentials for the student
            $examStudent->update([
                'exam_user_id' => ExamStudent::generateUniqueExamUserId(),
                'exam_password' => ExamStudent::generatePassword(),
            ]);

            // Remove old result if exists
            if ($examStudent->examResult) {
                $examStudent->examResult->delete();
            }

            $this->success('Exam rescheduled successfully for ' . $examStudent->student->name);
            $this->showRescheduleModal = false;
            $this->selectedStudent = null;
            $this->rescheduleData = [];
        } catch (\Exception $e) {
            $this->error('Failed to reschedule exam: ' . $e->getMessage());
        }
    }

    public function confirmNewExam(): void
    {
        try {
            if (!$this->validateNewExamData()) {
                return;
            }

            $oldExamStudent = $this->selectedStudent;
            $oldExam = $oldExamStudent->exam;

            // Create new exam
            $newExam = Exam::create([
                'course_id' => $this->newExamData['course_id'],
                'duration' => $this->newExamData['duration'],
                'date' => $this->newExamData['date'],
                'start_time' => $this->newExamData['start_time'],
                'end_time' => $this->newExamData['end_time'],
                'status' => ExamStatusEnum::SCHEDULED,
            ]);

            // Copy categories
            foreach ($this->newExamData['categories'] as $categoryId) {
                $newExam->examCategories()->create(['category_id' => $categoryId]);
            }

            // Create new exam student enrollment
            ExamStudent::create([
                'exam_id' => $newExam->id,
                'student_id' => $oldExamStudent->student_id,
                'exam_user_id' => ExamStudent::generateUniqueExamUserId(),
                'exam_password' => ExamStudent::generatePassword(),
            ]);

            $this->success('New exam created successfully for ' . $oldExamStudent->student->name);
            $this->showNewExamModal = false;
            $this->selectedStudent = null;
            $this->newExamData = [];
        } catch (\Exception $e) {
            $this->error('Failed to create new exam: ' . $e->getMessage());
        }
    }

    public function declareAllResults(): void
    {
        try {
            $pendingResults = ExamResult::where('result_status', ExamResultStatusEnum::NotDeclared->value)->get();

            if ($pendingResults->isEmpty()) {
                $this->info('No pending results to declare.');
                return;
            }

            $count = 0;
            foreach ($pendingResults as $result) {
                $result->update([
                    'result_status' => $result->score >= 40 ? ExamResultStatusEnum::Passed->value : ExamResultStatusEnum::Failed->value,
                    'declared_by' => auth()->id(),
                    'declared_at' => now(),
                ]);
                $count++;
            }

            $this->success("Successfully declared {$count} results!");
        } catch (\Exception $e) {
            $this->error('Failed to declare all results: ' . $e->getMessage());
        }
    }

    public function declareResult(ExamResult $result): void
    {
        try {
            $result->update([
                'result_status' => $result->score >= 40 ? ExamResultStatusEnum::Passed->value : ExamResultStatusEnum::Failed->value,
                'declared_by' => auth()->id(),
                'declared_at' => now(),
            ]);

            $this->success('Result declared successfully!');
        } catch (\Exception $e) {
            $this->error('Failed to declare result: ' . $e->getMessage());
        }
    }

    public function getStatusOptions(): array
    {
        return [
            '' => 'All Statuses',
            'completed' => 'Completed',
            'not_started' => 'Not Started',
            'failed' => 'Failed',
            'passed' => 'Passed',
        ];
    }

    public function getExamOptions(): array
    {
        $exams = Exam::with('course')->get();
        $options = ['' => 'All Exams'];
        foreach ($exams as $exam) {
            $options[$exam->id] = $exam->exam_id . ' - ' . $exam->course->name;
        }
        return $options;
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
        $this->resetPage();
    }

    public function updatedSelectAll($value): void
    {
        if ($value) {
            $this->selectedStudents = $this->examStudents->pluck('id')->toArray();
        } else {
            $this->selectedStudents = [];
        }
    }

    public function updatedSelectedStudents(): void
    {
        $this->selectAll = count($this->selectedStudents) === $this->examStudents->count();
    }

    public function bulkDeclareResults(): void
    {
        if (empty($this->selectedStudents)) {
            $this->error('Please select at least one student.');
            return;
        }

        try {
            $pendingResults = ExamResult::whereIn('exam_student_id', $this->selectedStudents)->where('result_status', ExamResultStatusEnum::NotDeclared->value)->get();

            foreach ($pendingResults as $result) {
                $result->update([
                    'result_status' => $result->score >= 40 ? ExamResultStatusEnum::Passed->value : ExamResultStatusEnum::Failed->value,
                    'declared_by' => auth()->id(),
                    'declared_at' => now(),
                ]);
            }

            $this->success('Bulk results declared successfully!');
            $this->selectedStudents = [];
            $this->selectAll = false;
        } catch (\Exception $e) {
            $this->error('Failed to declare bulk results: ' . $e->getMessage());
        }
    }

    public function exportResults()
    {
        try {
            $results = ExamStudent::with(['student', 'exam.course', 'examResult'])
                ->when($this->search, function ($query) {
                    $query->whereHas('student', function ($q) {
                        $q->where('name', 'like', '%' . $this->search . '%')->orWhere('email', 'like', '%' . $this->search . '%');
                    });
                })
                ->when($this->statusFilter, function ($query) {
                    if ($this->statusFilter === 'completed') {
                        $query->whereHas('examResult');
                    } elseif ($this->statusFilter === 'not_started') {
                        $query->whereDoesntHave('examResult');
                    } elseif ($this->statusFilter === 'failed') {
                        $query->whereHas('examResult', function ($q) {
                            $q->where('result_status', ExamResultStatusEnum::Failed->value);
                        });
                    } elseif ($this->statusFilter === 'passed') {
                        $query->whereHas('examResult', function ($q) {
                            $q->where('result_status', ExamResultStatusEnum::Passed->value);
                        });
                    }
                })
                ->when($this->examFilter, function ($query) {
                    $query->where('exam_id', $this->examFilter);
                })
                ->get();

            $filename = 'exam_results_' . now()->format('Y-m-d_H-i-s') . '.csv';

            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ];

            $callback = function () use ($results) {
                $file = fopen('php://output', 'w');

                // Add headers
                fputcsv($file, ['Student Name', 'Email', 'Exam ID', 'Course', 'Status', 'Score', 'Grade', 'Date']);

                // Add data
                foreach ($results as $result) {
                    fputcsv($file, [$result->student->name, $result->student->email, $result->exam->exam_id, $result->exam->course->name, $result->examResult ? $result->examResult->result_status : 'Not Started', $result->examResult ? $result->examResult->score . '%' : '-', $result->examResult ? $result->examResult->grade : '-', $result->examResult ? $result->examResult->created_at->format('Y-m-d H:i:s') : '-']);
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        } catch (\Exception $e) {
            $this->error('Failed to export results: ' . $e->getMessage());
        }
    }

    public function markAsAbsent(ExamStudent $examStudent): void
    {
        try {
            // Check if result already exists
            if ($examStudent->examResult) {
                $this->error('Student already has a result.');
                return;
            }

            // Create a result with 0 score and failed status
            ExamResult::create([
                'exam_id' => $examStudent->exam_id,
                'student_id' => $examStudent->student_id,
                'score' => 0,
                'result_status' => ExamResultStatusEnum::Failed->value,
                'declared_by' => auth()->id(),
                'declared_at' => now(),
                'data' => ['reason' => 'Absent'],
            ]);

            $this->success('Student marked as absent successfully.');
        } catch (\Exception $e) {
            $this->error('Failed to mark student as absent: ' . $e->getMessage());
        }
    }

    public function resetResult(ExamStudent $examStudent): void
    {
        try {
            if (!$examStudent->examResult) {
                $this->error('No result to reset.');
                return;
            }

            $examStudent->examResult->delete();
            $this->success('Result reset successfully. Student can retake the exam.');
        } catch (\Exception $e) {
            $this->error('Failed to reset result: ' . $e->getMessage());
        }
    }

    public function viewStudentHistory(ExamStudent $examStudent): void
    {
        try {
            $this->selectedStudent = $examStudent;
            $this->showResultModal = true;
        } catch (\Exception $e) {
            $this->error('Failed to load student history: ' . $e->getMessage());
        }
    }
}; ?>

<div>
    {{-- Header --}}
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold">Exam Results</h1>
            <p class="text-base-content/70">Manage and view all exam results</p>
        </div>
        <div class="flex gap-3">
            @if (!empty($selectedStudents))
                <x-button label="Declare Selected Results" icon="o-check-circle" class="btn-success"
                    wire:click="bulkDeclareResults" />
            @endif
            <x-button label="Declare All Results" icon="o-check-circle" class="btn-primary"
                wire:click="declareAllResults" />
            <x-button label="Export Results" icon="o-arrow-down-tray" class="btn-outline" wire:click="exportResults" />
            <x-button label="{{ $showFilters ? 'Hide Filters' : 'Show Filters' }}"
                icon="{{ $showFilters ? 'o-eye-slash' : 'o-funnel' }}" class="btn-outline" wire:click="toggleFilters" />
        </div>
    </div>

    {{-- Filters --}}
    @if ($showFilters)
        <div class="card bg-base-100 shadow-sm mb-6">
            <div class="card-body">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="label">
                            <span class="label-text">Search Students</span>
                        </label>
                        <input type="text" wire:model.live.debounce.300ms="search"
                            placeholder="Search by name or email..." class="input input-bordered w-full" />
                    </div>
                    <div>
                        <label class="label">
                            <span class="label-text">Status Filter</span>
                        </label>
                        <select wire:model.live="statusFilter" class="select select-bordered w-full">
                            @foreach ($this->getStatusOptions() as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="label">
                            <span class="label-text">Exam Filter</span>
                        </label>
                        <select wire:model.live="examFilter" class="select select-bordered w-full">
                            @foreach ($this->getExamOptions() as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="flex justify-end mt-4">
                    <x-button label="Clear" icon="o-x-mark" class="btn-outline btn-sm" wire:click="clearFilters" />
                </div>
            </div>
        </div>
    @endif

    {{-- Statistics Summary --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="stat bg-base-100 shadow-sm rounded-lg">
            <div class="stat-figure text-primary">
                <x-icon name="o-users" class="w-8 h-8" />
            </div>
            <div class="stat-title">Total Students</div>
            <div class="stat-value text-primary">{{ $examStudents->total() }}</div>
        </div>

        <div class="stat bg-base-100 shadow-sm rounded-lg">
            <div class="stat-figure text-success">
                <x-icon name="o-check-circle" class="w-8 h-8" />
            </div>
            <div class="stat-title">Passed</div>
            <div class="stat-value text-success">
                {{ $examStudents->where('examResult.result_status', ExamResultStatusEnum::Passed->value)->count() }}
            </div>
        </div>

        <div class="stat bg-base-100 shadow-sm rounded-lg">
            <div class="stat-figure text-error">
                <x-icon name="o-x-circle" class="w-8 h-8" />
            </div>
            <div class="stat-title">Failed</div>
            <div class="stat-value text-error">
                {{ $examStudents->where('examResult.result_status', ExamResultStatusEnum::Failed->value)->count() }}
            </div>
        </div>

        <div class="stat bg-base-100 shadow-sm rounded-lg">
            <div class="stat-figure text-warning">
                <x-icon name="o-clock" class="w-8 h-8" />
            </div>
            <div class="stat-title">Pending</div>
            <div class="stat-value text-warning">
                {{ $examStudents->where('examResult.result_status', ExamResultStatusEnum::NotDeclared->value)->count() + $examStudents->whereNull('examResult')->count() }}
            </div>
        </div>
    </div>

    {{-- Results Table --}}
    <div class="card bg-base-100 shadow-sm">
        <div class="card-body">
            <div class="overflow-x-auto">
                <table class="table table-zebra">
                    <thead>
                        <tr>
                            @foreach ($headers as $header)
                                @if ($header['key'] === 'select')
                                    <th class="{{ $header['class'] }}">
                                        <input type="checkbox" wire:model="selectAll" class="checkbox checkbox-sm" />
                                    </th>
                                @else
                                    <th class="{{ $header['class'] }}">{{ $header['label'] }}</th>
                                @endif
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($examStudents as $examStudent)
                            <tr>
                                <td>
                                    <input type="checkbox" wire:model="selectedStudents" value="{{ $examStudent->id }}"
                                        class="checkbox checkbox-sm" />
                                </td>
                                <td>
                                    <div class="flex items-center gap-3">
                                        <div class="avatar placeholder">
                                            <div class="bg-neutral text-neutral-content rounded-full w-8">
                                                <span
                                                    class="text-xs">{{ substr($examStudent->student->name, 0, 1) }}</span>
                                            </div>
                                        </div>
                                        <div>
                                            <div class="font-bold">{{ $examStudent->student->name }}</div>
                                            <div class="text-sm opacity-50">{{ $examStudent->student->email }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge badge-outline">{{ $examStudent->exam->exam_id }}</span>
                                </td>
                                <td>{{ $examStudent->exam->course->name }}</td>
                                <td>
                                    @if ($examStudent->examResult)
                                        @if ($examStudent->examResult->result_status === ExamResultStatusEnum::Passed->value)
                                            <span class="badge badge-success">Passed</span>
                                        @elseif($examStudent->examResult->result_status === ExamResultStatusEnum::Failed->value)
                                            <span class="badge badge-error">Failed</span>
                                        @else
                                            <span class="badge badge-warning">Not Declared</span>
                                        @endif
                                    @else
                                        <span class="badge badge-ghost">Not Started</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($examStudent->examResult)
                                        <span class="font-mono">{{ $examStudent->examResult->score }}%</span>
                                    @else
                                        <span class="text-base-content/50">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($examStudent->examResult)
                                        <span class="badge badge-outline">{{ $examStudent->examResult->grade }}</span>
                                    @else
                                        <span class="text-base-content/50">-</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="flex gap-2">
                                        @if ($examStudent->examResult)
                                            <x-button icon="o-eye" class="btn-xs btn-ghost"
                                                wire:click="viewResult({{ $examStudent->id }})"
                                                title="View Result" />
                                            @if ($examStudent->examResult->result_status === ExamResultStatusEnum::NotDeclared->value)
                                                <x-button icon="o-check-circle" class="btn-xs btn-success"
                                                    wire:click="declareResult({{ $examStudent->examResult->id }})"
                                                    title="Declare Result" />
                                            @endif
                                            <x-button icon="o-clock" class="btn-xs btn-info"
                                                wire:click="viewStudentHistory({{ $examStudent->id }})"
                                                title="View History" />
                                        @endif

                                        @if (!$examStudent->examResult || $examStudent->examResult->result_status === ExamResultStatusEnum::Failed->value)
                                            <x-button icon="o-calendar" class="btn-xs btn-warning"
                                                wire:click="rescheduleExam({{ $examStudent->id }})"
                                                title="Reschedule Exam" />
                                        @endif

                                        @if (!$examStudent->examResult)
                                            <x-button icon="o-plus-circle" class="btn-xs btn-info"
                                                wire:click="createNewExam({{ $examStudent->id }})"
                                                title="Create New Exam" />
                                            <x-button icon="o-x-circle" class="btn-xs btn-error"
                                                wire:click="markAsAbsent({{ $examStudent->id }})"
                                                title="Mark as Absent" />
                                        @endif

                                        @if ($examStudent->examResult)
                                            <x-button icon="o-arrow-path" class="btn-xs btn-warning"
                                                wire:click="resetResult({{ $examStudent->id }})"
                                                title="Reset Result" />
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-8 text-base-content/50">
                                    <div class="flex flex-col items-center gap-2">
                                        <x-icon name="o-inbox" class="w-8 h-8" />
                                        <span>No exam students found</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            <div class="mt-6">
                {{ $examStudents->links() }}
            </div>
        </div>
    </div>

    {{-- Result Detail Modal --}}
    @if ($showResultModal && $selectedStudent)
        <x-modal wire:model="showResultModal" title="Exam Result Details" separator>
            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="label">
                            <span class="label-text font-semibold">Student:</span>
                        </label>
                        <p>{{ $selectedStudent->student->name }}</p>
                    </div>
                    <div>
                        <label class="label">
                            <span class="label-text font-semibold">Exam:</span>
                        </label>
                        <p>{{ $selectedStudent->exam->exam_id }}</p>
                    </div>
                    <div>
                        <label class="label">
                            <span class="label-text font-semibold">Score:</span>
                        </label>
                        <p class="font-mono text-lg">{{ $selectedStudent->examResult->score }}%</p>
                    </div>
                    <div>
                        <label class="label">
                            <span class="label-text font-semibold">Grade:</span>
                        </label>
                        <p class="badge badge-lg">{{ $selectedStudent->examResult->grade }}</p>
                    </div>
                </div>

                @if ($selectedStudent->examResult->data && isset($selectedStudent->examResult->data['category_results']))
                    <div>
                        <label class="label">
                            <span class="label-text font-semibold">Results by Category:</span>
                        </label>
                        <div class="space-y-3">
                            @foreach ($selectedStudent->examResult->data['category_results'] as $categoryId => $result)
                                @php
                                    $category = \App\Models\Category::find($categoryId);
                                    $correct = $result['correct'] ?? 0;
                                    $wrong = $result['wrong'] ?? 0;
                                    $total = $correct + $wrong;
                                    $percentage = $total > 0 ? round(($correct / $total) * 100, 2) : 0;
                                @endphp
                                <div class="card bg-base-200">
                                    <div class="card-body p-4">
                                        <div class="flex justify-between items-center mb-2">
                                            <h4 class="font-semibold">{{ $category->name ?? 'Unknown Category' }}</h4>
                                            <span class="badge badge-outline">{{ $percentage }}%</span>
                                        </div>
                                        <div class="grid grid-cols-3 gap-4 text-sm">
                                            <div class="text-center">
                                                <div class="text-success font-bold">{{ $correct }}</div>
                                                <div class="text-xs opacity-70">Correct</div>
                                            </div>
                                            <div class="text-center">
                                                <div class="text-error font-bold">{{ $wrong }}</div>
                                                <div class="text-xs opacity-70">Wrong</div>
                                            </div>
                                            <div class="text-center">
                                                <div class="font-bold">{{ $total }}</div>
                                                <div class="text-xs opacity-70">Total</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if ($selectedStudent->examResult->data && isset($selectedStudent->examResult->data['answers']))
                    <div>
                        <label class="label">
                            <span class="label-text font-semibold">Detailed Answers:</span>
                        </label>
                        <div class="space-y-2 max-h-60 overflow-y-auto">
                            @foreach ($selectedStudent->examResult->data['answers'] as $questionId => $answer)
                                @php
                                    $question = \App\Models\Question::find($questionId);
                                    $isCorrect = $answer['is_correct'] ?? false;
                                @endphp
                                @if ($question)
                                    <div class="card bg-base-200">
                                        <div class="card-body p-3">
                                            <div class="flex items-center gap-2 mb-2">
                                                <span
                                                    class="badge {{ $isCorrect ? 'badge-success' : 'badge-error' }}">
                                                    {{ $isCorrect ? 'Correct' : 'Wrong' }}
                                                </span>
                                                <span class="text-sm opacity-70">Q{{ $loop->iteration }}</span>
                                            </div>
                                            <p class="text-sm">{{ $question->question_text }}</p>
                                            <div class="text-xs opacity-70 mt-1">
                                                <strong>Student's Answer:</strong>
                                                {{ $answer['student_answer'] ?? 'No answer' }}
                                            </div>
                                            @if (!$isCorrect && isset($answer['correct_answer']))
                                                <div class="text-xs text-success mt-1">
                                                    <strong>Correct Answer:</strong> {{ $answer['correct_answer'] }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            <x-slot:actions>
                <x-button label="Close" wire:click="showResultModal = false" />
            </x-slot:actions>
        </x-modal>
    @endif

    {{-- Reschedule Modal --}}
    @if ($showRescheduleModal && $selectedStudent)
        <x-modal wire:model="showRescheduleModal" title="Reschedule Exam" separator>
            <div class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="label">
                            <span class="label-text">New Date</span>
                        </label>
                        <input type="date" wire:model="rescheduleData.new_date"
                            class="input input-bordered w-full" />
                    </div>
                    <div>
                        <label class="label">
                            <span class="label-text">Start Time</span>
                        </label>
                        <input type="time" wire:model="rescheduleData.new_start_time"
                            class="input input-bordered w-full" />
                    </div>
                    <div>
                        <label class="label">
                            <span class="label-text">End Time</span>
                        </label>
                        <input type="time" wire:model="rescheduleData.new_end_time"
                            class="input input-bordered w-full" />
                    </div>
                </div>

                <div class="alert alert-info">
                    <x-icon name="o-information-circle" class="w-5 h-5" />
                    <span>This will update the exam schedule and generate new credentials for the student.</span>
                </div>
            </div>

            <x-slot:actions>
                <x-button label="Cancel" wire:click="showRescheduleModal = false" />
                <x-button label="Reschedule" class="btn-primary" wire:click="confirmReschedule" />
            </x-slot:actions>
        </x-modal>
    @endif

    {{-- New Exam Modal --}}
    @if ($showNewExamModal && $selectedStudent)
        <x-modal wire:model="showNewExamModal" title="Create New Exam" separator>
            <div class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="label">
                            <span class="label-text">Date</span>
                        </label>
                        <input type="date" wire:model="newExamData.date" class="input input-bordered w-full" />
                    </div>
                    <div>
                        <label class="label">
                            <span class="label-text">Start Time</span>
                        </label>
                        <input type="time" wire:model="newExamData.start_time"
                            class="input input-bordered w-full" />
                    </div>
                    <div>
                        <label class="label">
                            <span class="label-text">End Time</span>
                        </label>
                        <input type="time" wire:model="newExamData.end_time"
                            class="input input-bordered w-full" />
                    </div>
                </div>

                <div class="alert alert-info">
                    <x-icon name="o-information-circle" class="w-5 h-5" />
                    <span>This will create a new exam with the same categories and course, but with new credentials for
                        the student.</span>
                </div>
            </div>

            <x-slot:actions>
                <x-button label="Cancel" wire:click="showNewExamModal = false" />
                <x-button label="Create Exam" class="btn-primary" wire:click="confirmNewExam" />
            </x-slot:actions>
        </x-modal>
    @endif
</div>
