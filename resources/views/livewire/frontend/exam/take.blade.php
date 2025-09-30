<?php

use Carbon\Carbon;
use Mary\Traits\Toast;
use Illuminate\View\View;
use Livewire\Volt\Component;
use App\Enums\ExamResultStatusEnum;
use App\Enums\ExamStatusEnum;
use Livewire\Attributes\{Title, Layout};
use App\Models\{Exam, ExamStudent, ExamCategory, Question, Option, Category, ExamResult};

new class extends Component {
    use Toast;

    #[Layout('components.layouts.guest')]
    #[Title('Taking Exam')]
    public $exam;
    public $category;
    public $examCategory;
    public $examStudent;
    public $questions;
    public $availableQuestions = [];
    public $currentQuestion;
    public $answers = [];
    public $selectedAnswer = null;
    public $totalQuestions = 0;
    public $totalPoints = 0;
    public $timeRemaining;
    public $examStartTime;
    public $isLoading = false;
    public $showSubmitModal = false;
    public $showExitModal = false;
    public $showNavigation = false;
    public $autoSubmitTimer = 5; // Auto-submit after 5 seconds
    public $questionHistory = [];
    public $currentQuestionNumber = 1;
    public $questionSequence = [];
    public $currentSequenceIndex = 0;
    public $navigationMode = false; // Track if we're in navigation mode

    public function mount($exam_id, $slug)
    {
        // Load exam directly using exam_id from route
        $this->exam = Exam::where('exam_id', $exam_id)
            ->with(['course', 'examCategories.category'])
            ->first();

        if (!$this->exam) {
            $this->error('Exam not found.', redirectTo: route('frontend.exam.index'));
            return;
        }

        // Load category using slug
        $this->category = Category::where('slug', $slug)->first();

        if (!$this->category) {
            $this->error('Category not found.', redirectTo: route('frontend.exam.index'));
            return;
        }

        // Load exam category (relationship between exam and category)
        $this->examCategory = $this->exam->examCategories->where('category_id', $this->category->id)->first();

        if (!$this->examCategory) {
            $this->error('This category is not part of this exam.', redirectTo: route('frontend.exam.index'));
            return;
        }

        // Load questions for this category
        $this->questions = $this->category->questions()->with('options')->get();

        if ($this->questions->isEmpty()) {
            $this->error('No questions available for this category.', redirectTo: route('frontend.exam.index'));
            return;
        }

        // Load or create ExamStudent record
        $this->loadExamStudent();

        // Initialize exam
        $this->initializeExam();
    }

    public function loadExamStudent()
    {
        // Get the exam student ID from session (set during login)
        $examStudentId = session('exam_student_id');

        if (!$examStudentId) {
            $this->error('Session expired. Please login again.', redirectTo: route('frontend.exam.login'));
            return;
        }

        // Load the exam student record
        $this->examStudent = ExamStudent::with('student')->find($examStudentId);

        if (!$this->examStudent) {
            $this->error('Exam student not found. Please login again.', redirectTo: route('frontend.exam.login'));
            return;
        }

        // Verify this exam student belongs to the current exam
        if ($this->examStudent->exam_id !== $this->exam->id) {
            $this->error('Invalid exam access.', redirectTo: route('frontend.exam.index'));
            return;
        }
    }

    public function initializeExam()
    {
        // Get total points for this category
        $this->totalPoints = $this->examCategory->total_points ?? 0;

        // Calculate how many questions we need based on total points
        $this->calculateQuestionCount();

        // Check if exam state exists in session
        $sessionKey = 'exam_state_' . $this->exam->id . '_' . $this->category->id;
        $examState = session($sessionKey);

        if ($examState) {
            // Restore exam state from session
            $this->availableQuestions = $examState['available_questions'] ?? [];
            $this->answers = $examState['answers'] ?? [];
            $this->questionHistory = $examState['question_history'] ?? [];
            $this->questionSequence = $examState['question_sequence'] ?? [];
            $this->currentQuestionNumber = $examState['current_question_number'] ?? 1;
            $this->currentSequenceIndex = $examState['current_sequence_index'] ?? 0;
            $this->navigationMode = $examState['navigation_mode'] ?? false;
            $this->showNavigation = $examState['show_navigation'] ?? false;
            $this->examStartTime = $examState['exam_start_time'] ? Carbon::parse($examState['exam_start_time']) : now();

            // Load current question if exists
            if ($examState['current_question_id'] ?? false) {
                $this->loadQuestionById($examState['current_question_id']);
            } else {
                $this->loadNextRandomQuestion();
            }
        } else {
            // Initialize new exam state
            $this->availableQuestions = $this->questions
                ->shuffle()
                ->map(function ($question) {
                    $questionArray = $question->toArray();
                    $questionArray['options'] = $question->options->toArray();
                    return $questionArray;
                })
                ->values()
                ->toArray();
            $this->examStartTime = now();
            $this->currentQuestionNumber = 1;
            $this->currentSequenceIndex = -1; // Start at -1 so first question becomes index 0
            $this->showNavigation = false;
            $this->navigationMode = false;

            // Save initial state
            $this->saveExamState();

            // Load first question
            $this->loadNextRandomQuestion();
        }
    }

    public function calculateQuestionCount()
    {
        // Find the most common point value among questions
        $pointCounts = $this->questions->groupBy('points')->map->count();
        $mostCommonPoints = $pointCounts->keys()->sort()->first();

        // Use only the available questions (no repetition)
        $availableQuestionsCount = $this->questions->where('points', $mostCommonPoints)->count();

        // Set total questions to available count
        $this->totalQuestions = $availableQuestionsCount;

        // Calculate actual total points based on available questions
        $this->totalPoints = $availableQuestionsCount * $mostCommonPoints;

        // Use only unique questions (no repetition)
    }

    public function saveExamState()
    {
        $sessionKey = 'exam_state_' . $this->exam->id . '_' . $this->category->id;

        $examState = [
            'available_questions' => $this->availableQuestions,
            'answers' => $this->answers,
            'question_history' => $this->questionHistory,
            'question_sequence' => $this->questionSequence,
            'current_question_number' => $this->currentQuestionNumber,
            'current_sequence_index' => $this->currentSequenceIndex,
            'navigation_mode' => $this->navigationMode,
            'show_navigation' => $this->showNavigation,
            'exam_start_time' => $this->examStartTime->toDateTimeString(),
            'current_question_id' => $this->currentQuestion->id ?? null,
        ];

        session([$sessionKey => $examState]);
    }

    public function loadNextRandomQuestion()
    {
        // Check if we've reached the total number of questions
        if ($this->currentQuestionNumber > $this->totalQuestions) {
            $this->showSubmitModal = true;
            return;
        }

        // Find next available question that hasn't been answered        $availableQuestion = null;

        foreach ($this->availableQuestions as $index => $questionData) {
            if (!isset($this->answers[$questionData['id']])) {
                $availableQuestion = $questionData;
                unset($this->availableQuestions[$index]);
                break;
            }
        }

        if ($availableQuestion) {
            $this->loadQuestionFromData($availableQuestion);
        } else {
            // No more questions available - exam completed
            $this->showSubmitModal = true;
        }
    }

    public function loadQuestionFromData($questionData)
    {
        // Convert question to object
        $this->currentQuestion = (object) $questionData;

        // Convert options to objects if they exist
        if (isset($questionData['options']) && is_array($questionData['options'])) {
            $this->currentQuestion->options = collect($questionData['options'])->map(function ($option) {
                return (object) $option;
            });
        }

        // Check if already answered (for navigation)
        $this->selectedAnswer = $this->answers[$this->currentQuestion->id] ?? null;

        // Add to question history for navigation
        if (!in_array($this->currentQuestion->id, $this->questionHistory)) {
            $this->questionHistory[] = $this->currentQuestion->id;
        }

        // Update sequence index if this is a new question in sequence
        if (!in_array($this->currentQuestion->id, $this->questionSequence)) {
            $this->questionSequence[] = $this->currentQuestion->id;
            $this->currentSequenceIndex = count($this->questionSequence) - 1;
        } else {
            // Find the index of this question in the sequence
            $this->currentSequenceIndex = array_search($this->currentQuestion->id, $this->questionSequence);
        }

        // Save state after loading question
        $this->saveExamState();
    }

    public function loadQuestionById($questionId)
    {
        // Find question in history
        if (in_array($questionId, $this->questionHistory)) {
            // Find the original question data from the questions collection
            $originalQuestion = $this->questions->where('id', $questionId)->first();
            if ($originalQuestion) {
                $questionData = $originalQuestion->toArray();
                $questionData['options'] = $originalQuestion->options->toArray();
                $this->loadQuestionFromData($questionData);
            }
        }
    }

    public function nextQuestion()
    {
        $this->saveAnswer();

        // Show navigation after first question is answered
        if (!$this->showNavigation) {
            $this->showNavigation = true;
        }

        if ($this->navigationMode) {
            // In navigation mode, continue from next logical question
            $this->continueFromNavigation();
        } else {
            // Normal sequence mode
            $this->continueSequence();
        }
    }

    public function continueSequence()
    {
        // Increment question number first
        $this->currentQuestionNumber++;

        // Check if we've reached the required number of questions AFTER incrementing
        if ($this->currentQuestionNumber > $this->totalQuestions) {
            $this->showSubmitModal = true;
            return;
        }

        // Check if we have more questions in sequence
        if ($this->currentSequenceIndex + 1 < count($this->questionSequence)) {
            // Move to next question in sequence
            $this->currentSequenceIndex++;
            $nextQuestionId = $this->questionSequence[$this->currentSequenceIndex];
            $this->loadQuestionById($nextQuestionId);
        } else {
            // No more questions in sequence, load next random question
            $this->saveExamState();
            $this->loadNextRandomQuestion();
        }
    }

    public function continueFromNavigation()
    {
        // Increment question number first
        $this->currentQuestionNumber++;

        // Check if we've reached the required number of questions AFTER incrementing
        if ($this->currentQuestionNumber > $this->totalQuestions) {
            $this->showSubmitModal = true;
            return;
        }

        // Find the current question's position in the sequence
        $currentIndex = array_search($this->currentQuestion->id, $this->questionSequence);

        if ($currentIndex !== false && $currentIndex + 1 < count($this->questionSequence)) {
            // Continue from the next question in the original sequence
            $nextQuestionId = $this->questionSequence[$currentIndex + 1];
            $this->currentSequenceIndex = $currentIndex + 1;
            $this->loadQuestionById($nextQuestionId);
        } else {
            // No more questions in sequence, load next random question
            $this->saveExamState();
            $this->loadNextRandomQuestion();
        }

        // Exit navigation mode after continuing
        $this->navigationMode = false;
    }

    public function skipQuestion()
    {
        // Mark as skipped (null answer)
        $this->answers[$this->currentQuestion->id] = null;
        $this->selectedAnswer = null;

        // Save exam state and auto-save to database
        $this->saveExamState();
        $this->autoSaveToDatabase();

        // Show navigation after first question is skipped
        if (!$this->showNavigation) {
            $this->showNavigation = true;
        }

        if ($this->navigationMode) {
            // In navigation mode, continue from next logical question
            $this->continueFromNavigation();
        } else {
            // Normal sequence mode
            $this->continueSequence();
        }
    }

    public function previousQuestion()
    {
        // Save current answer before moving
        $this->saveAnswer();

        // Show navigation after first question interaction
        if (!$this->showNavigation) {
            $this->showNavigation = true;
        }

        // Check if we can go to previous question
        if ($this->currentQuestionNumber > 1) {
            $this->currentQuestionNumber--;

            // Find the previous question in sequence
            $currentIndex = array_search($this->currentQuestion->id, $this->questionSequence);
            if ($currentIndex !== false && $currentIndex > 0) {
                $previousQuestionId = $this->questionSequence[$currentIndex - 1];
                $this->currentSequenceIndex = $currentIndex - 1;
                $this->loadQuestionById($previousQuestionId);
            }
        }
    }

    public function goToQuestion($questionId)
    {
        // Allow navigation to any question that has been encountered (answered or skipped)
        if (in_array($questionId, $this->questionHistory)) {
            $this->navigationMode = true; // Set navigation mode
            $this->loadQuestionById($questionId);
        }
    }

    public function goToQuestionByNumber($questionNumber)
    {
        // Navigate to a specific question number (1-based)
        if ($questionNumber >= 1 && $questionNumber <= $this->totalQuestions) {
            // If this question has been encountered, load it directly
            if (isset($this->questionSequence[$questionNumber - 1])) {
                $this->navigationMode = true; // Set navigation mode
                $questionId = $this->questionSequence[$questionNumber - 1];
                $this->loadQuestionById($questionId);
                $this->currentQuestionNumber = $questionNumber; // Update question number
            }
            // If not encountered yet, do nothing (button will be disabled)
        }
    }

    public function saveAnswer()
    {
        if ($this->currentQuestion) {
            $this->answers[$this->currentQuestion->id] = $this->selectedAnswer;
            $this->saveExamState();

            // Auto-save to database
            $this->autoSaveToDatabase();
        }
    }

    public function autoSaveToDatabase()
    {
        try {
            if (!$this->examStudent) {
                return;
            }

            $answersData = $this->prepareAnswersData();
            $this->examStudent->update([
                'answers' => json_encode($answersData),
                'status' => ExamStatusEnum::SCHEDULED->value,
            ]);
        } catch (\Exception $e) {
        }
    }

    public function updatedSelectedAnswer()
    {
        // Save answer whenever user selects an option
        if ($this->currentQuestion) {
            $this->saveAnswer();
        }
    }

    public function submitExam()
    {
        $this->isLoading = true;

        try {
            // Check if examStudent exists
            if (!$this->examStudent) {
                throw new \Exception('ExamStudent record not found');
            }

            // Prepare answers data in the new structure
            $answersData = $this->prepareAnswersData();

            // Save to ExamStudent answers column
            $this->examStudent->update([
                'answers' => json_encode($answersData), // Ensure it's JSON encoded
            ]);

            // Create ExamResult record for better tracking
            ExamResult::updateOrCreate(
                [
                    'exam_id' => $this->exam->id,
                    'student_id' => $this->examStudent->student_id,
                    'category_id' => $this->category->id,
                ],
                [
                    'score' => $answersData['points_earned'], // Use points_earned as score
                    'declared_by' => null, // Will be set by admin/center when they declare results
                    'category_slug' => $this->category->slug,
                    'answers_data' => $answersData,
                    'total_questions' => $answersData['total_questions'],
                    'answered_questions' => $answersData['answered_questions'],
                    'skipped_questions' => $answersData['skipped_questions'],
                    'total_points' => $answersData['total_points'],
                    'points_earned' => $answersData['points_earned'],
                    'percentage' => (float) $answersData['percentage'],
                    'result' => $answersData['result'],
                    'exam_duration' => $answersData['exam_duration'],
                    'time_taken_minutes' => (float) $answersData['time_taken_minutes'],
                    'submitted_at' => now(),
                ],
            );

            // Clear exam state from session
            $this->clearExamState();

            $this->success('Exam submitted successfully!', redirectTo: route('frontend.exam.index'));
        } catch (\Exception $e) {
            $this->error('Failed to submit exam. Please try again.');
            $this->isLoading = false;
        }
    }

    public function prepareAnswersData()
    {
        $totalPointsEarned = 0;
        $totalPoints = 0;
        $answeredQuestions = 0;

        // Prepare answers data in flat structure
        $answersData = [
            'category_slug' => $this->category->slug,
            'category_total_points' => $this->examCategory->total_points ?? 0,
            'category_passing_points' => $this->examCategory->passing_points ?? 0,
        ];

        foreach ($this->answers as $questionId => $selectedAnswer) {
            // Find the question
            $question = $this->questions->firstWhere('id', $questionId);
            if (!$question) {
                continue;
            }

            $totalPoints += $question->points;

            // Determine if answer is correct
            $isCorrect = false;
            $pointsEarned = 0;
            $correctAnswerId = null;

            if ($selectedAnswer !== null) {
                $answeredQuestions++;
                // Get the correct answer ID from the question
                $correctAnswerId = $question->correct_option_id;
                $isCorrect = $correctAnswerId == $selectedAnswer;
                $pointsEarned = $isCorrect ? $question->points : 0;
            }

            $totalPointsEarned += $pointsEarned;

            // Store each question's data directly in the main structure
            $answersData["question_{$questionId}"] = [
                'question_id' => $question->id,
                'question' => $question->question_text,
                'answer' => $selectedAnswer,
                'correct_answer' => $correctAnswerId,
                'point' => $question->points,
                'point_earned' => $pointsEarned,
                'answered_at' => now()->toDateTimeString(),
                'options' => $question->options
                    ->map(function ($option) use ($question) {
                        return [
                            'id' => $option->id,
                            'option_text' => $option->option_text,
                            'is_correct' => $question->correct_option_id == $option->id,
                        ];
                    })
                    ->toArray(),
            ];
        }

        // Calculate skipped questions: total questions - answered questions
        $skippedQuestions = $this->totalQuestions - $answeredQuestions;

        // Calculate overall result using exam category passing points
        $passingPoints = $this->examCategory->passing_points ?? $totalPoints * 0.6; // Default 60%
        $percentage = $totalPoints > 0 ? ($totalPointsEarned / $totalPoints) * 100 : 0;
        $result = $totalPointsEarned >= $passingPoints ? ExamResultStatusEnum::Passed->value : ExamResultStatusEnum::Failed->value;

        // Add summary information
        $answersData['total_questions'] = $this->totalQuestions;
        $answersData['answered_questions'] = $answeredQuestions;
        $answersData['skipped_questions'] = $skippedQuestions;
        $answersData['total_points'] = $totalPoints;
        $answersData['points_earned'] = $totalPointsEarned;
        $answersData['percentage'] = round($percentage, 2);
        $answersData['result'] = $result;
        $answersData['submitted_at'] = now()->toDateTimeString();
        $answersData['exam_duration'] = $this->examCategory->duration ?? ($this->exam->duration ?? 60);
        $answersData['time_taken_minutes'] = $this->examStartTime ? $this->examStartTime->diffInMinutes(now()) : 0;

        return $answersData;
    }

    public function clearExamState()
    {
        $sessionKey = 'exam_state_' . $this->exam->id . '_' . $this->category->id;
        session()->forget($sessionKey);
    }

    public function showExitConfirmation()
    {
        $this->showExitModal = true;
    }

    public function confirmExitExam()
    {
        $this->isLoading = true;

        try {
            // Check if examStudent exists
            if (!$this->examStudent) {
                throw new \Exception('ExamStudent record not found');
            }

            // Prepare answers data in the new structure
            $answersData = $this->prepareAnswersData();

            // Save to ExamStudent answers column
            $this->examStudent->update([
                'answers' => json_encode($answersData), // Ensure it's JSON encoded
            ]);

            // Create ExamResult record for better tracking
            ExamResult::updateOrCreate(
                [
                    'exam_id' => $this->exam->id,
                    'student_id' => $this->examStudent->student_id,
                    'category_id' => $this->category->id,
                ],
                [
                    'score' => $answersData['points_earned'], // Use points_earned as score
                    'declared_by' => null, // Will be set by admin/center when they declare results
                    'category_slug' => $this->category->slug,
                    'answers_data' => $answersData,
                    'total_questions' => $answersData['total_questions'],
                    'answered_questions' => $answersData['answered_questions'],
                    'skipped_questions' => $answersData['skipped_questions'],
                    'total_points' => $answersData['total_points'],
                    'points_earned' => $answersData['points_earned'],
                    'percentage' => (float) $answersData['percentage'],
                    'result' => $answersData['result'],
                    'exam_duration' => $answersData['exam_duration'],
                    'time_taken_minutes' => (float) $answersData['time_taken_minutes'],
                    'submitted_at' => now(),
                ],
            );

            // Clear exam state from session
            $this->clearExamState();

            $this->success('Exam submitted successfully!', redirectTo: route('frontend.exam.index'));
        } catch (\Exception $e) {
            $this->error('Failed to submit exam. Please try again.');
            $this->isLoading = false;
        }
    }

    public function cancelExit()
    {
        $this->showExitModal = false;
    }

    public function getTimeRemaining()
    {
        if (!$this->examStartTime || !$this->exam) {
            return 0;
        }

        // Use exam category duration if available, otherwise use exam duration
        $duration = $this->examCategory->duration ?? ($this->exam->duration ?? 60);

        $examEndTime = $this->examStartTime->copy()->addMinutes($duration);
        $now = now();

        // Calculate remaining seconds (countdown)
        if ($now->greaterThan($examEndTime)) {
            $remaining = 0;
        } else {
            // Use timestamp difference for more reliable calculation
            $remaining = $examEndTime->timestamp - $now->timestamp;
            $remaining = max(0, $remaining); // Ensure non-negative
        }

        // Auto-submit when time runs out
        if ($remaining === 0 && !$this->showSubmitModal) {
            $this->showSubmitModal = true;
        }

        return $remaining;
    }

    public function getFormattedTimeRemaining()
    {
        $seconds = $this->getTimeRemaining();
        return gmdate('H:i:s', $seconds);
    }

    public function getFormattedCountdown()
    {
        $seconds = $this->getTimeRemaining();
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $remainingSeconds = $seconds % 60;

        return sprintf('%02d:%02d:%02d', $hours, $minutes, $remainingSeconds);
    }

    public function getTimeRemainingInMinutes()
    {
        return round($this->getTimeRemaining() / 60, 1);
    }

    public function isTimeRunningLow()
    {
        $remaining = $this->getTimeRemaining();
        $duration = $this->examCategory->duration ?? ($this->exam->duration ?? 60);
        $remainingMinutes = $remaining / 60;

        // Show warning when less than 10% of time remains
        return $remainingMinutes <= $duration * 0.1;
    }

    public function isTimeCritical()
    {
        $remainingMinutes = $this->getTimeRemaining() / 60;

        // Show critical warning when less than 5 minutes remain
        return $remainingMinutes <= 5;
    }

    public function checkTimeExpired()
    {
        $remaining = $this->getTimeRemaining();

        if ($remaining === 0 && !$this->showSubmitModal && !$this->showExitModal) {
            // Time has expired, show submit modal
            \Log::channel('exam')->info('Time expired, showing submit modal');
            $this->showSubmitModal = true;
            $this->dispatch('start-auto-submit-timer');
        }
    }

    public function startAutoSubmitTimer()
    {
        // Start countdown for auto-submit
        $this->dispatch('auto-submit-countdown', ['seconds' => $this->autoSubmitTimer]);
    }

    public function getTimeProgressPercentage()
    {
        if (!$this->examStartTime || !$this->exam) {
            return 0;
        }

        $duration = $this->examCategory->duration ?? ($this->exam->duration ?? 60);
        $totalSeconds = $duration * 60;
        $remainingSeconds = $this->getTimeRemaining();
        $elapsedSeconds = $totalSeconds - $remainingSeconds;

        return min(100, max(0, ($elapsedSeconds / $totalSeconds) * 100));
    }

    public function getAnsweredQuestionsCount()
    {
        return count(array_filter($this->answers, fn($answer) => $answer !== null));
    }

    public function getSkippedQuestionsCount()
    {
        return count(array_filter($this->answers, fn($answer) => $answer === null));
    }

    public function getExamStats()
    {
        return [
            'answered' => $this->getAnsweredQuestionsCount(),
            'skipped' => $this->getSkippedQuestionsCount(),
            'total' => $this->totalQuestions,
            'points' => $this->totalPoints,
        ];
    }

    public function getQuestionInfo()
    {
        $pointCounts = $this->questions->groupBy('points')->map->count();
        $mostCommonPoints = $pointCounts->keys()->sort()->first();
        $availableCount = $this->questions->where('points', $mostCommonPoints)->count();

        return "Using {$availableCount} unique questions for {$this->totalPoints} total points";
    }

    public function getQuestionButtonClass($status, $canNavigate)
    {
        return match ($status) {
            'current' => 'bg-primary text-white',
            'answered' => 'bg-success text-white',
            'skipped' => 'bg-warning text-white',
            'not-answered' => 'bg-blue-100 text-blue-600 hover:bg-blue-200',
            default => $canNavigate ? 'bg-gray-200 text-gray-400' : 'bg-gray-200 text-gray-400 cursor-not-allowed',
        };
    }

    public function getNavigationButtonClass($type, $disabled = false)
    {
        $baseClasses = 'flex items-center gap-2 px-6 py-3 rounded-lg font-semibold transition duration-200';

        return match ($type) {
            'previous' => $baseClasses . ' bg-gray-600 hover:bg-gray-700 text-white',
            'skip' => $baseClasses . ' bg-yellow-600 hover:bg-yellow-700 text-white',
            'next' => $baseClasses . ($disabled ? ' bg-gray-400 text-gray-200 cursor-not-allowed' : ' bg-blue-600 hover:bg-blue-700 text-white'),
            'submit' => $baseClasses . ' bg-green-600 hover:bg-green-700 text-white',
            'exit' => $baseClasses . ' bg-red-600 hover:bg-red-700 text-white',
            default => $baseClasses,
        };
    }

    public function getTimerDisplayClass()
    {
        $baseClasses = 'font-mono text-lg font-semibold transition-colors duration-300';

        if ($this->isTimeCritical()) {
            return $baseClasses . ' text-red-700 bg-red-100 px-2 py-1 rounded-lg animate-pulse border-2 border-red-300';
        } elseif ($this->isTimeRunningLow()) {
            return $baseClasses . ' text-red-600 bg-red-50 px-2 py-1 rounded-lg animate-pulse';
        }

        return $baseClasses . ' text-blue-600';
    }

    public function getProgressBarClass()
    {
        if ($this->isTimeCritical()) {
            return 'bg-red-600';
        } elseif ($this->isTimeRunningLow()) {
            return 'bg-red-500';
        }

        return 'bg-blue-500';
    }

    public function getExamDebugInfo()
    {
        $pointCounts = $this->questions->groupBy('points')->map->count();
        $mostCommonPoints = $pointCounts->keys()->sort()->first();
        $availableCount = $this->questions->where('points', $mostCommonPoints)->count();
        $requiredCount = $this->totalPoints / $mostCommonPoints;

        return [
            'total_points' => $this->totalPoints,
            'most_common_points' => $mostCommonPoints,
            'required_questions' => $requiredCount,
            'available_questions' => $availableCount,
            'current_question_number' => $this->currentQuestionNumber,
            'total_questions_set' => $this->totalQuestions,
            'questions_in_history' => count($this->questionHistory),
            'answers_count' => count($this->answers),
        ];
    }

    public function getExamStatus()
    {
        $pointCounts = $this->questions->groupBy('points')->map->count();
        $mostCommonPoints = $pointCounts->keys()->sort()->first();
        $availableCount = $this->questions->where('points', $mostCommonPoints)->count();
        $requiredCount = $this->totalPoints / $mostCommonPoints;

        $status = "Exam Status:\n";
        $status .= "Total Points Required: {$this->totalPoints}\n";
        $status .= "Most Common Point Value: {$mostCommonPoints}\n";
        $status .= "Questions Required: {$requiredCount}\n";
        $status .= "Questions Available: {$availableCount}\n";
        $status .= 'Mode: Unique Questions Only' . "\n";
        $status .= "Current Question: {$this->currentQuestionNumber}/{$this->totalQuestions}\n";

        $status .= "Using {$availableCount} questions for {$this->totalPoints} points\n";

        return $status;
    }

    public function getAllQuestionNumbers()
    {
        // Return array of question numbers from 1 to totalQuestions
        return range(1, $this->totalQuestions);
    }

    public function getQuestionStatus($questionNumber)
    {
        // Get the question ID for this question number
        $questionId = $this->questionSequence[$questionNumber - 1] ?? null;

        if (!$questionId) {
            return 'not-encountered'; // Question not yet encountered
        }

        if ($this->currentQuestion && $this->currentQuestion->id == $questionId) {
            return 'current'; // Currently viewing
        }

        if (isset($this->answers[$questionId])) {
            return $this->answers[$questionId] !== null ? 'answered' : 'skipped';
        }

        return 'not-answered'; // Encountered but not answered
    }

    public function canNavigateToQuestion($questionNumber)
    {
        // Check if we can navigate to this question number
        return isset($this->questionSequence[$questionNumber - 1]);
    }
}; ?>

<div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100">
    <!-- Header -->
    <div class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-4">
                <div>
                    <h1 class="text-xl font-bold text-gray-700">{{ $category->name ?? 'Exam' }}</h1>
                    <p class="text-sm text-gray-400">
                        Question {{ $currentQuestionNumber }} of {{ $this->totalQuestions }} • {{ $this->totalPoints }}
                        Points Total
                    </p>
                </div>
                <div class="flex items-center gap-4">
                    <div class="text-right">
                        <div class="{{ $this->getTimerDisplayClass() }}" wire:poll.1s="checkTimeExpired()">
                            {{ $this->getFormattedCountdown() }}
                        </div>

                        <!-- Time Progress Bar -->
                        <div class="mt-2 w-full bg-gray-200 rounded-full h-2">
                            <div class="h-2 rounded-full transition-all duration-1000 {{ $this->getProgressBarClass() }}"
                                style="width: {{ $this->getTimeProgressPercentage() }}%">
                            </div>
                        </div>
                    </div>
                    <button wire:click="showExitConfirmation" class="{{ $this->getNavigationButtonClass('exit') }}">
                        <x-icon name="o-arrow-right-on-rectangle" class="w-4 h-4" />
                        Exit Exam
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="grid grid-cols-1 {{ $showNavigation ? 'lg:grid-cols-4' : 'lg:grid-cols-1' }} gap-8">
            <!-- Question Navigation -->
            @if ($showNavigation)
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-xl shadow-lg p-6 sticky top-8">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Question Navigation</h3>

                        <!-- Progress Stats -->
                        <div class="mb-6 space-y-3">
                            @php $stats = $this->getExamStats(); @endphp
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Answered:</span>
                                <span class="font-semibold text-success">{{ $stats['answered'] }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Skipped:</span>
                                <span class="font-semibold text-warning">{{ $stats['skipped'] }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Total Points:</span>
                                <span class="font-semibold text-primary">{{ $stats['points'] }}</span>
                            </div>
                        </div>

                        <!-- Question Grid -->
                        <div class="grid grid-cols-5 gap-2 mb-6">
                            @foreach ($this->getAllQuestionNumbers() as $questionNumber)
                                @php
                                    $status = $this->getQuestionStatus($questionNumber);
                                    $canNavigate = $this->canNavigateToQuestion($questionNumber);
                                @endphp
                                <button wire:click="goToQuestionByNumber({{ $questionNumber }})"
                                    @disabled(!$canNavigate)
                                    class="w-8 h-8 rounded-lg text-sm font-medium transition duration-200 {{ $this->getQuestionButtonClass($status, $canNavigate) }}">
                                    {{ $questionNumber }}
                                </button>
                            @endforeach
                        </div>

                        <!-- Submit Button -->
                        <button wire:click="$set('showSubmitModal', true)"
                            class="w-full bg-green-600 hover:bg-green-700 text-white py-3 px-4 rounded-lg font-semibold transition duration-200 flex items-center justify-center gap-2">
                            <x-icon name="o-check-circle" class="w-4 h-4" />
                            Submit Exam
                        </button>
                    </div>
                </div>
            @endif

            <!-- Question Content -->
            <div class="{{ $showNavigation ? 'lg:col-span-3' : 'lg:col-span-1' }}">
                <div class="bg-white rounded-xl shadow-lg p-8">
                    <!-- Question -->
                    <div class="mb-8">
                        <h2 class="text-xl font-semibold text-gray-900 mb-4">
                            Question {{ $currentQuestionNumber }}
                        </h2>
                        <div class="text-lg text-gray-700 leading-relaxed">
                            {{ $currentQuestion->question_text ?? 'No question available' }}
                        </div>
                        @if ($currentQuestion && isset($currentQuestion->points))
                            <div class="mt-2 text-sm text-blue-600 font-medium">
                                Points: {{ $currentQuestion->points }}
                            </div>
                        @endif
                    </div>

                    <!-- Options -->
                    <div class="space-y-3 mb-8">
                        @if ($currentQuestion && isset($currentQuestion->options))
                            @foreach ($currentQuestion->options as $index => $option)
                                <div class="relative">
                                    <input type="radio" wire:model.live="selectedAnswer" value="{{ $option->id }}"
                                        id="option_{{ $option->id }}" class="sr-only peer" />
                                    <label for="option_{{ $option->id }}"
                                        class="flex items-center gap-4 p-5 border-2 border-gray-200 rounded-xl cursor-pointer transition-all duration-200 hover:border-blue-300 hover:shadow-md peer-checked:border-blue-500 peer-checked:bg-blue-50 peer-checked:shadow-lg">

                                        <!-- Custom Radio Button -->
                                        <div class="relative flex-shrink-0">
                                            <div
                                                class="w-6 h-6 border-2 border-gray-300 rounded-full flex items-center justify-center transition-all duration-200 peer-checked:border-blue-500 peer-checked:bg-blue-500">
                                                <div
                                                    class="w-3 h-3 bg-white rounded-full scale-0 transition-transform duration-200 peer-checked:scale-100">
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Option Letter -->
                                        <div
                                            class="flex-shrink-0 w-8 h-8 bg-gray-100 text-gray-600 rounded-full flex items-center justify-center font-semibold text-sm transition-all duration-200 peer-checked:bg-blue-500 peer-checked:text-white">
                                            {{ chr(65 + $index) }}
                                        </div>

                                        <!-- Option Text -->
                                        <div class="flex-1">
                                            <span
                                                class="text-gray-700 font-medium leading-relaxed">{{ $option->option_text }}</span>
                                        </div>

                                        <!-- Check Icon (for selected state) -->
                                        <div
                                            class="flex-shrink-0 opacity-0 peer-checked:opacity-100 transition-opacity duration-200">
                                            <svg class="w-5 h-5 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd"
                                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                                    clip-rule="evenodd"></path>
                                            </svg>
                                        </div>
                                    </label>
                                </div>
                            @endforeach
                        @else
                            <div class="text-center py-12 text-gray-500">
                                <div
                                    class="w-16 h-16 mx-auto mb-4 bg-gray-100 rounded-full flex items-center justify-center">
                                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z">
                                        </path>
                                    </svg>
                                </div>
                                <p class="text-lg font-medium">No question available</p>
                                <p class="text-sm text-gray-400 mt-1">Please try refreshing the page</p>
                            </div>
                        @endif
                    </div>

                    <!-- Navigation Buttons -->
                    <div class="flex justify-between items-center">
                        <!-- Previous Button -->
                        @if ($showNavigation && $currentQuestionNumber > 1)
                            <button wire:click="previousQuestion"
                                class="{{ $this->getNavigationButtonClass('previous') }}">
                                <x-icon name="o-chevron-left" class="w-4 h-4" />
                                Previous
                            </button>
                        @else
                            <div></div>
                        @endif

                        <div class="flex gap-3">
                            <!-- Skip Button - Only show when no option is selected -->
                            @if (!$selectedAnswer)
                                <button wire:click="skipQuestion"
                                    class="{{ $this->getNavigationButtonClass('skip') }}">
                                    <x-icon name="o-forward" class="w-4 h-4" />
                                    Skip
                                </button>
                            @endif

                            <!-- Next/Submit Button -->
                            @if ($currentQuestionNumber >= $totalQuestions)
                                <button wire:click="$set('showSubmitModal', true)"
                                    class="{{ $this->getNavigationButtonClass('submit') }}">
                                    <x-icon name="o-check-circle" class="w-4 h-4" />
                                    Submit Exam
                                </button>
                            @else
                                <button wire:click="nextQuestion" @disabled(!$selectedAnswer)
                                    class="{{ $this->getNavigationButtonClass('next', !$selectedAnswer) }}">
                                    Next
                                    <x-icon name="o-chevron-right" class="w-4 h-4" />
                                </button>
                            @endif
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

                    <!-- Auto-submit countdown -->
                    <div id="auto-submit-countdown"
                        class="hidden mb-4 p-3 bg-red-50 border border-red-200 rounded-lg">
                        <div class="flex items-center justify-center">
                            <div class="text-red-700 font-semibold">
                                Auto-submitting in <span id="countdown-timer">5</span> seconds...
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-50 rounded-lg p-4 mb-6">
                        <div class="text-sm text-gray-600 space-y-1">
                            @php $stats = $this->getExamStats(); @endphp
                            <div>Answered: {{ $stats['answered'] }} questions</div>
                            <div>Total: {{ $stats['total'] }} questions</div>
                            <div>Points: {{ $stats['points'] }}</div>
                        </div>
                    </div>

                    <div class="flex gap-3">
                        <button wire:click="$set('showSubmitModal', false)"
                            class="flex-1 px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-700 rounded-lg font-semibold transition duration-200">
                            Cancel
                        </button>
                        <button wire:click="submitExam"
                            class="flex-1 px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-semibold transition duration-200 flex items-center justify-center"
                            wire:loading.attr="disabled">
                            <span wire:loading.remove>Submit Exam</span>
                            <span wire:loading class="flex items-center justify-center">
                                <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg"
                                    fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10"
                                        stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                    </path>
                                </svg>
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Exit Confirmation Modal -->
    @if ($showExitModal)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-xl shadow-2xl p-8 max-w-md w-full mx-4">
                <div class="text-center">
                    <div class="mx-auto w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mb-4">
                        <x-icon name="o-exclamation-triangle" class="w-8 h-8 text-red-600" />
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">Exit Exam?</h3>
                    <p class="text-gray-600 mb-6">
                        Are you sure you want to exit? This will submit your exam with current answers and you won't be
                        able to continue.
                    </p>

                    <div class="bg-gray-50 rounded-lg p-4 mb-6">
                        <div class="text-sm text-gray-600 space-y-1">
                            @php $stats = $this->getExamStats(); @endphp
                            <div>Answered: {{ $stats['answered'] }} questions</div>
                            <div>Total: {{ $stats['total'] }} questions</div>
                            <div>Points: {{ $stats['points'] }}</div>
                        </div>
                    </div>

                    <div class="flex gap-3">
                        <button wire:click="cancelExit"
                            class="flex-1 px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-700 rounded-lg font-semibold transition duration-200">
                            Continue Exam
                        </button>
                        <button wire:click="confirmExitExam"
                            class="flex-1 px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-semibold transition duration-200 flex items-center justify-center"
                            wire:loading.attr="disabled">
                            <span wire:loading.remove>Exit & Submit</span>
                            <span wire:loading class="flex items-center justify-center">
                                <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg"
                                    fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10"
                                        stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                    </path>
                                </svg>
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

<script>
    document.addEventListener('livewire:init', () => {
        Livewire.on('start-auto-submit-timer', () => {
            console.log('Auto-submit timer started');
            const countdownElement = document.getElementById('auto-submit-countdown');
            const timerElement = document.getElementById('countdown-timer');

            if (countdownElement && timerElement) {
                countdownElement.classList.remove('hidden');

                let seconds = 5;
                timerElement.textContent = seconds;

                const countdown = setInterval(() => {
                    seconds--;
                    timerElement.textContent = seconds;
                    console.log('Countdown:', seconds);

                    if (seconds <= 0) {
                        clearInterval(countdown);
                        console.log('Auto-submitting exam...');
                        // Directly call the submit method
                        @this.call('submitExam');
                    }
                }, 1000);
            } else {
                console.error('Countdown elements not found');
            }
        });
    });
</script>
