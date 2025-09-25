<?php

use Carbon\Carbon;
use Mary\Traits\Toast;
use App\Models\ExamStudent;
use Livewire\Volt\Component;
use Livewire\Attributes\{Title, Layout};

new class extends Component {
    use Toast;

    #[Layout('components.layouts.guest')]
    #[Title('Exam Login')]
    public $examUserId = '';
    public $examPassword = '';
    public $isLoading = false;
    public $examStudent = null;
    public $showWaitingPage = false;
    public $timeUntilStart = 0;
    public $examStartTime = null;

    public function login()
    {
        $this->validate([
            'examUserId' => 'required|string',
            'examPassword' => 'required|string',
        ]);

        $this->isLoading = true;

        try {
            $examStudent = ExamStudent::with(['exam.course', 'exam.examCategories.category', 'student'])
                ->where('exam_user_id', $this->examUserId)
                ->where('exam_password', $this->examPassword)
                ->first();

            if (!$examStudent) {
                $this->error('Invalid credentials. Please check your User ID and Password.');
                $this->isLoading = false;
                return;
            }

            $this->examStudent = $examStudent;

            // Check if exam has started
            $now = now();
            $examStartDateTime = Carbon::parse($examStudent->exam->date->format('Y-m-d') . ' ' . $examStudent->exam->start_time->format('H:i:s'));

            if ($now->gte($examStartDateTime)) {
                // Exam has started, proceed to dashboard
                $this->proceedToExam();
            } else {
                // Exam hasn't started yet, show waiting page
                $this->showWaitingPage = true;
                $this->examStartTime = $examStartDateTime;
                $this->calculateTimeUntilStart();
            }

            $this->isLoading = false;
        } catch (\Exception $e) {
            $this->error('An error occurred during login. Please try again.');
            $this->isLoading = false;
        }
    }

    public function calculateTimeUntilStart()
    {
        if ($this->examStartTime) {
            $now = now();
            $this->timeUntilStart = max(0, $this->examStartTime->diffInSeconds($now));

            if ($this->timeUntilStart <= 0) {
                $this->proceedToExam();
            }
        }
    }

    public function proceedToExam()
    {
        // Store exam student in session
        session(['exam_student_id' => $this->examStudent->id]);
        session(['exam_id' => $this->examStudent->exam_id]);

        $this->success('Login successful! Redirecting to exam dashboard...');
        $this->redirect(route('frontend.exam.index'), navigate: true);
    }

    public function getTimeUntilStartProperty()
    {
        $this->calculateTimeUntilStart();
        return $this->timeUntilStart;
    }
}; ?>

<div class="min-h-screen bg-base-100 flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        @if (!$showWaitingPage)
            {{-- Login Form --}}
            <x-card class="bg-base-200 relative">
                <div class="absolute top-0 right-0">
                    <x-theme-toggle class="w-12 h-12 btn-sm" lightTheme="light" darkTheme="dark" />
                </div>

                <div class="text-center mb-8">
                    <div class="mx-auto w-16 h-16 bg-primary rounded-full flex items-center justify-center mb-4">
                        <x-icon name="o-check-circle" class="w-8 h-8 text-white" />
                    </div>
                    <h1 class="text-3xl font-bold mb-2">Exam Portal</h1>
                    <p class="text-gray-500">Enter your exam credentials to continue</p>
                </div>

                <x-form wire:submit="login" class="space-y-6" no-seperator>
                    <div>
                        <x-input label="Exam User ID" wire:model="examUserId" icon="o-user"
                            placeholder="Enter your exam user ID" inline />
                    </div>

                    <div>
                        <x-password label="Exam Password" wire:model="examPassword" icon="o-lock-closed"
                            placeholder="Enter your exam password" inline right />
                    </div>

                    <x-button type="submit" label="Login" icon="o-paper-airplane" spinner="login"
                        class="btn-primary" />
                </x-form>

                <div class="mt-6 text-center">
                    <p class="text-sm text-gray-500">
                        Need help? Contact your exam administrator
                    </p>
                </div>
            </x-card>
        @else
            {{-- Waiting Page with Timer --}}
            <x-card class="bg-base-200 relative">
                <div class="absolute top-0 right-0">
                    <x-theme-toggle class="w-12 h-12 btn-sm" lightTheme="light" darkTheme="dark" />
                </div>

                <div class="text-center mb-8">
                    <div class="mx-auto w-16 h-16 bg-warning rounded-full flex items-center justify-center mb-4">
                        <x-icon name="o-clock" class="w-8 h-8 text-white" />
                    </div>
                    <h1 class="text-3xl font-bold mb-2">Exam Not Started Yet</h1>
                    <p class="text-gray-500">Please wait for the exam to begin</p>
                </div>

                @if ($examStudent)
                    <div class="space-y-4 mb-6">
                        <div class="bg-base-300 rounded-lg p-4">
                            <h3 class="font-semibold text-lg mb-2">Exam Details</h3>
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Student:</span>
                                    <span class="font-medium">{{ $examStudent->student->first_name }}
                                        {{ $examStudent->student->fathers_name }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Course:</span>
                                    <span class="font-medium">{{ $examStudent->exam->course->name }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Exam Date:</span>
                                    <span class="font-medium">{{ $examStudent->exam->date->format('M d, Y') }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Start Time:</span>
                                    <span
                                        class="font-medium">{{ $examStudent->exam->start_time->format('g:i A') }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="bg-primary/10 rounded-lg p-6 text-center">
                            <h3 class="text-lg font-semibold mb-2">Time Until Exam Starts</h3>
                            <div class="text-4xl font-mono font-bold text-primary"
                                wire:poll.1s="calculateTimeUntilStart">
                                @php
                                    $hours = floor($timeUntilStart / 3600);
                                    $minutes = floor(($timeUntilStart % 3600) / 60);
                                    $seconds = $timeUntilStart % 60;
                                @endphp
                                {{ sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds) }}
                            </div>
                            <p class="text-sm text-gray-600 mt-2">
                                @if ($timeUntilStart > 3600)
                                    {{ floor($timeUntilStart / 3600) }} hours remaining
                                @elseif ($timeUntilStart > 60)
                                    {{ floor($timeUntilStart / 60) }} minutes remaining
                                @else
                                    {{ $timeUntilStart }} seconds remaining
                                @endif
                            </p>
                        </div>
                    </div>
                @endif

                <div class="text-center">
                    <div class="loading loading-spinner loading-lg text-primary"></div>
                    <p class="text-sm text-gray-500 mt-2">
                        You will be automatically redirected when the exam starts
                    </p>
                </div>
            </x-card>
        @endif
    </div>
</div>
