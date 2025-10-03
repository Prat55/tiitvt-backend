<?php

use App\Models\ExamResult;
use App\Enums\ExamResultStatusEnum;
use Mary\Traits\Toast;
use Illuminate\View\View;
use Livewire\Volt\Component;
use Livewire\Attributes\{Title};

new class extends Component {
    use Toast;

    #[Title('Exam Result Details')]
    public ExamResult $examResult;

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

    public function mount(ExamResult $examResult): void
    {
        $this->examResult = $examResult->load(['student', 'exam.course', 'category', 'declaredBy']);

        // Check if student exists
        if (!$this->examResult->student) {
            $this->error('Student not found for this exam result.', redirectTo: route('admin.exam.results'));
            return;
        }

        // Initialize questions array for navigation
        $this->initializeQuestions();
    }

    public function rendering(View $view): void
    {
        $view->examResult = $this->examResult;
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
            $this->tempAnswerData = $currentQuestion['data'];
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
            // Update the exam result directly
            $answersData = $this->examResult->answers_data ?? [];

            // Calculate earned points based on whether the selected answer is correct
            $selectedAnswerId = $this->tempAnswerData['answer'] ?? null;
            $isCorrect = false;

            if ($selectedAnswerId && isset($this->tempAnswerData['options'])) {
                foreach ($this->tempAnswerData['options'] as $option) {
                    if ($option['id'] == $selectedAnswerId && ($option['is_correct'] ?? false)) {
                        $isCorrect = true;
                        break;
                    }
                }
            }

            // Set earned points based on correctness
            $this->tempAnswerData['point_earned'] = $isCorrect ? $this->tempAnswerData['point'] ?? 0 : 0;

            $answersData[$questionKey] = $this->tempAnswerData;

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
            $this->error('Please enter a question ID');
            return;
        }

        $questionId = (int) $this->jumpToQuestionId;
        $foundIndex = -1;

        foreach ($this->questions as $index => $question) {
            if (isset($question['data']['question_id']) && $question['data']['question_id'] == $questionId) {
                $foundIndex = $index;
                break;
            }
        }

        if ($foundIndex !== -1) {
            $this->currentQuestionIndex = $foundIndex;
            $this->editingQuestion = false;
            $this->jumpToQuestionId = '';
            $this->success("Jumped to question {$questionId}");
        } else {
            $this->error("Question ID {$questionId} not found");
        }
    }
};
?>

<div>
    {{-- Header Section --}}
    <div class="flex justify-between items-start lg:items-center flex-col lg:flex-row mt-3 mb-5 gap-4">
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
                    <li class="font-medium">Result #{{ $examResult->id }}</li>
                </ul>
            </div>
        </div>

        <div class="flex flex-col sm:flex-row gap-3">
            @if (!$examResult->result)
                <x-button label="Declare Result" icon="o-flag" class="btn-primary btn-sm"
                    wire:click="openDeclareModal" />
            @endif
            <x-button label="View Answers" icon="o-document-text" class="btn-info btn-sm"
                wire:click="openAnswersModal" />
            <x-button label="Back to Results" icon="o-arrow-left" class="btn-ghost btn-sm"
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
                        <x-avatar placeholder="RT"
                            title="{{ $examResult->student?->first_name ?? 'Unknown' }} {{ $examResult->student?->last_name ?? 'Student' }}"
                            subtitle="{{ $examResult->student?->email ?? 'N/A' }}" class="!w-10" />
                    </div>
                    <div class="divider my-2"></div>
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="font-medium">Student Reg No:</span>
                            <div class="text-gray-600">{{ $examResult->student?->tiitvt_reg_no ?? 'N/A' }}</div>
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
                            <div class="text-gray-600">{{ $examResult->exam->exam_id }}</div>
                        </div>
                        <div>
                            <span class="font-medium">Duration:</span>
                            <div class="text-gray-600">{{ $examResult->exam_duration }} minutes</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Result Summary --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="stat bg-base-100 shadow-sm rounded-lg">
            <div class="stat-figure text-primary">
                <x-icon name="o-star" class="w-8 h-8" />
            </div>
            <div class="stat-title">Score</div>
            <div class="stat-value text-primary">{{ $examResult->score }}</div>
            <div class="stat-desc">{{ $examResult->points_earned }}/{{ $examResult->total_points }} points</div>
        </div>

        <div class="stat bg-base-100 shadow-sm rounded-lg">
            <div class="stat-figure text-success">
                <x-icon name="o-chart-bar" class="w-8 h-8" />
            </div>
            <div class="stat-title">Percentage</div>
            <div class="stat-value text-success">{{ number_format($examResult->percentage, 1) }}%</div>
            <div class="stat-desc">Grade: {{ $this->getGradeFromPercentage($examResult->percentage) }}</div>
        </div>

        <div class="stat bg-base-100 shadow-sm rounded-lg">
            <div class="stat-figure text-info">
                <x-icon name="o-question-mark-circle" class="w-8 h-8" />
            </div>
            <div class="stat-title">Questions</div>
            <div class="stat-value text-info">{{ $examResult->answered_questions }}/{{ $examResult->total_questions }}
            </div>
            <div class="stat-desc">{{ $examResult->skipped_questions }} skipped</div>
        </div>

        <div class="stat bg-base-100 shadow-sm rounded-lg">
            <div class="stat-figure text-warning">
                <x-icon name="o-clock" class="w-8 h-8" />
            </div>
            <div class="stat-title">Time Taken</div>
            <div class="stat-value text-warning">{{ number_format($examResult->time_taken_minutes, 1) }}m</div>
            <div class="stat-desc">Submitted: {{ $examResult->submitted_at?->format('M d, g:i A') }}</div>
        </div>
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
                            <x-input wire:model="jumpToQuestionId" placeholder="Question No." type="number"
                                class="input-sm" />
                            <x-button label="Go" icon="o-arrow-right" class="btn-sm btn-outline"
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

                        {{-- Editing Controls --}}
                        @if ($editingQuestion)
                            <div class="mt-6 p-4 bg-warning/10 border border-warning/20 rounded-lg">
                                <h4 class="font-medium mb-3">Edit Answer Details</h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <x-input label="Points" wire:model="tempAnswerData.point" type="number"
                                        step="0.01" />
                                    <x-input label="Points Earned" wire:model="tempAnswerData.point_earned"
                                        type="number" step="0.01" />
                                </div>
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
