<?php

use Mary\Traits\Toast;
use App\Enums\RolesEnum;
use Illuminate\View\View;
use Livewire\Volt\Component;
use Livewire\Attributes\{Title};
use App\Enums\ExamResultStatusEnum;
use App\Models\{ExamResult, Exam, Student};

new class extends Component {
    use Toast;

    #[Title('Exam Result Details')]
    public ExamResult $examResult;
    public $allExamResults = [];
    public $selectedCategoryId = null;

    public $showDeclareModal = false;
    public $showAnswersModal = false;

    // Declare form fields
    public $declareResult;
    public $declareNotes = '';

    // Answer navigation and editing
    public $currentQuestionIndex = 0;
    public $questions = [];
    public $editingQuestion = false;
    public $tempAnswerData = [];
    public $jumpToQuestionId = '';

    public function mount($examId, $studentRegNo): void
    {
        // Find the exam by exam_id
        $exam = Exam::where('exam_id', $examId)->first();

        if (!$exam) {
            $this->error('Exam not found.', redirectTo: route('admin.exam.results'));
            return;
        }

        // Find the student by TIITVT registration number (decode from URL)
        $studentRegNo = decodeTiitvtRegNo($studentRegNo);
        $student = Student::where('tiitvt_reg_no', $studentRegNo)->first();

        if (!$student) {
            $this->error('Student not found.', redirectTo: route('admin.exam.results'));
            return;
        }

        // Check if user has permission to access this student
        if (!hasAuthRole(RolesEnum::Admin->value)) {
            if (!$student->center_id == getUserCenterId()) {
                $this->error('You are not authorized to access this student.', redirectTo: route('admin.exam.results'));
                return;
            }
        }

        // Get the first exam result for this student in this exam (for initial display)
        $this->examResult = ExamResult::with(['student', 'exam.course', 'category', 'declaredBy'])
            ->where('exam_id', $exam->id)
            ->where('student_id', $student->id)
            ->first();

        if (!$this->examResult) {
            $this->error('No exam result found for this student in this exam.', redirectTo: route('admin.exam.results'));
            return;
        }

        // Get all exam results for this student in this exam
        $this->allExamResults = ExamResult::with(['category'])
            ->where('exam_id', $exam->id)
            ->where('student_id', $student->id)
            ->get()
            ->groupBy('category_id')
            ->map(function ($results, $categoryId) {
                $firstResult = $results->first();
                return [
                    'category_id' => $categoryId,
                    'category_name' => $firstResult->category->name,
                    'exam_result' => $firstResult,
                    'total_score' => $results->sum('score'),
                    'avg_percentage' => $results->avg('percentage'),
                    'total_questions' => $results->sum('total_questions'),
                    'answered_questions' => $results->sum('answered_questions'),
                ];
            })
            ->values();

        // Set default selected category to the current result's category
        $this->selectedCategoryId = $this->examResult->category_id;

        // Initialize questions array for navigation
        $this->initializeQuestions();
    }

    public function rendering(View $view): void
    {
        $view->examResult = $this->examResult;
        $view->allExamResults = $this->allExamResults;
    }

    public function selectCategory($categoryId): void
    {
        $this->selectedCategoryId = $categoryId;

        // Find the exam result for this category
        $categoryData = $this->allExamResults->where('category_id', $categoryId)->first();
        if ($categoryData) {
            $this->examResult = $categoryData['exam_result']->load(['student', 'exam.course', 'category', 'declaredBy']);
            $this->initializeQuestions();

            // Reset question navigation to first question
            $this->currentQuestionIndex = 0;
            $this->editingQuestion = false;
            $this->jumpToQuestionId = '';
        }
    }

    public function getSelectedCategoryData()
    {
        return $this->allExamResults->where('category_id', $this->selectedCategoryId)->first();
    }

    public function openDeclareModal(): void
    {
        $this->showDeclareModal = true;
    }

    public function closeDeclareModal(): void
    {
        $this->showDeclareModal = false;
        $this->resetDeclareForm();
    }

    public function openAnswersModal(): void
    {
        $this->showAnswersModal = true;
        $this->currentQuestionIndex = 0;
        $this->editingQuestion = false;
    }

    public function closeAnswersModal(): void
    {
        $this->showAnswersModal = false;
        $this->editingQuestion = false;
        $this->currentQuestionIndex = 0;
    }

    public function resetDeclareForm(): void
    {
        $this->declareResult = '';
        $this->declareNotes = '';
    }

    public function declareResult(): void
    {
        $this->validate([
            'declareResult' => 'required|in:passed,failed',
            'declareNotes' => 'nullable|string|max:500',
        ]);

        try {
            $this->examResult->update([
                'result' => $this->declareResult,
                'declared_by' => auth()->id(),
                'declared_at' => now(),
            ]);

            $this->success('Exam result declared successfully!');
            $this->closeDeclareModal();
            $this->examResult->refresh();
        } catch (\Exception $e) {
            $this->error('Failed to declare exam result: ' . $e->getMessage());
        }
    }

    public function recalculateResult(): void
    {
        try {
            if ($this->examResult->recalculateResult()) {
                $this->success('Result recalculated successfully!');
                $this->examResult->refresh();
            } else {
                $this->error('Failed to recalculate result.');
            }
        } catch (\Exception $e) {
            $this->error('Failed to recalculate result: ' . $e->getMessage());
        }
    }

    public function getGradeFromPercentage($percentage): string
    {
        if ($percentage >= 90) {
            return 'A+';
        }
        if ($percentage >= 80) {
            return 'A';
        }
        if ($percentage >= 70) {
            return 'B+';
        }
        if ($percentage >= 60) {
            return 'B';
        }
        if ($percentage >= 50) {
            return 'C+';
        }
        if ($percentage >= 40) {
            return 'C';
        }
        if ($percentage >= 30) {
            return 'D';
        }
        return 'F';
    }

    public function getOverallResult(): string
    {
        // If ANY category is failed, the overall result is failed
        $hasAnyFailed = $this->allExamResults->some(function ($resultData) {
            return $resultData['exam_result']->result === 'failed';
        });

        if ($hasAnyFailed) {
            return 'failed';
        }

        // Check if all exam results are passed
        $allPassed = $this->allExamResults->every(function ($resultData) {
            return $resultData['exam_result']->result === 'passed';
        });

        if ($allPassed) {
            return 'passed';
        }

        // If there are pending results (not all passed, no failed)
        return 'pending';
    }

    public function initializeQuestions(): void
    {
        $this->questions = [];
        if ($this->examResult->answers_data) {
            foreach ($this->examResult->answers_data as $key => $questionData) {
                if (str_starts_with($key, 'question_')) {
                    $this->questions[] = [
                        'key' => $key,
                        'data' => $questionData,
                    ];
                }
            }
        }
    }

    public function nextQuestion(): void
    {
        if ($this->currentQuestionIndex < count($this->questions) - 1) {
            $this->currentQuestionIndex++;
            $this->editingQuestion = false;
        }
    }

    public function previousQuestion(): void
    {
        if ($this->currentQuestionIndex > 0) {
            $this->currentQuestionIndex--;
            $this->editingQuestion = false;
        }
    }

    public function startEditing(): void
    {
        $this->editingQuestion = true;
        $currentQuestion = $this->questions[$this->currentQuestionIndex] ?? null;
        if ($currentQuestion) {
            // Only copy the answer, not the points (they are read-only)
            $this->tempAnswerData = [
                'answer' => $currentQuestion['data']['answer'] ?? null,
            ];
        }
    }

    public function cancelEditing(): void
    {
        $this->editingQuestion = false;
        $this->tempAnswerData = [];
    }

    public function saveEditing(): void
    {
        if ($this->editingQuestion && isset($this->questions[$this->currentQuestionIndex])) {
            $questionKey = $this->questions[$this->currentQuestionIndex]['key'];
            $currentQuestionData = $this->questions[$this->currentQuestionIndex]['data'];

            // Update the exam result directly
            $answersData = $this->examResult->answers_data ?? [];

            // Calculate earned points based on whether the selected answer is correct
            $selectedAnswerId = $this->tempAnswerData['answer'] ?? null;
            $isCorrect = false;

            if ($selectedAnswerId && isset($currentQuestionData['options'])) {
                foreach ($currentQuestionData['options'] as $option) {
                    if ($option['id'] == $selectedAnswerId && ($option['is_correct'] ?? false)) {
                        $isCorrect = true;
                        break;
                    }
                }
            }

            // Update only the answer and recalculate earned points
            $updatedQuestionData = $currentQuestionData;
            $updatedQuestionData['answer'] = $selectedAnswerId;
            $updatedQuestionData['point_earned'] = $isCorrect ? $currentQuestionData['point'] ?? 0 : 0;

            $answersData[$questionKey] = $updatedQuestionData;

            // Recalculate totals
            $totalPointsEarned = 0;
            $totalPoints = 0;
            $answeredQuestions = 0;

            foreach ($answersData as $key => $questionData) {
                if (str_starts_with($key, 'question_')) {
                    $totalPoints += $questionData['point'] ?? 0;
                    $totalPointsEarned += $questionData['point_earned'] ?? 0;

                    if (isset($questionData['answer']) && $questionData['answer'] !== null) {
                        $answeredQuestions++;
                    }
                }
            }

            $percentage = $totalPoints > 0 ? round(($totalPointsEarned / $totalPoints) * 100, 2) : 0;

            $this->examResult->update([
                'answers_data' => $answersData,
                'points_earned' => $totalPointsEarned,
                'percentage' => $percentage,
                'score' => $percentage,
            ]);
            $this->examResult->refresh();

            // Refresh the questions array with updated data
            $this->initializeQuestions();

            $this->editingQuestion = false;
            $this->tempAnswerData = [];

            // Force Livewire to refresh the component
            $this->dispatch('$refresh');

            $this->success('Answer updated successfully!');
        }
    }

    public function updateTempAnswer($field, $value): void
    {
        $this->tempAnswerData[$field] = $value;
    }

    public function getCurrentQuestion()
    {
        return $this->questions[$this->currentQuestionIndex] ?? null;
    }

    public function jumpToQuestion(): void
    {
        if (empty($this->jumpToQuestionId)) {
            $this->error('Please enter a question number');
            return;
        }

        $questionNumber = (int) $this->jumpToQuestionId;

        // Convert to 0-based index
        $questionIndex = $questionNumber - 1;

        // Check if the index is valid
        if ($questionIndex >= 0 && $questionIndex < count($this->questions)) {
            $this->currentQuestionIndex = $questionIndex;
            $this->editingQuestion = false;
            $this->jumpToQuestionId = '';
            $this->success("Jumped to question {$questionNumber}");
        } else {
            $this->error("Question number {$questionNumber} not found. Please enter a number between 1 and " . count($this->questions));
        }
    }
};
?>

<div>
    {{-- Header Section --}}
    <div
        class="flex justify-between items-start lg:items-center flex-col lg:flex-row mt-3 mb-5 gap-4 flex-wrap lg:flex-nowrap">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                Exam Result Details
            </h1>
            <div class="breadcrumbs text-sm text-gray-600 dark:text-gray-400 mt-1">
                <ul class="flex items-center space-x-2">
                    <li>
                        <a href="{{ route('admin.index') }}" wire:navigate class="hover:text-primary transition-colors">
                            Dashboard
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('admin.exam.results') }}" wire:navigate
                            class="hover:text-primary transition-colors">
                            Exam Results
                        </a>
                    </li>
                    <li class="font-medium flex items-center flex-wrap lg:flex-nowrap gap-2">
                        <div class="badge badge-soft badge-sm">
                            Exam #{{ $examResult->exam?->exam_id }}
                        </div>
                        <div class="badge badge-soft badge-sm">
                            Student #{{ $examResult->student?->tiitvt_reg_no }}
                        </div>
                    </li>
                </ul>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3">
            {{-- @if (!$examResult->result)
                <x-button label="Declare Result" icon="o-flag" class="btn-primary btn-sm"
                    wire:click="openDeclareModal" />
            @endif --}}
            <x-button tooltip="View Answers" icon="o-document-text" class="btn-info btn-sm"
                wire:click="openAnswersModal" responsive />
            @if ($this->getOverallResult() !== 'failed')
                <x-button tooltip="Preview Certificate" icon="o-document" class="btn-success btn-sm"
                    link="{{ route('certificate.exam.preview', str_replace('/', '_', $examResult->student->tiitvt_reg_no)) }}"
                    external responsive />
                <x-button tooltip="Download Certificate" icon="o-arrow-down-tray" class="btn-primary btn-sm"
                    link="{{ route('certificate.exam.download', str_replace('/', '_', $examResult->student->tiitvt_reg_no)) }}"
                    external responsive />
            @endif
            <x-button label="Go Back" icon="o-arrow-left" class="btn-outline btn-sm"
                link="{{ route('admin.exam.results') }}" />
        </div>
    </div>
    <hr class="mb-6">

    {{-- Student & Exam Info --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        {{-- Student Information --}}
        <div class="card bg-base-100 shadow-sm">
            <div class="card-body">
                <h3 class="card-title text-lg mb-4">
                    <x-icon name="o-user" class="w-5 h-5" />
                    Student Information
                </h3>
                <div class="space-y-3">
                    <div class="flex items-center gap-3">
                        <x-avatar placeholder="{{ $examResult->student?->getInitials() }}"
                            title="{{ $examResult->student?->first_name ?? 'Unknown' }}{{ $examResult->student?->fathers_name ? ' ' . $examResult->student->fathers_name : '' }}{{ $examResult->student?->surname ? ' ' . $examResult->student->surname : '' }}"
                            subtitle="{{ $examResult->student?->email ?? 'N/A' }}" class="!w-10" />
                    </div>
                    <div class="divider my-2"></div>
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="font-medium">Student Reg No:</span>
                            <div>
                                <span class="badge badge-soft badge-sm">
                                    {{ $examResult->student?->tiitvt_reg_no ?? 'N/A' }}
                                </span>
                            </div>
                        </div>
                        <div>
                            <span class="font-medium">Phone:</span>
                            <div class="text-gray-600">{{ $examResult->student?->phone ?? 'N/A' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Exam Information --}}
        <div class="card bg-base-100 shadow-sm">
            <div class="card-body">
                <h3 class="card-title text-lg mb-4">
                    <x-icon name="o-academic-cap" class="w-5 h-5" />
                    Exam Information
                </h3>
                <div class="space-y-3">
                    <div>
                        <span class="font-medium">Course:</span>
                        <div class="text-lg font-semibold">{{ $examResult->exam?->course?->name ?? 'N/A' }}</div>
                    </div>
                    <div>
                        <span class="font-medium">Category:</span>
                        <div class="text-lg">{{ $examResult->category->name }}</div>
                    </div>
                    <div class="divider my-2"></div>
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="font-medium">Exam ID:</span>
                            <div class="mt-2">
                                <span class="badge badge-soft h-fit">{{ $examResult->exam?->exam_id ?? 'N/A' }}</span>
                            </div>
                        </div>
                        <div>
                            <span class="font-medium">Duration:</span>
                            <div class="mt-2">
                                <span class="badge badge-soft badge-primary">
                                    {{ $examResult->exam_duration }} minutes
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Category Selector --}}
    @if ($allExamResults->count() > 1)
        <div class="card bg-base-100 shadow-sm mb-6">
            <div class="card-body">
                <h3 class="card-title text-lg mb-4">
                    <x-icon name="o-tag" class="w-5 h-5" />
                    Select Category to View Results
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach ($allExamResults as $categoryData)
                        <div class="card bg-base-200 shadow-sm cursor-pointer transition-all hover:shadow-md {{ $selectedCategoryId == $categoryData['category_id'] ? 'ring-2 ring-primary' : '' }}"
                            wire:click="selectCategory({{ $categoryData['category_id'] }})">
                            <div class="card-body p-4">
                                <div class="flex items-center justify-between mb-2">
                                    <h4 class="font-semibold">{{ $categoryData['category_name'] }}</h4>
                                    @if ($selectedCategoryId == $categoryData['category_id'])
                                        <x-icon name="o-check-circle" class="w-5 h-5 text-primary" />
                                    @endif
                                </div>
                                <div class="text-sm space-y-1">
                                    <div class="flex justify-between">
                                        <span>Score:</span>
                                        <span class="font-medium">{{ $categoryData['total_score'] }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span>Percentage:</span>
                                        <span
                                            class="font-medium">{{ number_format($categoryData['avg_percentage'], 1) }}%</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span>Questions:</span>
                                        <span
                                            class="font-medium">{{ $categoryData['answered_questions'] }}/{{ $categoryData['total_questions'] }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    {{-- Result Summary --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <x-stat title="Score" value="{{ $examResult->score }}" icon="o-star"
            description="{{ $examResult->points_earned }}/{{ $examResult->total_points }} points"
            color="text-primary" />

        <x-stat title="Percentage" value="{{ number_format($examResult->percentage, 1) }}%" icon="o-chart-bar"
            description="Grade: {{ $this->getGradeFromPercentage($examResult->percentage) }}" color="text-success" />

        <x-stat title="Questions" value="{{ $examResult->answered_questions }}/{{ $examResult->total_questions }}"
            icon="o-question-mark-circle" description="{{ $examResult->skipped_questions }} skipped"
            color="text-info" />

        <x-stat title="Time Taken" value="{{ number_format($examResult->time_taken_minutes, 1) }}m" icon="o-clock"
            description="Submitted: {{ $examResult->submitted_at?->format('M d, g:i A') }}" color="text-warning" />
    </div>

    {{-- Result Status --}}
    <div class="card bg-base-100 shadow-sm mb-6">
        <div class="card-body">
            <h3 class="card-title text-lg mb-4">
                <x-icon name="o-flag" class="w-5 h-5" />
                Result Status
            </h3>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    @if ($examResult->result === 'passed')
                        <span class="badge badge-success badge-lg">Passed</span>
                    @elseif ($examResult->result === 'failed')
                        <span class="badge badge-error badge-lg">Failed</span>
                    @else
                        <span class="badge badge-warning badge-lg">Pending Declaration</span>
                    @endif

                    @if ($examResult->declared_by)
                        <div class="text-sm text-gray-600">
                            Declared by: {{ $examResult->declaredBy->name ?? 'Unknown' }}
                            <br>
                            <span class="text-xs">{{ $examResult->declared_at?->format('M d, Y g:i A') }}</span>
                        </div>
                    @endif
                </div>

                @if (!$examResult->result)
                    <x-button label="Declare Result" icon="o-flag" class="btn-primary"
                        wire:click="openDeclareModal" />
                @endif
            </div>

            {{-- Passing Criteria Information --}}
            @php
                $examCategory = $examResult->exam->examCategories->firstWhere('category_id', $examResult->category_id);
                $passingPoints = $examCategory?->passing_points ?? 0;
                $totalPoints = $examCategory?->total_points ?? 100;
            @endphp
            @if ($passingPoints > 0)
                <div class="mt-4 p-3 bg-base-200 rounded-lg">
                    <div class="flex items-center justify-between text-sm">
                        <span class="font-medium">Passing Criteria:</span>
                        <span class="font-semibold">{{ $passingPoints }} / {{ $totalPoints }} points
                            ({{ number_format(($passingPoints / $totalPoints) * 100, 1) }}%)</span>
                    </div>
                    <div class="flex items-center justify-between text-sm mt-2">
                        <span class="font-medium">Points Earned:</span>
                        <span
                            class="font-semibold {{ $examResult->points_earned >= $passingPoints ? 'text-success' : 'text-error' }}">
                            {{ $examResult->points_earned }} / {{ $totalPoints }} points
                        </span>
                    </div>

                    {{-- Show recalculate button if there's a mismatch --}}
                    @php
                        $shouldPass = (int) $examResult->points_earned >= (int) $passingPoints;
                        $currentlyPassed = $examResult->result === 'passed';
                        $hasMismatch = $shouldPass !== $currentlyPassed;
                    @endphp
                    @if ($hasMismatch)
                        <div class="mt-3 p-2 bg-warning/10 border border-warning rounded">
                            <div class="flex items-center justify-between">
                                <div class="text-sm text-warning">
                                    <x-icon name="o-exclamation-triangle" class="w-4 h-4 inline" />
                                    Result mismatch detected! Should be:
                                    <strong>{{ $shouldPass ? 'PASSED' : 'FAILED' }}</strong>
                                </div>
                                <x-button label="Fix Result" icon="o-arrow-path" class="btn-warning btn-sm"
                                    wire:click="recalculateResult" spinner="recalculateResult" />
                            </div>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>


    {{-- Declare Modal --}}
    <x-modal wire:model="showDeclareModal" title="Declare Exam Result" class="backdrop-blur">
        <div class="space-y-4">
            <div class="alert alert-info">
                <x-icon name="o-information-circle" class="w-6 h-6" />
                <div>
                    <h3 class="font-bold">Declare Final Result</h3>
                    <div class="text-xs">This will set the final result status for this exam.</div>
                </div>
            </div>

            @php
                $results = [['id' => 'passed', 'name' => 'Passed'], ['id' => 'failed', 'name' => 'Failed']];
            @endphp

            <x-select label="Result" wire:model="declareResult" :options="$results" />

            <x-textarea label="Notes (Optional)" wire:model="declareNotes"
                placeholder="Add any notes about this result..." />
        </div>

        <x-slot:actions>
            <x-button label="Cancel" class="btn-ghost" wire:click="closeDeclareModal" />
            <x-button label="Declare Result" class="btn-primary" wire:click="declareResult" />
        </x-slot:actions>
    </x-modal>

    {{-- Answers Modal --}}
    <x-modal wire:model="showAnswersModal" title="Answered Questions" class="backdrop-blur" box-class="max-w-6xl">
        @if (count($questions) > 0)
            @php
                $currentQuestion = $this->getCurrentQuestion();
                $questionData = $currentQuestion['data'] ?? null;
            @endphp

            @if ($questionData)
                <div class="space-y-6">
                    {{-- Question Header --}}
                    <div class="flex justify-between items-center">
                        <div class="flex items-center gap-4">
                            <h3 class="text-lg font-semibold">
                                Question {{ $currentQuestionIndex + 1 }} of {{ count($questions) }}
                            </h3>
                        </div>

                        <div class="text-sm">
                            <span class="badge badge-outline">{{ $questionData['point'] ?? 0 }} pts</span>
                            <span
                                class="badge {{ ($questionData['point_earned'] ?? 0) > 0 ? 'badge-success' : 'badge-error' }}">
                                {{ $questionData['point_earned'] ?? 0 }} earned
                            </span>
                        </div>
                    </div>

                    {{-- Jump to Question Input --}}
                    <div class="flex items-center justify-between gap-2">
                        <div class="flex items-center gap-2">
                            @if (!$editingQuestion)
                                <x-button label="Edit" icon="o-pencil" class="btn-warning btn-sm"
                                    wire:click="startEditing" />
                            @endif
                        </div>

                        <div class="flex items-center gap-2">
                            <x-input type="number" wire:model="jumpToQuestionId" placeholder="Question No."
                                min="1" max="{{ count($questions) }}" />
                            <x-button label="Go" icon="o-arrow-right" class="btn-outline"
                                wire:click="jumpToQuestion" />
                        </div>
                    </div>

                    {{-- Question Content --}}
                    <div class="card bg-base-200 p-6">

                        <div class="text-lg mb-4 font-medium">{{ $questionData['question'] ?? 'N/A' }}</div>

                        {{-- Options Display --}}
                        @if (isset($questionData['options']) && is_array($questionData['options']))
                            <div class="space-y-3">
                                @foreach ($questionData['options'] as $option)
                                    <div
                                        class="flex items-center gap-3 p-3 rounded-lg border {{ $option['id'] == ($questionData['answer'] ?? null) ? 'bg-primary/20 border-primary' : 'bg-base-100' }}">
                                        @if ($editingQuestion)
                                            <input type="radio" wire:model="tempAnswerData.answer"
                                                value="{{ $option['id'] }}" class="radio radio-primary">
                                        @else
                                            <input type="radio" disabled
                                                {{ $option['id'] == ($questionData['answer'] ?? null) ? 'checked' : '' }}
                                                class="radio">
                                        @endif

                                        <span
                                            class="flex-1 {{ $option['is_correct'] ?? false ? 'font-bold text-success' : '' }}">
                                            {{ $option['option_text'] ?? 'N/A' }}
                                            @if ($option['is_correct'] ?? false)
                                                <span class="badge badge-success badge-xs ml-2">Correct</span>
                                            @endif
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    {{-- Navigation Controls --}}
                    <div class="flex justify-between items-center">
                        <x-button label="Previous" icon="o-chevron-left"
                            class="btn-outline btn-sm {{ $currentQuestionIndex === 0 ? 'btn-disabled' : '' }}"
                            wire:click="previousQuestion" :disabled="$currentQuestionIndex === 0" />

                        <div class="flex gap-2">
                            @if ($editingQuestion)
                                <x-button label="Cancel" class="btn-ghost btn-sm" wire:click="cancelEditing" />
                                <x-button label="Done" icon="o-check" class="btn-success btn-sm"
                                    wire:click="saveEditing" />
                            @endif
                        </div>

                        <x-button label="Next" icon="o-chevron-right"
                            class="btn-outline btn-sm {{ $currentQuestionIndex === count($questions) - 1 ? 'btn-disabled' : '' }}"
                            wire:click="nextQuestion" :disabled="$currentQuestionIndex === count($questions) - 1" />
                    </div>
                </div>
            @endif
        @else
            <div class="text-center py-8 text-gray-500">
                <x-icon name="o-document-text" class="w-12 h-12 mx-auto mb-2" />
                <div>No answer data available</div>
            </div>
        @endif
    </x-modal>
</div>
