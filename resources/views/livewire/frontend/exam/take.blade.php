<?php

use Carbon\Carbon;
use Mary\Traits\Toast;
use Illuminate\View\View;
use Livewire\Volt\Component;
use App\Enums\ExamResultStatusEnum;
use Livewire\Attributes\{Title, Layout};
use App\Models\{ExamStudent, ExamCategory, Question, Option, Category};

new class extends Component {
    use Toast;

    #[Layout('components.layouts.guest')]
    #[Title('Taking Exam')]
    public $examStudent;
    public $exam;
    public $examCategory;
    public $category;
    public $questions;
    public $currentQuestionIndex = 0;
    public $currentQuestion;
    public $answers = [];
    public $selectedAnswer = null;
    public $remainingQuestions = [];
    public $totalQuestions = 0;
    public $totalPoints = 0;
    public $currentPoints = 0;
    public $earnedPoints = 0;
    public $timeRemaining;
    public $examStartTime;
    public $isLoading = false;
    public $showSubmitModal = false;

    public function mount($exam_id, $slug)
    {
        \Log::channel('exam')->info('Exam Take Mount Started', [
            'exam_id' => $exam_id,
            'slug' => $slug,
            'session_id' => session('exam_student_id'),
        ]);

        // Initialize questions as empty collection
        $this->questions = collect();

        // Only fetch basic data in mount
        $this->loadExamStudent();
        $this->loadExam();
        $this->loadCategory($slug);

        \Log::channel('exam')->info('Mount completed - basic data loaded');
    }

    public function loadExamStudent()
    {
        $examStudentId = session('exam_student_id');

        if (!$examStudentId) {
            \Log::channel('exam')->error('No exam student ID in session');
            $this->error('Please login first to access the exam.', redirectTo: route('frontend.exam.login'));
            return;
        }

        $this->examStudent = ExamStudent::with(['exam.course', 'exam.examCategories.category', 'student'])->find($examStudentId);

        if (!$this->examStudent) {
            \Log::channel('exam')->error('Exam student not found', ['exam_student_id' => $examStudentId]);
            $this->error('Exam student not found.', redirectTo: route('frontend.exam.login'));
            return;
        }

        \Log::channel('exam')->info('Exam student loaded', ['student_id' => $this->examStudent->id]);
    }

    public function loadExam()
    {
        if (!$this->examStudent) {
            return;
        }

        $this->exam = $this->examStudent->exam;
        \Log::channel('exam')->info('Exam loaded', ['exam_id' => $this->exam->exam_id]);
    }

    public function loadCategory($slug)
    {
        $this->category = Category::where('slug', $slug)->first();

        if (!$this->category) {
            \Log::channel('exam')->error('Category not found', ['slug' => $slug]);
            $this->error('Category not found.', redirectTo: route('frontend.exam.index'));
            return;
        }

        \Log::channel('exam')->info('Category loaded', ['category_id' => $this->category->id, 'name' => $this->category->name]);
    }

    public function rendering(View $view): void
    {
        // Validate and load questions only when rendering
        $this->validateExamAccess();
        $this->loadQuestions();
        $this->initializeExamState();

        \Log::channel('exam')->info('Rendering exam template', [
            'category_name' => $this->category->name ?? 'Unknown',
            'questions_count' => $this->questions ? $this->questions->count() : 0,
            'current_question_id' => $this->currentQuestion->id ?? 'None',
        ]);

        $view->title('Taking Exam - ' . ($this->category->name ?? 'Unknown Category'));
    }

    public function validateExamAccess()
    {
        if (!$this->exam || !$this->category) {
            return;
        }

        // Check if category is part of this exam
        $this->examCategory = $this->exam->examCategories->where('category_id', $this->category->id)->first();

        if (!$this->examCategory) {
            \Log::channel('exam')->error('Category not part of exam', [
                'category_id' => $this->category->id,
                'exam_id' => $this->exam->id,
            ]);
            $this->error('This category is not part of your exam.', redirectTo: route('frontend.exam.index'));
            return;
        }

        \Log::channel('exam')->info('Exam access validated');
    }

    public function loadQuestions()
    {
        if (!$this->category || !$this->examCategory) {
            return;
        }

        // Get total points for this category from exam category
        $this->totalPoints = $this->examCategory->total_points ?? 0;

        // Check if questions are already loaded in session
        $sessionQuestions = session('exam_questions_' . $this->category->id);
        $sessionPoints = session('exam_points_' . $this->category->id);

        if (!$sessionQuestions) {
            // Load questions with points and filter based on total_points
            $allQuestions = $this->category->questions()->with('options')->where('points', '>', 0)->inRandomOrder()->get();

            if ($allQuestions->isEmpty()) {
                \Log::channel('exam')->error('No questions available', ['category_id' => $this->category->id]);
                $this->error('No questions available for this category.', redirectTo: route('frontend.exam.index'));
                return;
            }

            // Select questions that match the total points requirement
            $selectedQuestions = $this->selectQuestionsByPoints($allQuestions, $this->totalPoints);

            if (empty($selectedQuestions)) {
                \Log::channel('exam')->error('No questions match point requirements', [
                    'category_id' => $this->category->id,
                    'required_points' => $this->totalPoints,
                    'available_questions' => $allQuestions->count(),
                ]);
                $this->error('No questions available that match the point requirements for this category.', redirectTo: route('frontend.exam.index'));
                return;
            }

            // Store questions and points in session
            session(['exam_questions_' . $this->category->id => $selectedQuestions]);
            session(['exam_points_' . $this->category->id => $this->totalPoints]);

            $this->remainingQuestions = $selectedQuestions;
            $this->totalQuestions = count($selectedQuestions);
            $this->currentPoints = 0;
            $this->earnedPoints = 0;

            \Log::channel('exam')->info('Questions loaded into session with points', [
                'category_id' => $this->category->id,
                'total_questions' => $this->totalQuestions,
                'total_points' => $this->totalPoints,
                'selected_questions' => array_map(function ($q) {
                    return ['id' => $q['id'], 'points' => $q['points']];
                }, $selectedQuestions),
            ]);
        } else {
            // Load from session
            $this->remainingQuestions = $sessionQuestions;
            $this->totalQuestions = count($sessionQuestions);
            $this->totalPoints = $sessionPoints ?? 0;
            $this->currentPoints = session('current_points_' . $this->category->id, 0);
            $this->earnedPoints = session('earned_points_' . $this->category->id, 0);

            \Log::channel('exam')->info('Questions loaded from session with points', [
                'category_id' => $this->category->id,
                'remaining_questions' => count($this->remainingQuestions),
                'total_points' => $this->totalPoints,
                'current_points' => $this->currentPoints,
                'earned_points' => $this->earnedPoints,
            ]);
        }

        // Convert to collection for compatibility
        $this->questions = collect($this->remainingQuestions);
    }

    public function selectQuestionsByPoints($questions, $targetPoints)
    {
        $selectedQuestions = [];
        $currentPoints = 0;

        // Sort questions by points (descending) for better selection
        $sortedQuestions = $questions->sortByDesc('points');

        foreach ($sortedQuestions as $question) {
            // If adding this question doesn't exceed target points, add it
            if ($currentPoints + $question->points <= $targetPoints) {
                $selectedQuestions[] = $question->toArray();
                $currentPoints += $question->points;
            }

            // If we've reached the target points, we can stop
            if ($currentPoints >= $targetPoints) {
                break;
            }
        }

        // If we couldn't reach target points with individual questions,
        // try to get as close as possible
        if ($currentPoints < $targetPoints) {
            $remainingPoints = $targetPoints - $currentPoints;
            foreach ($sortedQuestions as $question) {
                if (!in_array($question->id, array_column($selectedQuestions, 'id'))) {
                    if ($question->points <= $remainingPoints) {
                        $selectedQuestions[] = $question->toArray();
                        $currentPoints += $question->points;
                        break;
                    }
                }
            }
        }

        \Log::channel('exam')->info('Questions selected by points', [
            'target_points' => $targetPoints,
            'selected_points' => $currentPoints,
            'questions_selected' => count($selectedQuestions),
        ]);

        return $selectedQuestions;
    }

    public function isQuestionAnswered($questionId)
    {
        return isset($this->answers[$questionId]) && $this->answers[$questionId] !== null;
    }

    public function initializeExamState()
    {
        if (empty($this->remainingQuestions)) {
            \Log::channel('exam')->error('Cannot initialize exam state - no questions available');
            return;
        }

        // Load the first question from remaining questions
        $this->loadNextQuestion();

        $this->examStartTime = Carbon::parse(session('exam_start_time', now()));
        $this->answers = $this->examStudent->answers ?? [];

        if ($this->currentQuestion && isset($this->answers[$this->currentQuestion->id])) {
            $this->selectedAnswer = $this->answers[$this->currentQuestion->id];
        }

        \Log::channel('exam')->info('Exam state initialized', [
            'current_question_id' => $this->currentQuestion ? $this->currentQuestion->id : 'None',
            'remaining_questions' => count($this->remainingQuestions),
            'total_questions' => $this->totalQuestions,
        ]);
    }

    public function loadNextQuestion()
    {
        if (empty($this->remainingQuestions)) {
            $this->currentQuestion = null;
            \Log::channel('exam')->info('No more questions available');
            return;
        }

        // Get the first question from remaining questions
        $questionData = array_shift($this->remainingQuestions);

        // Convert array to object for compatibility
        $this->currentQuestion = (object) $questionData;

        // Update session with remaining questions (remove the current question)
        session(['exam_questions_' . $this->category->id => $this->remainingQuestions]);

        \Log::channel('exam')->info('Next question loaded', [
            'question_id' => $this->currentQuestion->id,
            'remaining_questions' => count($this->remainingQuestions),
            'total_questions_answered' => $this->totalQuestions - count($this->remainingQuestions),
        ]);
    }

    public function updatedSelectedAnswer()
    {
        if ($this->selectedAnswer && $this->currentQuestion) {
            $this->answers[$this->currentQuestion->id] = $this->selectedAnswer;
            $this->saveAnswers();
        }
    }

    public function nextQuestion()
    {
        // Save current answer before moving to next question
        $this->saveAnswers();

        // Mark current question as answered and remove from session
        if ($this->currentQuestion) {
            \Log::channel('exam')->info('Question answered and removed from session', [
                'question_id' => $this->currentQuestion->id,
                'answer' => $this->selectedAnswer,
            ]);
        }

        // Load next question from session (this will remove current question)
        $this->loadNextQuestion();

        if ($this->currentQuestion) {
            $this->currentQuestionIndex++;
            $this->selectedAnswer = $this->answers[$this->currentQuestion->id] ?? null;
        } else {
            // No more questions, show submit modal
            \Log::channel('exam')->info('All questions completed, showing submit modal');
            $this->showSubmitModal = true;
        }
    }

    public function previousQuestion()
    {
        if ($this->currentQuestionIndex > 0) {
            $this->currentQuestionIndex--;
            $this->currentQuestion = $this->questions[$this->currentQuestionIndex];
            $this->selectedAnswer = $this->answers[$this->currentQuestion->id] ?? null;
        }
    }

    public function skipQuestion()
    {
        if (!$this->currentQuestion) {
            return;
        }

        // Mark as skipped (null answer) and save
        $this->answers[$this->currentQuestion->id] = null;
        $this->saveAnswers();
        $this->selectedAnswer = null;

        \Log::channel('exam')->info('Question skipped and removed from session', [
            'question_id' => $this->currentQuestion->id,
        ]);

        // Load next question from session (this will remove current question)
        $this->loadNextQuestion();

        if ($this->currentQuestion) {
            $this->currentQuestionIndex++;
        } else {
            // No more questions, show submit modal
            \Log::channel('exam')->info('All questions completed after skip, showing submit modal');
            $this->showSubmitModal = true;
        }
    }

    public function goToQuestion($index)
    {
        if ($this->questions && $index >= 0 && $index < $this->questions->count()) {
            $this->currentQuestionIndex = $index;
            $this->currentQuestion = $this->questions[$index];
            $this->selectedAnswer = $this->answers[$this->currentQuestion->id] ?? null;
        }
    }

    public function saveAnswers()
    {
        $this->examStudent->update(['answers' => $this->answers]);
    }

    public function submitExam()
    {
        $this->isLoading = true;

        try {
            // Save final answers
            $this->saveAnswers();

            // Create exam result (don't calculate score, just mark as not declared)
            $this->examStudent->examResults()->create([
                'exam_id' => $this->exam->id,
                'student_id' => $this->examStudent->student_id,
                'score' => 0, // Don't show grades
                'result_status' => ExamResultStatusEnum::NotDeclared->value,
                'declared_at' => null,
                'data' => [
                    'category_id' => $this->category->id,
                    'total_questions' => $this->questions->count(),
                    'answered_questions' => count(array_filter($this->answers)),
                    'skipped_questions' => count(array_filter($this->answers, fn($answer) => $answer === null)),
                    'exam_duration' => $this->examStartTime->diffInMinutes(now()),
                    'manually_submitted' => true,
                ],
            ]);

            $this->success('Exam submitted successfully!', redirectTo: route('frontend.exam.index'));
        } catch (\Exception $e) {
            $this->error('An error occurred while submitting the exam. Please try again.');
            $this->isLoading = false;
        }
    }

    public function calculateScore()
    {
        $correctAnswers = 0;
        $totalQuestions = $this->questions->count();

        foreach ($this->questions as $question) {
            $answer = $this->answers[$question->id] ?? null;
            if ($answer && $answer == $question->correct_option_id) {
                $correctAnswers++;
            }
        }

        return ($correctAnswers / $totalQuestions) * 100;
    }

    public function getAnsweredQuestionsCount()
    {
        return count(array_filter($this->answers, fn($answer) => $answer !== null));
    }

    public function getSkippedQuestionsCount()
    {
        return count(array_filter($this->answers, fn($answer) => $answer === null));
    }

    public function getTimeRemaining()
    {
        $examEndTime = $this->examStartTime->copy()->addMinutes($this->exam->duration);
        $remaining = $examEndTime->diffInSeconds(now());

        if ($remaining <= 0) {
            $this->autoSubmitExam();
            return 0;
        }

        return $remaining;
    }

    public function autoSubmitExam()
    {
        try {
            // Save final answers
            $this->saveAnswers();

            // Create exam result
            $this->examStudent->examResults()->create([
                'exam_id' => $this->exam->id,
                'student_id' => $this->examStudent->student_id,
                'score' => 0, // Don't calculate score for auto-submit
                'result_status' => ExamResultStatusEnum::NotDeclared->value,
                'declared_at' => null,
                'data' => [
                    'category_id' => $this->category->id,
                    'total_questions' => $this->questions->count(),
                    'answered_questions' => count(array_filter($this->answers)),
                    'skipped_questions' => count(array_filter($this->answers, fn($answer) => $answer === null)),
                    'exam_duration' => $this->examStartTime->diffInMinutes(now()),
                    'auto_submitted' => true,
                ],
            ]);

            $this->success('Time expired! Exam submitted automatically.', redirectTo: route('frontend.exam.index'));
        } catch (\Exception $e) {
            $this->error('An error occurred while submitting the exam.', redirectTo: route('frontend.exam.index'));
        }
    }

    public function logout()
    {
        session()->forget(['exam_student_id', 'exam_id', 'current_category_id', 'exam_start_time']);
        $this->success('Logged out successfully.', redirectTo: route('frontend.exam.login'));
    }
}; ?>

{{-- Debug: Component is rendering --}}
<div class="bg-green-100 p-4 text-center">
    <strong>SUCCESS:</strong> Exam component is rendering! Questions: {{ $totalQuestions }} (Remaining:
    {{ count($remainingQuestions) }}, Answered: {{ $this->getAnsweredQuestionsCount() }})
    <br>
    Category: {{ $category->name ?? 'Unknown' }}
    <br>
    Current Question: {{ $currentQuestion->id ?? 'None' }}
</div>

<div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100">
    <!-- Header -->
    <div class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-4">
                <div>
                    <h1 class="text-xl font-bold text-gray-900">{{ $category->name ?? 'Exam' }}</h1>
                    <p class="text-sm text-gray-600">Question {{ $currentQuestionIndex + 1 }} of
                        {{ $totalQuestions }}</p>
                </div>
                <div class="flex items-center gap-4">
                    <div class="text-right">
                        <div class="text-sm text-gray-600">Time Remaining</div>
                        <div class="font-mono text-lg font-semibold text-red-600"
                            wire:poll.1s="timeRemaining = getTimeRemaining()">
                            @if (isset($this->exam) && isset($this->examStartTime))
                                {{ gmdate('H:i:s', $this->getTimeRemaining()) }}
                            @else
                                00:00:00
                            @endif
                        </div>
                    </div>
                    <button wire:click="logout"
                        class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg transition duration-200 flex items-center gap-2">
                        <x-icon name="o-arrow-right-on-rectangle" class="w-4 h-4" />
                        Exit
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
            <!-- Question Navigation -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-xl shadow-lg p-6 sticky top-8">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Question Navigation</h3>

                    <!-- Progress Stats -->
                    <div class="mb-6 space-y-3">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Answered:</span>
                            <span class="font-semibold text-green-600">{{ $this->getAnsweredQuestionsCount() }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Skipped:</span>
                            <span class="font-semibold text-yellow-600">{{ $this->getSkippedQuestionsCount() }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Remaining:</span>
                            <span
                                class="font-semibold text-gray-600">{{ ($questions ? $questions->count() : 0) - count($answers) }}</span>
                        </div>
                    </div>

                    <!-- Question Grid -->
                    <div class="grid grid-cols-5 gap-2">
                        @foreach ($questions as $index => $question)
                            <button wire:click="goToQuestion({{ $index }})"
                                class="w-8 h-8 rounded-lg text-sm font-medium transition duration-200
                                    {{ $index === $currentQuestionIndex ? 'bg-blue-600 text-white' : '' }}
                                    {{ isset($answers[$question->id]) && $answers[$question->id] !== null ? 'bg-green-100 text-green-800' : '' }}
                                    {{ isset($answers[$question->id]) && $answers[$question->id] === null ? 'bg-yellow-100 text-yellow-800' : '' }}
                                    {{ !isset($answers[$question->id]) ? 'bg-gray-100 text-gray-600 hover:bg-gray-200' : '' }}">
                                {{ $index + 1 }}
                            </button>
                        @endforeach
                    </div>

                    <!-- Submit Button -->
                    <X-button wire:click="$set('showSubmitModal', true)" label="Submit Exam" icon="o-check-circle"
                        class="w-full mt-6 bg-green-600 hover:bg-green-700 text-white py-3 px-4 rounded-lg font-semibold transition duration-200 flex items-center justify-center gap-2" />
                </div>
            </div>

            <!-- Question Content -->
            <div class="lg:col-span-3">
                <div class="bg-white rounded-xl shadow-lg p-8">
                    <!-- Question -->
                    <div class="mb-8">
                        <h2 class="text-xl font-semibold text-gray-900 mb-4">
                            Question {{ $currentQuestionIndex + 1 }}
                        </h2>
                        <div class="text-lg text-gray-700 leading-relaxed">
                            {{ $currentQuestion->question_text ?? 'No question available' }}
                        </div>
                    </div>

                    <!-- Options -->
                    <div class="space-y-4 mb-8">
                        @if ($currentQuestion && $currentQuestion->options)
                            @foreach ($currentQuestion->options as $option)
                                <label
                                    class="flex items-start gap-3 p-4 border border-gray-200 rounded-lg hover:border-blue-300 hover:bg-blue-50 transition duration-200 cursor-pointer">
                                    <input type="radio" wire:model="selectedAnswer" value="{{ $option->id }}"
                                        class="mt-1 w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500" />
                                    <div class="flex-1">
                                        <span class="text-gray-700">{{ $option->option_text }}</span>
                                    </div>
                                </label>
                            @endforeach
                        @else
                            <div class="text-center py-8 text-gray-500">
                                No question available at this time.
                            </div>
                        @endif
                    </div>

                    <!-- Navigation Buttons -->
                    <div class="flex justify-between items-center">
                        <button wire:click="previousQuestion"
                            class="flex items-center gap-2 px-6 py-3 bg-gray-600 hover:bg-gray-700 text-white rounded-lg font-semibold transition duration-200 {{ $currentQuestionIndex === 0 ? 'opacity-50 cursor-not-allowed' : '' }}"
                            {{ $currentQuestionIndex === 0 ? 'disabled' : '' }}>
                            <x-icon name="o-chevron-left" class="w-4 h-4" />
                            Previous
                        </button>

                        <div class="flex gap-3">
                            <button wire:click="skipQuestion"
                                class="flex items-center gap-2 px-6 py-3 bg-yellow-600 hover:bg-yellow-700 text-white rounded-lg font-semibold transition duration-200">
                                <x-icon name="o-forward" class="w-4 h-4" />
                                Skip
                            </button>

                            <button wire:click="nextQuestion"
                                class="flex items-center gap-2 px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-semibold transition duration-200 {{ $currentQuestionIndex === ($questions ? $questions->count() : 0) - 1 ? 'opacity-50 cursor-not-allowed' : '' }}"
                                {{ $currentQuestionIndex === ($questions ? $questions->count() : 0) - 1 ? 'disabled' : '' }}>
                                Next
                                <x-icon name="o-chevron-right" class="w-4 h-4" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Submit Confirmation Modal -->
    @if ($showSubmitModal)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-xl shadow-2xl p-8 max-w-md w-full mx-4">
                <div class="text-center">
                    <div class="mx-auto w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mb-4">
                        <x-icon name="o-check-circle" class="w-8 h-8 text-green-600" />
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">Submit Exam?</h3>
                    <p class="text-gray-600 mb-6">
                        Are you sure you want to submit your exam? This action cannot be undone.
                    </p>

                    <div class="bg-gray-50 rounded-lg p-4 mb-6">
                        <div class="text-sm text-gray-600 space-y-1">
                            <div>Answered: {{ $this->getAnsweredQuestionsCount() }} questions</div>
                            <div>Skipped: {{ $this->getSkippedQuestionsCount() }} questions</div>
                            <div>Total: {{ $questions ? $questions->count() : 0 }} questions</div>
                        </div>
                    </div>

                    <div class="flex gap-3">
                        <button wire:click="$set('showSubmitModal', false)"
                            class="flex-1 px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-700 rounded-lg font-semibold transition duration-200">
                            Cancel
                        </button>
                        <button wire:click="submitExam"
                            class="flex-1 px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-semibold transition duration-200 flex items-center justify-center gap-2"
                            wire:loading.attr="disabled">
                            <span wire:loading.remove>Submit Exam</span>
                            <span wire:loading class="flex items-center">
                                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white"
                                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10"
                                        stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                    </path>
                                </svg>
                                Submitting...
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
