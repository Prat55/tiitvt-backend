<?php

use Mary\Traits\Toast;
use App\Enums\RolesEnum;
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

    // Student search and selection properties
    public $studentSearch = '';
    public $selectAllStudents = false;
    public $filteredStudents = [];

    // Pagination properties
    public $studentsPerPage = 10;
    public $currentPage = 1;
    public $totalStudents = 0;
    public $hasMoreStudents = false;

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
        $this->studentSearch = '';
        $this->selectAllStudents = false;
        $this->filteredStudents = [];
        $this->currentPage = 1;
        $this->totalStudents = 0;
        $this->hasMoreStudents = false;

        // Load students for the selected course
        if ($this->selectedCourse) {
            $this->loadStudents();
        }
    }

    public function updatedStudentSearch()
    {
        $this->currentPage = 1;

        // If there's a search term, load all matching students
        if (!empty($this->studentSearch)) {
            $this->loadAllMatchingStudents();
        } else {
            // Reset to paginated view
            $this->loadStudents();
        }
    }

    public function loadAllMatchingStudents()
    {
        if (!$this->selectedCourse || empty($this->studentSearch)) {
            $this->filteredStudents = [];
            return;
        }

        $searchTerm = strtolower($this->studentSearch);

        // Load all students that match the search criteria
        $allStudents = Student::where('course_id', $this->selectedCourse)
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
            ->filter(function ($student) use ($searchTerm) {
                return strpos(strtolower($student['name']), $searchTerm) !== false;
            })
            ->values()
            ->toArray();

        $this->students = $allStudents;
        $this->filteredStudents = $allStudents;
        $this->hasMoreStudents = false; // No pagination when searching
    }

    public function loadStudents()
    {
        if (!$this->selectedCourse) {
            $this->students = [];
            $this->filteredStudents = [];
            return;
        }

        // Get total count
        $this->totalStudents = Student::where('course_id', $this->selectedCourse)->count();

        // Calculate offset
        $offset = ($this->currentPage - 1) * $this->studentsPerPage;

        // Load students for current page
        $students = Student::when(hasAuthRole(RolesEnum::Center->value), function ($q) {
            $q->where('center_id', auth()->user()->center->id);
        })
            ->where('course_id', $this->selectedCourse)
            ->offset($offset)
            ->limit($this->studentsPerPage)
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

        // If it's the first page, replace students, otherwise append
        if ($this->currentPage === 1) {
            $this->students = $students;
        } else {
            $this->students = array_merge($this->students, $students);
        }

        // Check if there are more students to load
        $this->hasMoreStudents = $offset + $this->studentsPerPage < $this->totalStudents;

        // Filter students
        $this->filterStudents();
    }

    public function loadMoreStudents()
    {
        if ($this->hasMoreStudents) {
            $this->currentPage++;
            $this->loadStudents();
        }
    }

    public function updatedSelectAllStudents()
    {
        if ($this->selectAllStudents) {
            // Select all visible students
            $this->selectedStudents = collect($this->filteredStudents)->pluck('id')->toArray();
        } else {
            // Deselect all students
            $this->selectedStudents = [];
        }
    }

    public function updatedSelectedStudents()
    {
        // Update select all checkbox based on current selection
        if (empty($this->filteredStudents)) {
            $this->selectAllStudents = false;
        } else {
            $visibleStudentIds = collect($this->filteredStudents)->pluck('id')->toArray();
            $this->selectAllStudents = count($this->selectedStudents) === count($visibleStudentIds) && !empty(array_intersect($this->selectedStudents, $visibleStudentIds));
        }
    }

    public function filterStudents()
    {
        if (empty($this->students)) {
            $this->filteredStudents = [];
            return;
        }

        if (empty($this->studentSearch)) {
            $this->filteredStudents = $this->students;
        } else {
            $searchTerm = strtolower($this->studentSearch);
            $this->filteredStudents = array_filter($this->students, function ($student) use ($searchTerm) {
                return strpos(strtolower($student['name']), $searchTerm) !== false;
            });
        }
    }

    public function removeStudent($studentId)
    {
        $this->selectedStudents = array_diff($this->selectedStudents, [$studentId]);
        $this->updatedSelectedStudents();
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
        $this->reset(['selectedCourse', 'selectedStudents', 'selectedCategories', 'duration', 'date', 'startTime', 'endTime', 'studentSearch', 'selectAllStudents', 'filteredStudents', 'currentPage', 'totalStudents', 'hasMoreStudents']);
        $this->resetValidation();
        $this->success('Form reset successfully!', position: 'toast-bottom');
    }

    public function rendering(View $view): void
    {
        if ($this->selectedCourse) {
            $view->categories = Course::find($this->selectedCourse)->categories->toArray();

            // Students are loaded via loadStudents() method when course changes
            // No need to load them here as it's handled in updatedSelectedCourse()
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

                    <x-card class="bg-base-300" shadow>
                        <div class="space-y-4">
                            <!-- Search Input -->
                            <div class="relative">
                                <x-input label="Search Students" wire:model.live="studentSearch"
                                    placeholder="Type to search students..." icon="o-magnifying-glass" :disabled="!$selectedCourse || empty($students)"
                                    class="pr-10" hint="You can search by name or registration number" />
                            </div>

                            <!-- Select All Checkbox -->
                            @if (!empty($filteredStudents))
                                <div class="flex items-center gap-2 p-3 bg-base-200 rounded-lg">
                                    <x-checkbox wire:model.live="selectAllStudents"
                                        label="Select All ({{ count($filteredStudents) }} students)" />
                                </div>
                            @endif

                            <!-- Selected Students Display -->
                            @if (!empty($selectedStudents))
                                <div class="space-y-2">
                                    <h4 class="text-sm font-semibold text-primary">Selected Students
                                        ({{ count($selectedStudents) }})</h4>
                                    <div class="max-h-32 overflow-y-auto space-y-1">
                                        @foreach ($selectedStudents as $selectedId)
                                            @php
                                                $selectedStudent = collect($students)->firstWhere('id', $selectedId);
                                            @endphp
                                            @if ($selectedStudent)
                                                <div
                                                    class="flex items-center justify-between p-2 bg-primary/10 rounded-lg">
                                                    <span class="text-sm">{{ $selectedStudent['name'] }}</span>
                                                    <button type="button"
                                                        wire:click="removeStudent({{ $selectedId }})"
                                                        class="btn btn-xs btn-circle btn-error">
                                                        <x-icon name="o-x-mark" class="w-3 h-3" />
                                                    </button>
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <!-- Student List -->
                            @if (!empty($filteredStudents))
                                <div class="space-y-2">
                                    <div class="flex justify-between items-center">
                                        <h4 class="text-sm font-semibold text-primary">Available Students</h4>
                                        @if ($totalStudents > 0)
                                            <span class="text-xs text-base-content/60">
                                                Showing {{ count($filteredStudents) }} of {{ $totalStudents }} students
                                            </span>
                                        @endif
                                    </div>
                                    <div class="max-h-64 overflow-y-auto space-y-1 border rounded-lg p-2">
                                        @foreach ($filteredStudents as $student)
                                            <div
                                                class="flex items-center gap-3 p-2 hover:bg-base-200 rounded-lg {{ in_array($student['id'], $selectedStudents) ? 'bg-primary/5' : '' }}">
                                                <x-checkbox wire:model.live="selectedStudents"
                                                    value="{{ $student['id'] }}" label="{{ $student['name'] }}" />
                                            </div>
                                        @endforeach
                                    </div>

                                    <!-- Load More Button -->
                                    @if ($hasMoreStudents && empty($studentSearch))
                                        <div class="flex justify-center pt-2">
                                            <x-button label="Load More Students" icon="o-arrow-down"
                                                class="btn-outline btn-sm" wire:click="loadMoreStudents"
                                                spinner="loadMoreStudents" />
                                        </div>
                                    @endif
                                </div>
                            @elseif($selectedCourse && empty($students))
                                <div class="text-center py-8 text-base-content/60">
                                    <x-icon name="o-users" class="w-12 h-12 mx-auto mb-2" />
                                    <p>No students found for this course.</p>
                                </div>
                            @elseif($selectedCourse && !empty($students) && empty($filteredStudents))
                                <div class="text-center py-8 text-base-content/60">
                                    <x-icon name="o-magnifying-glass" class="w-12 h-12 mx-auto mb-2" />
                                    <p>No students match your search criteria.</p>
                                </div>
                            @elseif(!$selectedCourse)
                                <div class="text-center py-8 text-base-content/60">
                                    <x-icon name="o-academic-cap" class="w-12 h-12 mx-auto mb-2" />
                                    <p>Please select a course first to view students.</p>
                                </div>
                            @endif
                        </div>
                    </x-card>
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
                <x-button label="Schedule Exams" icon="o-calendar" class="btn-primary btn-sm btn-soft"
                    type="submit" spinner="scheduleExam" />
            </div>
        </form>
    </x-card>
</div>
