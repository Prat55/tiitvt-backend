<?php

use Carbon\Carbon;
use Mary\Traits\Toast;
use Livewire\Volt\Component;
use Livewire\Attributes\{Title, Layout};
use App\Models\{ExamStudent, ExamCategory, Question, ExamResult};

new class extends Component {
    use Toast;

    #[Layout('components.layouts.guest')]
    #[Title('Exam Dashboard')]
    public $examStudent;
    public $exam;
    public $examCategories = [];
    public $currentTime;
    public $isLoading = false;

    public function mount()
    {
        $examStudentId = session('exam_student_id');

        if (!$examStudentId) {
            $this->error('Session expired. Please login again.', redirectTo: route('frontend.exam.login'));
            return;
        }

        $this->loadExamStudent();
        $this->currentTime = now();
    }

    public function loadExamStudent()
    {
        $examStudentId = session('exam_student_id');
        $this->examStudent = ExamStudent::with(['exam.course', 'exam.examCategories.category', 'student'])->find($examStudentId);

        if (!$this->examStudent) {
            $this->error('Exam student not found.', redirectTo: route('frontend.exam.login'));
            return;
        }

        $this->exam = $this->examStudent->exam;
        $this->examCategories = $this->exam->examCategories;
    }

    public function startExam($examCategoryId)
    {
        $this->isLoading = true;

        \Log::channel('exam')->info('Starting exam', [
            'exam_category_id' => $examCategoryId,
            'exam_student_id' => session('exam_student_id'),
        ]);

        try {
            $examCategory = ExamCategory::with('category')->find($examCategoryId);

            if (!$examCategory) {
                $this->error('Category not found.');
                $this->isLoading = false;
                return;
            }

            // Check if this category is already completed
            if ($this->isCategoryCompleted($examCategory->category_id)) {
                $this->error('This exam category has already been completed.');
                $this->isLoading = false;
                return;
            }

            // Check if exam is accessible
            if (!$this->examStudent->isExamAccessible()) {
                $this->error('Exam is not accessible at this time. Please check the exam schedule.');
                $this->isLoading = false;
                return;
            }

            // Check if exam time is valid (simplified time check)
            $now = now();
            $examDate = $this->exam->date;
            $startTime = $this->exam->start_time;
            $endTime = $this->exam->end_time;

            // Create full datetime objects
            $examStartDateTime = Carbon::parse($examDate->format('Y-m-d') . ' ' . $startTime->format('H:i:s'));
            $examEndDateTime = Carbon::parse($examDate->format('Y-m-d') . ' ' . $endTime->format('H:i:s'));

            if ($now->lt($examStartDateTime)) {
                $this->error('Exam has not started yet. Please wait for the scheduled time.');
                $this->isLoading = false;
                return;
            }

            if ($now->gt($examEndDateTime)) {
                $this->error('Exam time has expired.');
                $this->isLoading = false;
                return;
            }

            // Store exam category ID in session for exam interface
            session(['current_exam_category_id' => $examCategoryId]);
            session(['current_category_id' => $examCategory->category_id]);
            session(['exam_start_time' => now()->toDateTimeString()]);

            \Log::channel('exam')->info('Redirecting to exam', [
                'exam_id' => $this->exam->exam_id,
                'category_slug' => $examCategory->category->slug,
                'redirect_url' => route('frontend.exam.take', ['exam_id' => $this->exam->exam_id, 'slug' => $examCategory->category->slug]),
            ]);

            $this->success('Starting exam...', redirectTo: route('frontend.exam.take', ['exam_id' => $this->exam->exam_id, 'slug' => $examCategory->category->slug]));
        } catch (\Exception $e) {
            $this->error('An error occurred while starting the exam: ' . $e->getMessage());
            $this->isLoading = false;
        }
    }

    public function isCategoryCompleted($categoryId)
    {
        return ExamResult::isCategoryCompleted($this->exam->id, $this->examStudent->student_id, $categoryId);
    }

    public function updateCurrentTime()
    {
        $this->currentTime = now();
    }

    public function refreshData()
    {
        $this->loadExamStudent();
    }

    public function logout()
    {
        session()->forget(['exam_student_id', 'exam_id', 'current_category_id', 'current_exam_category_id', 'exam_start_time']);
        $this->success('Logged out successfully.', redirectTo: route('frontend.exam.login'));
    }
}; ?>

<div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100">
    <!-- Header -->
    <div class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Exam Dashboard</h1>
                    <p class="text-sm text-gray-600">Welcome, {{ $examStudent->student->first_name ?? 'Student' }}</p>
                </div>
                <div class="flex items-center gap-4">
                    <div class="text-right">
                        <div class="text-sm text-gray-600">Current Time</div>
                        <div class="text-lg font-semibold text-blue-600" wire:poll.1s="updateCurrentTime">
                            {{ $currentTime->format('g:i A') }}
                        </div>
                    </div>
                    <x-button wire:click="refreshData" label="Refresh" icon="o-arrow-path"
                        class="btn-outline btn-primary" spinner="refreshData" />
                    <x-button wire:click="logout" label="Logout" icon="o-arrow-right-on-rectangle" class="btn-error" />
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8" wire:poll.5s="refreshData">
        <!-- Exam Info Card -->
        <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
            <div class="flex items-start justify-between">
                <div>
                    <h2 class="text-xl font-semibold text-gray-900 mb-2">{{ $exam->course->name ?? 'Course' }}</h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                        <div class="flex items-center gap-2">
                            <x-icon name="o-calendar-days" class="w-4 h-4 text-gray-500" />
                            <span class="text-gray-600">Date: {{ $exam->date->format('d M Y') }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <x-icon name="o-clock" class="w-4 h-4 text-gray-500" />
                            <span class="text-gray-600">Time: {{ $exam->start_time->format('g:i A') }} -
                                {{ $exam->end_time->format('g:i A') }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <x-icon name="o-clock" class="w-4 h-4 text-gray-500" />
                            <span class="text-gray-600">Duration: {{ $exam->duration }} minutes</span>
                        </div>
                    </div>
                </div>

                <div class="text-right">
                    <div class="text-sm text-gray-600">Exam Status</div>
                    <div
                        class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                        <x-icon name="o-check-circle" class="w-4 h-4 mr-1" />
                        Active
                    </div>
                </div>
            </div>
        </div>

        <!-- Categories Grid -->
        <div class="mb-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Exam Categories</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($examCategories as $examCategory)
                    @php
                        $isCompleted = $this->isCategoryCompleted($examCategory->category_id);
                    @endphp
                    <div
                        class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition duration-200 {{ $isCompleted ? 'opacity-75' : '' }}">
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-2">
                                    <h4 class="text-lg font-semibold text-gray-900">{{ $examCategory->category->name }}
                                    </h4>
                                </div>
                                <div class="space-y-2 text-sm text-gray-600">
                                    <div class="flex items-center gap-2">
                                        <x-icon name="o-star" class="w-4 h-4 text-yellow-500" />
                                        <span>Total Points: {{ $examCategory->total_points }}</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <x-icon name="o-flag" class="w-4 h-4 text-green-500" />
                                        <span>Passing Points: {{ $examCategory->passing_points }}</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <x-icon name="o-clock" class="w-4 h-4 text-blue-500" />
                                        <span>Duration: {{ $exam->duration }} minutes</span>
                                    </div>
                                </div>
                            </div>
                            <div class="ml-4">
                                <div
                                    class="w-12 h-12 {{ $isCompleted ? 'bg-green-100' : 'bg-blue-100' }} rounded-full flex items-center justify-center">
                                    <x-icon name="{{ $isCompleted ? 'o-check-circle' : 'o-book-open' }}"
                                        class="w-6 h-6 {{ $isCompleted ? 'text-green-600' : 'text-blue-600' }}" />
                                </div>
                            </div>
                        </div>

                        @if ($isCompleted)
                            <div
                                class="w-full bg-green-100 text-green-800 py-3 px-4 rounded-lg text-center font-medium">
                                <x-icon name="o-check-circle" class="w-4 h-4 inline mr-2" />
                                Completed
                            </div>
                        @else
                            <x-button wire:click="startExam({{ $examCategory->id }})" label="Start Exam" icon="o-play"
                                spinner="startExam({{ $examCategory->id }})" class="btn-primary w-full" />
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Instructions -->
        <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-6">
            <div class="flex items-start gap-3">
                <x-icon name="s-exclamation-triangle" class="w-6 h-6 text-yellow-600 mt-0.5" />
                <div>
                    <h4 class="font-semibold text-yellow-800 mb-2">Important Instructions</h4>
                    <ul class="text-sm text-yellow-700 space-y-1">
                        <li>• Each category has a specific time limit and passing criteria</li>
                        <li>• You can skip questions and come back to them later</li>
                        <li>• Make sure you have a stable internet connection</li>
                        <li>• Do not refresh the page during the exam</li>
                        <li>• Submit your answers before the time expires</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
