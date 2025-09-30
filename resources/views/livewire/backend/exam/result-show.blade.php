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

    public $showEditModal = false;
    public $showDeclareModal = false;
    public $showAnswersModal = false;

    // Edit form fields
    public $editScore;
    public $editPointsEarned;
    public $editPercentage;
    public $editResult;
    public $editAnswersData = [];

    // Declare form fields
    public $declareResult;
    public $declareNotes = '';

    public function mount(ExamResult $examResult): void
    {
        $this->examResult = $examResult->load(['student', 'exam.course', 'category', 'declaredBy']);

        // Check if student exists
        if (!$this->examResult->student) {
            $this->error('Student not found for this exam result.', redirectTo: route('admin.exam.results'));
            return;
        }

        // Initialize edit form with current values
        $this->editScore = $this->examResult->score;
        $this->editPointsEarned = $this->examResult->points_earned;
        $this->editPercentage = $this->examResult->percentage;
        $this->editResult = $this->examResult->result;
        $this->editAnswersData = $this->examResult->answers_data ?? [];
    }

    public function rendering(View $view): void
    {
        $view->examResult = $this->examResult;
    }

    public function openEditModal(): void
    {
        $this->showEditModal = true;
    }

    public function closeEditModal(): void
    {
        $this->showEditModal = false;
        $this->resetEditForm();
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
    }

    public function closeAnswersModal(): void
    {
        $this->showAnswersModal = false;
    }

    public function resetEditForm(): void
    {
        $this->editScore = $this->examResult->score;
        $this->editPointsEarned = $this->examResult->points_earned;
        $this->editPercentage = $this->examResult->percentage;
        $this->editResult = $this->examResult->result;
        $this->editAnswersData = $this->examResult->answers_data ?? [];
    }

    public function resetDeclareForm(): void
    {
        $this->declareResult = '';
        $this->declareNotes = '';
    }

    public function updateResult(): void
    {
        $this->validate([
            'editScore' => 'required|numeric|min:0|max:100',
            'editPointsEarned' => 'required|numeric|min:0',
            'editPercentage' => 'required|numeric|min:0|max:100',
            'editResult' => 'required|in:passed,failed',
        ]);

        try {
            $this->examResult->update([
                'score' => $this->editScore,
                'points_earned' => $this->editPointsEarned,
                'percentage' => (float) $this->editPercentage,
                'result' => $this->editResult,
                'answers_data' => $this->editAnswersData,
            ]);

            $this->success('Exam result updated successfully!');
            $this->closeEditModal();
            $this->examResult->refresh();
        } catch (\Exception $e) {
            $this->error('Failed to update exam result: ' . $e->getMessage());
        }
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

    public function updateAnswerData($questionId, $field, $value): void
    {
        if (isset($this->editAnswersData["question_{$questionId}"])) {
            $this->editAnswersData["question_{$questionId}"][$field] = $value;

            // Recalculate points if point_earned is changed
            if ($field === 'point_earned') {
                $this->recalculateTotals();
            }
        }
    }

    public function recalculateTotals(): void
    {
        $totalPointsEarned = 0;
        $totalPoints = 0;
        $answeredQuestions = 0;

        foreach ($this->editAnswersData as $key => $questionData) {
            if (str_starts_with($key, 'question_')) {
                $totalPoints += $questionData['point'] ?? 0;
                $totalPointsEarned += $questionData['point_earned'] ?? 0;

                if (isset($questionData['answer']) && $questionData['answer'] !== null) {
                    $answeredQuestions++;
                }
            }
        }

        $this->editPointsEarned = $totalPointsEarned;
        $this->editPercentage = $totalPoints > 0 ? round(($totalPointsEarned / $totalPoints) * 100, 2) : 0;
        $this->editScore = $this->editPercentage;
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
            <x-button label="Edit Result" icon="o-pencil" class="btn-warning btn-sm" wire:click="openEditModal" />
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

    {{-- Edit Modal --}}
    <x-modal wire:model="showEditModal" title="Edit Exam Result" class="backdrop-blur" max-width="4xl">
        <div class="space-y-6">
            {{-- Basic Info --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <x-input label="Score" wire:model="editScore" type="number" step="0.01" min="0"
                    max="100" />
                <x-input label="Points Earned" wire:model="editPointsEarned" type="number" step="0.01"
                    min="0" />
                <x-input label="Percentage" wire:model="editPercentage" type="number" step="0.01"
                    min="0" max="100" />
            </div>

            @php
                $results = [['id' => 'Passed', 'name' => 'passed'], ['id' => 'Failed', 'name' => 'failed']];
            @endphp
            <x-select label="Result" wire:model="editResult" :options="$results" />

            {{-- Answer Data Editor --}}
            <div class="divider">Answer Data</div>

            <div class="max-h-96 overflow-y-auto space-y-4">
                @foreach ($editAnswersData as $key => $questionData)
                    @if (str_starts_with($key, 'question_'))
                        <div class="card bg-base-200 p-4">
                            <div class="font-medium mb-2">Question {{ $questionData['question_id'] ?? 'N/A' }}</div>
                            <div class="text-sm mb-3">{{ $questionData['question'] ?? 'N/A' }}</div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <x-input label="Points" wire:model="editAnswersData.{{ $key }}.point"
                                    type="number" step="0.01" />
                                <x-input label="Points Earned"
                                    wire:model="editAnswersData.{{ $key }}.point_earned" type="number"
                                    step="0.01" />
                                <x-input label="Answer ID" wire:model="editAnswersData.{{ $key }}.answer"
                                    type="number" />
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>

        <x-slot:actions>
            <x-button label="Cancel" class="btn-ghost" wire:click="closeEditModal" />
            <x-button label="Update Result" class="btn-primary" wire:click="updateResult" />
        </x-slot:actions>
    </x-modal>

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
                $results = [['id' => 'Passed', 'name' => 'passed'], ['id' => 'Failed', 'name' => 'failed']];
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
    <x-modal wire:model="showAnswersModal" title="Detailed Answer Data" class="backdrop-blur" max-width="6xl">
        <div class="space-y-4 max-h-96 overflow-y-auto">
            @if ($examResult->answers_data)
                @foreach ($examResult->answers_data as $key => $questionData)
                    @if (str_starts_with($key, 'question_'))
                        <div class="card bg-base-200 p-4">
                            <div class="flex justify-between items-start mb-3">
                                <div class="font-medium">Question {{ $questionData['question_id'] ?? 'N/A' }}</div>
                                <div class="text-sm">
                                    <span class="badge badge-outline">{{ $questionData['point'] ?? 0 }} pts</span>
                                    <span
                                        class="badge {{ ($questionData['point_earned'] ?? 0) > 0 ? 'badge-success' : 'badge-error' }}">
                                        {{ $questionData['point_earned'] ?? 0 }} earned
                                    </span>
                                </div>
                            </div>

                            <div class="text-sm mb-3">{{ $questionData['question'] ?? 'N/A' }}</div>

                            @if (isset($questionData['options']) && is_array($questionData['options']))
                                <div class="space-y-2">
                                    @foreach ($questionData['options'] as $option)
                                        <div
                                            class="flex items-center gap-2 p-2 rounded {{ $option['id'] == ($questionData['answer'] ?? null) ? 'bg-primary/20' : '' }}">
                                            <input type="radio" disabled
                                                {{ $option['id'] == ($questionData['answer'] ?? null) ? 'checked' : '' }}>
                                            <span
                                                class="text-sm {{ $option['is_correct'] ?? false ? 'font-bold text-success' : '' }}">
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
                    @endif
                @endforeach
            @else
                <div class="text-center py-8 text-gray-500">
                    <x-icon name="o-document-text" class="w-12 h-12 mx-auto mb-2" />
                    <div>No answer data available</div>
                </div>
            @endif
        </div>

        <x-slot:actions>
            <x-button label="Close" class="btn-ghost" wire:click="closeAnswersModal" />
        </x-slot:actions>
    </x-modal>
</div>
