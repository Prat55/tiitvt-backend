<?php

use Mary\Traits\Toast;
use Livewire\Volt\Component;
use App\Services\ExamService;
use Livewire\Attributes\Title;
use App\Models\{Student, Course, Exam, Category};

new class extends Component {
    use Toast;

    #[Title('Schedule Exam')]
    public string $selectedCourse = '';
    public array $selectedStudents = [];
    public array $selectedCategories = [];
    public int $duration = 60;
    public string $date = '';
    public string $startTime = '';
    public string $endTime = '';
    public array $filteredStudents = [];
    public array $availableCategories = [];
    public array $completedCategories = [];

    private ExamService $examService;

    public function mount()
    {
        $this->examService = new ExamService();
        $this->date = now()->format('Y-m-d');
        $this->startTime = '09:00';
        $this->endTime = '10:00';
        $this->filteredStudents = [];
        $this->availableCategories = [];
        $this->completedCategories = [];
    }

    public function getCoursesProperty()
    {
        return Course::all();
    }

    public function updatedSelectedCourse()
    {
        if ($this->selectedCourse) {
            // Get students for the selected course
            $this->filteredStudents = Student::where('course_id', $this->selectedCourse)
                ->get()
                ->map(function ($student) {
                    return [
                        'id' => $student->id,
                        'name' => $student->first_name . ' ' . $student->fathers_name . ($student->surname ? ' ' . $student->surname : ''),
                        'student_id' => $student->tiitvt_reg_no,
                    ];
                })
                ->toArray();

            // Get available categories for the selected course (excluding completed ones)
            $this->availableCategories = $this->examService->getAvailableCategories($this->selectedCourse);

            // Get completed categories for this course (categories where all students have completed exams)
            $this->completedCategories = $this->examService->getCompletedCategories($this->selectedCourse);

            // Reset selections
            $this->selectedStudents = [];
            $this->selectedCategories = [];
        } else {
            $this->filteredStudents = [];
            $this->availableCategories = [];
            $this->completedCategories = [];
            $this->selectedStudents = [];
            $this->selectedCategories = [];
        }
    }

    public function calculateEndTime()
    {
        if ($this->startTime && $this->duration) {
            $start = \Carbon\Carbon::parse($this->startTime);
            $end = $start->copy()->addMinutes($this->duration);
            $this->endTime = $end->format('H:i');
        }
    }

    public function scheduleExam()
    {
        $this->validate([
            'selectedCourse' => 'required|exists:courses,id',
            'selectedStudents' => 'required|array|min:1',
            'selectedStudents.*' => 'exists:students,id',
            'selectedCategories' => 'required|array|min:1',
            'selectedCategories.*' => 'exists:categories,id',
            'duration' => 'required|integer|min:15|max:300',
            'date' => 'required|date|after_or_equal:today',
            'startTime' => 'required|date_format:H:i',
            'endTime' => 'required|date_format:H:i|after:startTime',
        ]);

        try {
            $scheduledExams = [];
            $failedExams = [];

            foreach ($this->selectedStudents as $studentId) {
                // Check for time conflicts for each student
                if ($this->examService->hasTimeConflict($studentId, $this->date, $this->startTime, $this->endTime)) {
                    $student = Student::find($studentId);
                    $failedExams[] = $student->first_name . ' ' . $student->fathers_name . ($student->surname ? ' ' . $student->surname : '');
                    continue;
                }

                // Schedule exam for this student
                $exam = $this->examService->scheduleExamWithCategories([
                    'course_id' => $this->selectedCourse,
                    'student_id' => $studentId,
                    'category_ids' => $this->selectedCategories,
                    'duration' => $this->duration,
                    'date' => $this->date,
                    'start_time' => $this->startTime,
                    'end_time' => $this->endTime,
                ]);

                $scheduledExams[] = [
                    'student_name' => $exam->student->first_name . ' ' . $exam->student->fathers_name . ($exam->student->surname ? ' ' . $exam->student->surname : ''),
                    'exam_id' => $exam->exam_id,
                    'password' => $exam->password,
                    'categories' => $exam->examCategories->pluck('category.name')->implode(', '),
                ];
            }

            // Show success/error messages
            if (!empty($scheduledExams)) {
                $successMessage = 'Successfully scheduled exams for ' . count($scheduledExams) . ' student(s):<br>';
                foreach ($scheduledExams as $exam) {
                    $successMessage .= "• {$exam['student_name']}: Exam ID: {$exam['exam_id']}, Password: {$exam['password']}, Categories: {$exam['categories']}<br>";
                }
                $this->success($successMessage, position: 'toast-bottom');
            }

            if (!empty($failedExams)) {
                $errorMessage = 'Failed to schedule exams for: ' . implode(', ', $failedExams) . ' (time conflicts)';
                $this->error($errorMessage, position: 'toast-bottom');
            }

            // Reset form if all exams were scheduled successfully
            if (empty($failedExams)) {
                $this->reset(['selectedCourse', 'selectedStudents', 'selectedCategories', 'duration', 'date', 'startTime', 'endTime']);
                $this->filteredStudents = [];
                $this->availableCategories = [];
                $this->completedCategories = [];
            }
        } catch (\Exception $e) {
            $this->error('Failed to schedule exams: ' . $e->getMessage(), position: 'toast-bottom');
        }
    }

    public function resetForm(): void
    {
        $this->reset(['selectedCourse', 'selectedStudents', 'selectedCategories', 'duration', 'date', 'startTime', 'endTime']);
        $this->filteredStudents = [];
        $this->availableCategories = [];
        $this->completedCategories = [];
        $this->resetValidation();
        $this->success('Form reset successfully!', position: 'toast-bottom');
    }
}; ?>

<div>
    <!-- Header -->
    <div class="flex justify-between items-start lg:items-center flex-col lg:flex-row mt-3 mb-5 gap-2">
        <div>
            <h1 class="text-2xl font-bold">
                Schedule New Exam
            </h1>
            <div class="breadcrumbs text-sm">
                <ul class="flex">
                    <li>
                        <a href="{{ route('admin.index') }}" wire:navigate>
                            Dashboard
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('admin.exam.index') }}" wire:navigate>
                            Exams
                        </a>
                    </li>
                    <li>
                        Schedule Exam
                    </li>
                </ul>
            </div>
        </div>
        <div class="flex gap-3">
            <x-button label="Reset Form" icon="o-arrow-path" class="btn-outline" wire:click="resetForm" responsive />
            <x-button label="Back to Exams" icon="o-arrow-left" class="btn-primary btn-outline"
                link="{{ route('admin.exam.index') }}" responsive />
        </div>
    </div>

    <hr class="mb-5">

    <!-- Form -->
    <x-card shadow>
        <form wire:submit="scheduleExam" class="space-y-6">
            <!-- Course and Category Selection -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="md:col-span-2">
                    <h3 class="text-lg font-semibold text-primary">Course & Category Selection</h3>
                </div>

                <x-choices-offline label="Select Course" wire:model.live="selectedCourse" :options="$this->courses"
                    placeholder="Choose a course..." icon="o-academic-cap" single searchable clearable />

                <x-choices-offline label="Select Categories" wire:model="selectedCategories" :options="$availableCategories"
                    placeholder="Choose categories..." icon="o-tag" :disabled="!$selectedCourse || empty($availableCategories)" searchable clearable multiple />
            </div>

            <!-- Completed Categories Warning -->
            @if (!empty($completedCategories))
                <div class="alert alert-warning">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                            clip-rule="evenodd"></path>
                    </svg>
                    <span class="text-sm">
                        <strong>Note:</strong> The following categories have been completed by all students and are
                        hidden:
                        {{ implode(', ', array_column($completedCategories, 'name')) }}
                    </span>
                </div>
            @endif

            <!-- Student Selection -->
            <div class="grid grid-cols-1 gap-6">
                <div>
                    <h3 class="text-lg font-semibold text-primary mb-4">Student Selection</h3>
                    <x-choices-offline label="Select Students" wire:model="selectedStudents" :options="$filteredStudents"
                        placeholder="Choose students..." icon="o-users" :disabled="!$selectedCourse || empty($filteredStudents)" searchable clearable
                        multiple />
                    <p class="text-sm text-gray-600 mt-2">You can select multiple students. Each student will get a
                        separate exam with unique ID and password.</p>
                </div>
            </div>

            <!-- Exam Details -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="md:col-span-2">
                    <h3 class="text-lg font-semibold text-primary">Exam Details</h3>
                </div>

                <x-input label="Duration (minutes)" wire:model.live="duration" type="number" min="15"
                    max="300" icon="o-clock" placeholder="Enter duration in minutes" required />

                <x-input label="Exam Date" wire:model="date" type="date" min="{{ date('Y-m-d') }}" icon="o-calendar"
                    required />

                <x-input label="Start Time" wire:model.live="startTime" wire:change="calculateEndTime" type="time"
                    icon="o-play" required />

                <x-input label="End Time" wire:model="endTime" type="time" icon="o-stop" required />
            </div>

            <!-- Duration Info -->
            <div class="alert alert-info">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                        d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                        clip-rule="evenodd"></path>
                </svg>
                <span class="text-sm">Duration must be between 15 minutes and 5 hours. End time will be automatically
                    calculated based on start time and duration.</span>
            </div>

            <!-- Form Actions -->
            <div class="flex justify-end gap-3 pt-6 border-t">
                <x-button label="Cancel" icon="o-x-mark" class="btn-error btn-soft btn-sm"
                    link="{{ route('admin.exam.index') }}" />
                <x-button label="Schedule Exams" icon="o-calendar" class="btn-primary btn-sm btn-soft" type="submit"
                    spinner="scheduleExam" />
            </div>
        </form>
    </x-card>
</div>
