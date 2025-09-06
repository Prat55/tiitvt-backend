<?php

use Mary\Traits\Toast;
use Illuminate\View\View;
use Livewire\Volt\Component;
use App\Services\ExamService;
use Livewire\Attributes\Title;
use App\Models\{Student, Course, Exam, Category};

new class extends Component {
    use Toast;

    #[Title('Schedule New Exam')]
    public $selectedCourse = '';
    public $selectedStudents = [];
    public $selectedCategories = [];
    public $duration = 60;
    public $date = '';
    public $startTime = '';
    public $endTime = '';

    public $categories = [];
    public $students = [];
    public $dateConfig = ['altFormat' => 'd/m/Y'];

    public function mount()
    {
        $this->date = now()->format('Y-m-d');
        $this->startTime = '09:00';
        $this->endTime = '10:00';
        $this->selectedStudents = [];
    }

    public function updatedSelectedCourse()
    {
        // Reset selections when course changes
        $this->selectedStudents = [];
        $this->selectedCategories = [];
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
            $examService = new ExamService();

            // Check for time conflicts for all selected students
            foreach ($this->selectedStudents as $studentId) {
                if ($examService->hasTimeConflict($studentId, $this->date, $this->startTime, $this->endTime)) {
                    $student = Student::find($studentId);
                    $failedExams[] = $student->first_name . ' ' . $student->fathers_name . ($student->surname ? ' ' . $student->surname : '');
                }
            }

            // If there are time conflicts, don't proceed
            if (!empty($failedExams)) {
                $errorMessage = 'Failed to schedule exams for: ' . implode(', ', $failedExams) . ' (time conflicts)';
                $this->error($errorMessage, position: 'toast-bottom');
                return;
            }

            // Schedule one exam for all students
            $exam = $examService->scheduleExamWithCategories([
                'course_id' => $this->selectedCourse,
                'student_ids' => $this->selectedStudents,
                'category_ids' => $this->selectedCategories,
                'duration' => $this->duration,
                'date' => $this->date,
                'start_time' => $this->startTime,
                'end_time' => $this->endTime,
            ]);

            // Prepare success message with individual student credentials
            foreach ($exam->examStudents as $examStudent) {
                $scheduledExams[] = [
                    'student_name' => $examStudent->student->first_name . ' ' . $examStudent->student->fathers_name . ($examStudent->student->surname ? ' ' . $examStudent->student->surname : ''),
                    'exam_id' => $exam->exam_id,
                    'user_id' => $examStudent->exam_user_id,
                    'password' => $examStudent->exam_password,
                    'categories' => $exam->examCategories->pluck('category.name')->implode(', '),
                ];
            }

            // Show success message
            if (!empty($scheduledExams)) {
                $successMessage = 'Successfully scheduled exam for ' . count($scheduledExams) . ' student(s):<br>';
                foreach ($scheduledExams as $exam) {
                    $successMessage .= "• {$exam['student_name']}: Exam ID: {$exam['exam_id']}, User ID: {$exam['user_id']}, Password: {$exam['password']}, Categories: {$exam['categories']}<br>";
                }
                $this->success($successMessage, position: 'toast-bottom');
            }

            // Reset form since all exams were scheduled successfully
            $this->reset(['selectedCourse', 'selectedStudents', 'selectedCategories', 'duration', 'date', 'startTime', 'endTime']);
        } catch (\Exception $e) {
            $this->error('Failed to schedule exams: ' . $e->getMessage(), position: 'toast-bottom');
        }
    }

    public function resetForm(): void
    {
        $this->reset(['selectedCourse', 'selectedStudents', 'selectedCategories', 'duration', 'date', 'startTime', 'endTime']);
        $this->resetValidation();
        $this->success('Form reset successfully!', position: 'toast-bottom');
    }

    public function rendering(View $view): void
    {
        if ($this->selectedCourse) {
            $view->categories = Course::find($this->selectedCourse)->categories->toArray();

            $view->students = Student::where('course_id', $this->selectedCourse)
                ->get(['id', 'first_name', 'fathers_name', 'surname', 'tiitvt_reg_no'])
                ->map(function ($student) {
                    $name = $student->first_name . ' ' . $student->fathers_name;
                    if ($student->surname) {
                        $name .= ' ' . $student->surname;
                    }
                    $name .= ' - ' . $student->tiitvt_reg_no;
                    return [
                        'id' => $student->id,
                        'name' => $name,
                    ];
                })
                ->toArray();
        }
    }
}; ?>
@section('cdn')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
@endsection
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

                <x-choices-offline label="Select Course" wire:model.live="selectedCourse" :options="Course::all(['id', 'name'])->toArray()"
                    placeholder="Choose a course..." icon="o-academic-cap" single searchable clearable />

                <x-choices-offline label="Select Categories" wire:model="selectedCategories" :options="$categories"
                    placeholder="Choose categories..." icon="o-tag" :disabled="!$selectedCourse || empty($categories)"
                    no-result-text="Ops! Nothing here ..." searchable clearable />
            </div>

            <!-- Student Selection -->
            <div class="grid grid-cols-1 gap-6">
                <div>
                    <h3 class="text-lg font-semibold text-primary mb-4">Student Selection</h3>

                    <x-choices-offline label="Select Students" wire:model="selectedStudents" :options="$students"
                        placeholder="Choose students..." icon="o-users" :disabled="!$selectedCourse || empty($students)"
                        no-result-text="Ops! Nothing here ..." searchable clearable
                        hint="You can select multiple students. All students will take the same exam but with individual user IDs and passwords." />
                </div>
            </div>

            <!-- Exam Details -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="md:col-span-2">
                    <h3 class="text-lg font-semibold text-primary">Exam Details</h3>
                </div>

                <x-datepicker label="Date" wire:model="date" icon="o-calendar" :config="$dateConfig" />

                <div class="grid grid-cols-2 gap-3">
                    <x-input label="Start Time" wire:model.live="startTime" wire:change="calculateEndTime"
                        type="time" icon="o-play" required />

                    <x-input label="End Time" wire:model="endTime" type="time" icon="o-stop" required />
                </div>
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
