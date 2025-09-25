<?php

use Mary\Traits\Toast;
use App\Enums\RolesEnum;
use Illuminate\View\View;
use Livewire\Volt\Component;
use App\Services\ExamService;
use Livewire\Attributes\Title;
use App\Models\{Student, Course, Exam, Category, Center};

new class extends Component {
    use Toast;

    #[Title('Schedule Exam')]
    public $currentStep = 1;
    public $example = 1;

    public $selectedCourse = '';
    public $selectedCategories = [];
    public $categories = [];
    public $categorySearch = '';
    public $selectAllCategories = false;
    public $filteredCategories = [];

    public $categoryPoints = [];
    public $selectedStudents = [];
    public $students = [];
    public $studentSearch = '';
    public $selectAllStudents = false;
    public $filteredStudents = [];
    public $selectedCenter = '';
    public $centers = [];

    public $duration = 60;
    public $date = '';
    public $startTime = '';
    public $endTime = '';
    public $dateConfig = ['altFormat' => 'd/m/Y'];

    public $studentsPerPage = 20;
    public $currentPage = 1;
    public $totalStudents = 0;
    public $hasMoreStudents = false;

    public $showSuccessModal = false;
    public $scheduledExams = [];
    public $examCredentials = [];

    public function copyStudentCredentials($studentName, $userId, $password)
    {
        $credentials = "Student: {$studentName}\nUser ID: {$userId}\nPassword: {$password}";

        $this->dispatch('copy-to-clipboard', credentials: $credentials);
        $this->success("Credentials for {$studentName} copied to clipboard!", position: 'toast-bottom');
    }

    public function mount()
    {
        $this->date = now()->format('Y-m-d');
        $this->startTime = '09:00';
        $this->endTime = '10:00';
        $this->selectedStudents = [];
        $this->selectedCategories = [];
        $this->categoryPoints = [];
        $this->selectedCenter = '';
        $this->currentStep = 1;
        $this->example = 1;
        $this->loadCenters();
    }

    public function nextStep()
    {
        if ($this->validateCurrentStep()) {
            $this->currentStep++;
            $this->example = $this->currentStep;

            if ($this->currentStep == 2 && $this->selectedCourse && empty($this->students)) {
                $this->loadStudents();
            }
        }
    }

    public function previousStep()
    {
        if ($this->currentStep > 1) {
            $this->currentStep--;
            $this->example = $this->currentStep;
        }
    }

    public function goToStep($step)
    {
        if ($step >= 1 && $step <= 3) {
            $this->currentStep = $step;
            $this->example = $step;
        }
    }

    private function validateCurrentStep()
    {
        switch ($this->currentStep) {
            case 1:
                return !empty($this->selectedCourse) && !empty($this->selectedCategories);
            case 2:
                return !empty($this->selectedStudents) && $this->validateCategoryPoints();
            case 3:
                return !empty($this->date) && !empty($this->startTime) && !empty($this->endTime);
            default:
                return false;
        }
    }

    private function validateCategoryPoints()
    {
        foreach ($this->selectedCategories as $categoryId) {
            if (!isset($this->categoryPoints[$categoryId]) || empty($this->categoryPoints[$categoryId]['total_points']) || $this->categoryPoints[$categoryId]['total_points'] <= 0) {
                return false;
            }
        }
        return true;
    }

    public function loadCenters()
    {
        if (hasAuthRole(RolesEnum::Admin->value)) {
            $this->centers = Center::active()
                ->get(['id', 'name'])
                ->toArray();
        } else {
            $this->centers = [];
        }
    }

    public function updatedSelectedCenter()
    {
        $this->selectedStudents = [];
        $this->studentSearch = '';
        $this->selectAllStudents = false;
        $this->filteredStudents = [];
        $this->currentPage = 1;
        $this->totalStudents = 0;
        $this->hasMoreStudents = false;
        $this->students = [];

        if ($this->selectedCourse) {
            $this->loadStudents();
        }
    }

    public function updatedSelectedCourse()
    {
        $this->selectedStudents = [];
        $this->selectedCategories = [];
        $this->categoryPoints = [];
        $this->studentSearch = '';
        $this->categorySearch = '';
        $this->selectAllStudents = false;
        $this->selectAllCategories = false;
        $this->filteredStudents = [];
        $this->filteredCategories = [];
        $this->currentPage = 1;
        $this->totalStudents = 0;
        $this->hasMoreStudents = false;

        if ($this->selectedCourse) {
            $this->loadCategories();
            if ($this->currentStep >= 2) {
                $this->loadStudents();
            }
        }
    }

    public function updatedCurrentStep()
    {
        if ($this->currentStep == 2 && $this->selectedCourse) {
            $this->loadStudents();
        }
    }

    public function reloadStudents()
    {
        if ($this->selectedCourse) {
            $this->students = [];
            $this->filteredStudents = [];
            $this->currentPage = 1;
            $this->loadStudents();
        }
    }

    public function updatedStudentSearch()
    {
        $this->currentPage = 1;

        if (!empty($this->studentSearch)) {
            $this->loadAllMatchingStudents();
        } else {
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

        $query = Student::where('course_id', $this->selectedCourse);

        if (hasAuthRole(RolesEnum::Center->value)) {
            $query->where('center_id', auth()->user()->center->id);
        } elseif (hasAuthRole(RolesEnum::Admin->value) && !empty($this->selectedCenter)) {
            $query->where('center_id', $this->selectedCenter);
        }
        $allStudents = $query
            ->select(['id', 'first_name', 'fathers_name', 'surname', 'tiitvt_reg_no'])
            ->orderBy('first_name')
            ->get()
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
        $this->hasMoreStudents = false;
    }

    public function loadStudents()
    {
        if (!$this->selectedCourse) {
            $this->students = [];
            $this->filteredStudents = [];
            return;
        }

        $query = Student::where('course_id', $this->selectedCourse);

        if (hasAuthRole(RolesEnum::Center->value)) {
            $query->where('center_id', auth()->user()->center->id);
        } elseif (hasAuthRole(RolesEnum::Admin->value) && !empty($this->selectedCenter)) {
            $query->where('center_id', $this->selectedCenter);
        }

        $cacheKey = 'students_count_' . $this->selectedCourse . '_' . ($this->selectedCenter ?? 'all') . '_' . (hasAuthRole(RolesEnum::Center->value) ? auth()->user()->center->id : 'admin');
        $this->totalStudents = cache()->remember($cacheKey, 300, function () use ($query) {
            return $query->count();
        });

        $offset = ($this->currentPage - 1) * $this->studentsPerPage;
        $students = $query
            ->select(['id', 'first_name', 'fathers_name', 'surname', 'tiitvt_reg_no'])
            ->offset($offset)
            ->limit($this->studentsPerPage)
            ->orderBy('first_name')
            ->get()
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

        if ($this->currentPage === 1) {
            $this->students = $students;
        } else {
            $this->students = array_merge($this->students, $students);
        }

        $this->hasMoreStudents = $offset + $this->studentsPerPage < $this->totalStudents;
        $this->filterStudents();
    }

    public function loadCategories()
    {
        if (!$this->selectedCourse) {
            $this->categories = [];
            $this->filteredCategories = [];
            return;
        }

        try {
            $course = Course::with([
                'categories' => function ($query) {
                    $query->where('is_active', true);
                },
            ])->find($this->selectedCourse);

            if ($course && $course->categories) {
                $this->categories = $course->categories->toArray();
            } else {
                $this->categories = [];
            }

            $this->filteredCategories = $this->categories;
        } catch (\Exception $e) {
            $this->categories = [];
            $this->filteredCategories = [];
        }
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
            $this->selectedStudents = collect($this->filteredStudents)->pluck('id')->toArray();
        } else {
            $this->selectedStudents = [];
        }
    }

    public function updatedSelectedStudents()
    {
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

    public function updatedCategorySearch()
    {
        $this->filterCategories();
    }

    public function updatedSelectAllCategories()
    {
        if ($this->selectAllCategories) {
            $this->selectedCategories = collect($this->filteredCategories)->pluck('id')->toArray();
            foreach ($this->selectedCategories as $categoryId) {
                if (!isset($this->categoryPoints[$categoryId])) {
                    $this->categoryPoints[$categoryId] = [
                        'total_points' => 0,
                        'passing_points' => 0,
                    ];
                }
            }
        } else {
            $this->selectedCategories = [];
            $this->categoryPoints = [];
        }

        // Recalculate end time when categories change
        $this->calculateEndTime();
    }

    public function updatedSelectedCategories()
    {
        if (empty($this->filteredCategories)) {
            $this->selectAllCategories = false;
        } else {
            $visibleCategoryIds = collect($this->filteredCategories)->pluck('id')->toArray();
            $this->selectAllCategories = count($this->selectedCategories) === count($visibleCategoryIds) && !empty(array_intersect($this->selectedCategories, $visibleCategoryIds));
        }

        foreach ($this->selectedCategories as $categoryId) {
            if (!isset($this->categoryPoints[$categoryId])) {
                $this->categoryPoints[$categoryId] = [
                    'total_points' => 0,
                    'passing_points' => 0,
                ];
            }
        }

        $this->categoryPoints = array_intersect_key($this->categoryPoints, array_flip($this->selectedCategories));

        // Recalculate end time when categories change
        $this->calculateEndTime();
    }

    public function filterCategories()
    {
        if (empty($this->categories)) {
            $this->filteredCategories = [];
            return;
        }

        if (empty($this->categorySearch)) {
            $this->filteredCategories = $this->categories;
        } else {
            $searchTerm = strtolower($this->categorySearch);
            $this->filteredCategories = array_filter($this->categories, function ($category) use ($searchTerm) {
                return strpos(strtolower($category['name']), $searchTerm) !== false;
            });
        }
    }

    public function removeCategory($categoryId)
    {
        $this->selectedCategories = array_diff($this->selectedCategories, [$categoryId]);
        unset($this->categoryPoints[$categoryId]);
        $this->updatedSelectedCategories();
        // calculateEndTime() is already called in updatedSelectedCategories()
    }

    public function updatedCategoryPoints($value, $key)
    {
        $categoryId = (int) explode('.', $key)[0];
        $field = explode('.', $key)[1];

        if ($field === 'passing_points' && isset($this->categoryPoints[$categoryId]['total_points'])) {
            if ($value > $this->categoryPoints[$categoryId]['total_points']) {
                $this->addError("categoryPoints.{$categoryId}.passing_points", 'Passing points cannot be greater than total points.');
                return;
            }
        }

        if ($field === 'total_points' && isset($this->categoryPoints[$categoryId]['passing_points'])) {
            if ($this->categoryPoints[$categoryId]['passing_points'] > $value) {
                $this->addError("categoryPoints.{$categoryId}.passing_points", 'Passing points cannot be greater than total points.');
                return;
            }
        }
    }

    public function getCategoryTotalPoints($categoryId)
    {
        return $this->categoryPoints[$categoryId]['total_points'] ?? 0;
    }

    public function getCategoryPassingPoints($categoryId)
    {
        return $this->categoryPoints[$categoryId]['passing_points'] ?? 0;
    }

    public function setCategoryTotalPoints($categoryId, $value)
    {
        $this->categoryPoints[$categoryId]['total_points'] = (int) $value;
        $this->validateCategoryPointsForCategory($categoryId);
    }

    public function setCategoryPassingPoints($categoryId, $value)
    {
        $this->categoryPoints[$categoryId]['passing_points'] = (int) $value;
        $this->validateCategoryPointsForCategory($categoryId);
    }

    private function validateCategoryPointsForCategory($categoryId)
    {
        $totalPoints = $this->categoryPoints[$categoryId]['total_points'] ?? 0;
        $passingPoints = $this->categoryPoints[$categoryId]['passing_points'] ?? 0;

        if ($passingPoints > $totalPoints) {
            $this->addError("categoryPoints.{$categoryId}.passing_points", 'Passing points cannot be greater than total points.');
        }
    }

    public function calculateEndTime()
    {
        if ($this->startTime && $this->duration && !empty($this->selectedCategories)) {
            $start = \Carbon\Carbon::parse($this->startTime);
            $totalDuration = $this->duration * count($this->selectedCategories);
            $end = $start->copy()->addMinutes($totalDuration);
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
            'categoryPoints' => 'required|array',
            'categoryPoints.*.total_points' => 'required|integer|min:1',
            'categoryPoints.*.passing_points' => 'required|integer|min:0|lte:categoryPoints.*.total_points',
            'duration' => 'required|integer|min:15|max:300',
            'date' => 'required|date|after_or_equal:today',
            'startTime' => 'required|date_format:H:i',
            'endTime' => 'required|date_format:H:i|after:startTime',
        ]);

        try {
            $examService = new ExamService();

            foreach ($this->selectedStudents as $studentId) {
                if ($examService->hasTimeConflict($studentId, $this->date, $this->startTime, $this->endTime)) {
                    $student = Student::find($studentId);
                    $failedExams[] = $student->first_name . ' ' . $student->fathers_name . ($student->surname ? ' ' . $student->surname : '');
                }
            }

            if (!empty($failedExams)) {
                $errorMessage = 'Failed to schedule exams for: ' . implode(', ', $failedExams) . ' (time conflicts)';
                $this->error($errorMessage, position: 'toast-bottom');
                return;
            }

            $categoryData = [];
            foreach ($this->selectedCategories as $categoryId) {
                $categoryData[] = [
                    'category_id' => $categoryId,
                    'total_points' => $this->categoryPoints[$categoryId]['total_points'],
                    'passing_points' => $this->categoryPoints[$categoryId]['passing_points'],
                ];
            }

            $exam = $examService->scheduleExamWithCategories([
                'center_id' => hasAuthRole(RolesEnum::Center->value) ? auth()->user()->center->id : null,
                'course_id' => $this->selectedCourse,
                'student_ids' => $this->selectedStudents,
                'category_data' => $categoryData,
                'duration' => $this->duration,
                'date' => $this->date,
                'start_time' => $this->startTime,
                'end_time' => $this->endTime,
            ]);

            $this->examCredentials = [];
            foreach ($exam->examStudents as $examStudent) {
                $this->examCredentials[] = [
                    'student_name' => $examStudent->student->first_name . ' ' . $examStudent->student->fathers_name . ($examStudent->student->surname ? ' ' . $examStudent->student->surname : ''),
                    'exam_id' => $exam->exam_id,
                    'user_id' => $examStudent->exam_user_id,
                    'password' => $examStudent->exam_password,
                    'categories' => $exam->examCategories->pluck('category.name')->implode(', '),
                ];
            }

            $this->showSuccessModal = true;
            $this->scheduledExams = $this->examCredentials;
        } catch (\Exception $e) {
            $this->error('Failed to schedule exams: ' . $e->getMessage(), position: 'toast-bottom');
        }
    }

    public function closeSuccessModal()
    {
        $this->showSuccessModal = false;
        $this->resetForm();
    }

    public function exportCredentials()
    {
        $csvData = [];
        $csvData[] = ['Student Name', 'Exam ID', 'User ID', 'Password', 'Categories'];

        foreach ($this->examCredentials as $credential) {
            $csvData[] = [$credential['student_name'], $credential['exam_id'], $credential['user_id'], $credential['password'], $credential['categories']];
        }

        $filename = 'exam_credentials_' . now()->format('Y_m_d_H_i_s') . '.csv';

        return response()->streamDownload(
            function () use ($csvData) {
                $file = fopen('php://output', 'w');
                foreach ($csvData as $row) {
                    fputcsv($file, $row);
                }
                fclose($file);
            },
            $filename,
            [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ],
        );
    }

    public function resetForm(): void
    {
        $this->reset(['selectedCourse', 'selectedStudents', 'selectedCategories', 'categoryPoints', 'duration', 'date', 'startTime', 'endTime', 'studentSearch', 'categorySearch', 'selectAllStudents', 'selectAllCategories', 'filteredStudents', 'filteredCategories', 'currentPage', 'totalStudents', 'hasMoreStudents', 'selectedCenter', 'currentStep', 'example', 'showSuccessModal', 'scheduledExams', 'examCredentials']);
        $this->resetValidation();
        $this->currentStep = 1;
        $this->example = 1;
        $this->success('Form reset successfully!', position: 'toast-bottom');
    }
}; ?>
@section('cdn')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('copy-to-clipboard', (event) => {
                const credentials = event.credentials;

                if (navigator.clipboard && window.isSecureContext) {
                    navigator.clipboard.writeText(credentials).then(() => {
                        console.log('Credentials copied to clipboard');
                    }).catch(err => {
                        console.error('Failed to copy: ', err);
                        fallbackCopyTextToClipboard(credentials);
                    });
                } else {
                    fallbackCopyTextToClipboard(credentials);
                }
            });
        });

        function fallbackCopyTextToClipboard(text) {
            const textArea = document.createElement("textarea");
            textArea.value = text;
            textArea.style.top = "0";
            textArea.style.left = "0";
            textArea.style.position = "fixed";
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();

            try {
                const successful = document.execCommand('copy');
                if (successful) {
                    console.log('Fallback: Copying text command was successful');
                } else {
                    console.error('Fallback: Unable to copy');
                }
            } catch (err) {
                console.error('Fallback: Oops, unable to copy', err);
            }

            document.body.removeChild(textArea);
        }
    </script>
@endsection
<div class="min-h-screen bg-base-100 dark:bg-base-300">
    <div class="bg-base-200 dark:bg-base-300 border-b border-base-300 dark:border-base-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold text-base-content">
                        Schedule Exam
                    </h1>
                    <p class="mt-2 text-sm text-base-content/70">
                        Create and schedule exams for students in a simple 3-step process
                    </p>
                </div>
                <div class="flex gap-3">
                    <x-button label="Reset Form" icon="o-arrow-path" class="btn-outline btn-sm" wire:click="resetForm" />
                    <x-button label="Back to Exams" icon="o-arrow-left" class="btn-primary btn-outline btn-sm"
                        link="{{ route('admin.exam.index') }}" />
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <x-steps wire:model="example" stepper-classes="w-full p-6 bg-base-200 dark:bg-base-300 rounded-lg mb-8"
            steps-color="step-primary">
            <x-step step="1" text="Course & Categories" />
            <x-step step="2" text="Points & Students" />
            <x-step step="3" text="Schedule Details" />
        </x-steps>

        <x-card class="bg-base-100 dark:bg-base-200 shadow-xl">
            @if ($currentStep == 1)
                <div class="p-6">
                    <div class="mb-6">
                        <h2 class="text-2xl font-bold text-primary mb-2">Step 1: Course & Category Selection</h2>
                        <p class="text-base-content/70 dark:text-base-100/70">Select the course and categories for the
                            exam</p>
                    </div>

                    <div class="space-y-6">
                        <div>
                            <x-choices-offline label="Select Course" wire:model.live="selectedCourse" :options="Course::all(['id', 'name'])->toArray()"
                                placeholder="Choose a course..." icon="o-academic-cap" single searchable clearable
                                class="text-base" />
                        </div>

                        @if ($selectedCourse)
                            <div>
                                <h3 class="text-lg font-semibold text-primary mb-4">Select Categories</h3>

                                <div class="mb-4">
                                    <x-input label="Search Categories" wire:model.live="categorySearch"
                                        placeholder="Type to search categories..." icon="o-magnifying-glass"
                                        :disabled="empty($this->categories)" />
                                </div>

                                @if (!empty($filteredCategories))
                                    <div
                                        class="flex items-center gap-2 p-3 bg-base-200 dark:bg-base-300 rounded-lg mb-4">
                                        <x-checkbox wire:model.live="selectAllCategories"
                                            label="Select All ({{ count($filteredCategories) }} categories)" />
                                    </div>
                                @endif

                                @if (!empty($selectedCategories))
                                    <div class="mb-4">
                                        <h4 class="text-sm font-semibold text-primary mb-2">
                                            Selected Categories ({{ count($selectedCategories) }})
                                        </h4>
                                        <div class="flex flex-wrap gap-2">
                                            @foreach ($selectedCategories as $selectedId)
                                                @php
                                                    $selectedCategory = collect($this->categories)->firstWhere(
                                                        'id',
                                                        $selectedId,
                                                    );
                                                @endphp
                                                @if ($selectedCategory)
                                                    <div class="badge badge-primary gap-2">
                                                        {{ $selectedCategory['name'] }}
                                                        <button type="button"
                                                            wire:click="removeCategory({{ $selectedId }})"
                                                            class="btn btn-xs btn-circle btn-ghost">
                                                            <x-icon name="o-x-mark" class="w-3 h-3" />
                                                        </button>
                                                    </div>
                                                @endif
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                @if (!empty($filteredCategories))
                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                                        @foreach ($filteredCategories as $category)
                                            <div
                                                class="p-3 border border-base-300 dark:border-base-200 rounded-lg hover:bg-base-200 dark:hover:bg-base-300 transition-colors {{ in_array($category['id'], $selectedCategories) ? 'bg-primary/10 border-primary' : '' }}">
                                                <x-checkbox wire:model.live="selectedCategories"
                                                    value="{{ $category['id'] }}" label="{!! $category['name'] !!}" />
                                            </div>
                                        @endforeach
                                    </div>
                                @elseif($selectedCourse && empty($this->categories))
                                    <div class="text-center py-12">
                                        <x-icon name="o-tag" class="w-16 h-16 mx-auto mb-4 text-base-content/40" />
                                        <p class="text-base-content/60">No categories found for this course.</p>
                                    </div>
                                @elseif($selectedCourse && !empty($this->categories) && empty($filteredCategories))
                                    <div class="text-center py-12">
                                        <x-icon name="o-magnifying-glass"
                                            class="w-16 h-16 mx-auto mb-4 text-base-content/40" />
                                        <p class="text-base-content/60">No categories match your search criteria.</p>
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>

                    <div class="flex justify-end mt-8 pt-6 border-t border-base-300 dark:border-base-200">
                        <x-button label="Next: Points & Students" icon="o-arrow-right" class="btn-primary"
                            wire:click="nextStep" :disabled="!$this->validateCurrentStep()" />
                    </div>
                </div>
            @elseif($currentStep == 2)
                <div class="p-6">
                    <div class="mb-6">
                        <h2 class="text-2xl font-bold text-primary mb-2">Step 2: Points & Student Selection</h2>
                        <p class="text-base-content/70 dark:text-base-100/70">Configure category points and select
                            students</p>
                    </div>

                    <div class="space-y-8">
                        @if (!empty($selectedCategories))
                            <div>
                                <h3 class="text-lg font-semibold text-primary mb-4">Category Points Configuration</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    @foreach ($selectedCategories as $categoryId)
                                        @php
                                            $category = collect($this->categories)->firstWhere('id', $categoryId);
                                        @endphp
                                        @if ($category)
                                            <x-card class="bg-base-200 dark:bg-base-300">
                                                <div class="p-4">
                                                    <h4 class="text-sm font-semibold text-primary mb-3">
                                                        {{ $category['name'] }}</h4>
                                                    <div class="space-y-3">
                                                        <x-input label="Total Points"
                                                            value="{{ $this->getCategoryTotalPoints($categoryId) }}"
                                                            wire:change="setCategoryTotalPoints({{ $categoryId }}, $event.target.value)"
                                                            type="number" min="1" required />
                                                        <x-input label="Passing Points"
                                                            value="{{ $this->getCategoryPassingPoints($categoryId) }}"
                                                            wire:change="setCategoryPassingPoints({{ $categoryId }}, $event.target.value)"
                                                            type="number" min="0" required />
                                                    </div>
                                                    @error("categoryPoints.{$categoryId}.total_points")
                                                        <div class="text-error text-xs mt-1">{{ $message }}</div>
                                                    @enderror
                                                    @error("categoryPoints.{$categoryId}.passing_points")
                                                        <div class="text-error text-xs mt-1">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </x-card>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        @if ($selectedCourse)
                            <div>
                                <h3 class="text-lg font-semibold text-primary mb-4">Select Students</h3>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                    <x-input label="Search Students" wire:model.live="studentSearch"
                                        placeholder="Type to search students..." icon="o-magnifying-glass"
                                        :disabled="empty($students)" />

                                    @if (hasAuthRole(RolesEnum::Admin->value))
                                        <x-choices-offline label="Filter by Center" wire:model.live="selectedCenter"
                                            :options="$centers" placeholder="All Centers" single clearable searchable
                                            :disabled="empty($centers)" />
                                    @endif
                                </div>

                                @if (hasAuthRole(RolesEnum::Admin->value) && $selectedCourse)
                                    <div class="mb-4">
                                        <x-button label="Reload Students" icon="o-arrow-path"
                                            class="btn-outline btn-sm" wire:click="reloadStudents" />

                                        <span class="text-xs text-base-content/60 ml-2">
                                            Center: {{ $selectedCenter ? 'Selected' : 'All Centers' }} |
                                            Students: {{ count($students) }} |
                                            Total: {{ $totalStudents }}
                                        </span>
                                    </div>
                                @endif

                                @if (!empty($filteredStudents))
                                    <div
                                        class="flex items-center gap-2 p-3 bg-base-200 dark:bg-base-300 rounded-lg mb-4">
                                        <x-checkbox wire:model.live="selectAllStudents"
                                            label="Select All ({{ count($filteredStudents) }} students)" />
                                    </div>
                                @endif

                                @if (!empty($selectedStudents))
                                    <div class="mb-4">
                                        <h4 class="text-sm font-semibold text-primary mb-2">
                                            Selected Students ({{ count($selectedStudents) }})
                                        </h4>
                                        <div class="flex flex-wrap gap-2">
                                            @foreach ($selectedStudents as $selectedId)
                                                @php
                                                    $selectedStudent = collect($students)->firstWhere(
                                                        'id',
                                                        $selectedId,
                                                    );
                                                @endphp
                                                @if ($selectedStudent)
                                                    <div class="badge badge-secondary gap-2">
                                                        {{ $selectedStudent['name'] }}
                                                        <button type="button"
                                                            wire:click="removeStudent({{ $selectedId }})"
                                                            class="btn btn-xs btn-circle btn-ghost">
                                                            <x-icon name="o-x-mark" class="w-3 h-3" />
                                                        </button>
                                                    </div>
                                                @endif
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                @if (!empty($filteredStudents))
                                    <div
                                        class="max-h-96 overflow-y-auto border border-base-300 dark:border-base-200 rounded-lg">
                                        <div class="divide-y divide-base-300 dark:divide-base-200">
                                            @foreach ($filteredStudents as $student)
                                                <div
                                                    class="p-3 hover:bg-base-200 dark:hover:bg-base-300 transition-colors {{ in_array($student['id'], $selectedStudents) ? 'bg-primary/5' : '' }}">
                                                    <x-checkbox wire:model.live="selectedStudents"
                                                        value="{{ $student['id'] }}"
                                                        label="{{ $student['name'] }}" />
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>

                                    @if ($hasMoreStudents && empty($studentSearch))
                                        <div class="flex justify-center mt-4">
                                            <x-button label="Load More Students" icon="o-arrow-down"
                                                class="btn-outline btn-sm" wire:click="loadMoreStudents"
                                                spinner="loadMoreStudents" />
                                        </div>
                                    @endif
                                @elseif($selectedCourse && empty($students))
                                    <div class="text-center py-12">
                                        <x-icon name="o-users" class="w-16 h-16 mx-auto mb-4 text-base-content/40" />
                                        <p class="text-base-content/60">No students found for this course.</p>
                                    </div>
                                @elseif($selectedCourse && !empty($students) && empty($filteredStudents))
                                    <div class="text-center py-12">
                                        <x-icon name="o-magnifying-glass"
                                            class="w-16 h-16 mx-auto mb-4 text-base-content/40" />
                                        <p class="text-base-content/60">No students match your search criteria.</p>
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>

                    <!-- Step 2 Actions -->
                    <div class="flex justify-between mt-8 pt-6 border-t border-base-300 dark:border-base-200">
                        <x-button label="Previous" icon="o-arrow-left" class="btn-outline"
                            wire:click="previousStep" />
                        <x-button label="Next: Schedule Details" icon="o-arrow-right" class="btn-primary"
                            wire:click="nextStep" :disabled="!$this->validateCurrentStep()" />
                    </div>
                </div>
            @elseif($currentStep == 3)
                <div class="p-6">
                    <div class="mb-6">
                        <h2 class="text-2xl font-bold text-primary mb-2">Step 3: Schedule Details</h2>
                        <p class="text-base-content/70 dark:text-base-100/70">Set the date, time, and duration for the
                            exam</p>
                    </div>

                    <div class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <x-datepicker label="Exam Date" wire:model="date" icon="o-calendar" :config="$dateConfig" />

                            <x-input label="Duration (minutes)" wire:model.live="duration"
                                wire:change="calculateEndTime" type="number" min="15" max="300"
                                icon="o-clock" required />

                            <x-input label="Start Time" wire:model.live="startTime" wire:change="calculateEndTime"
                                type="time" icon="o-play" required />

                            <x-input label="End Time" wire:model="endTime" type="time" icon="o-stop" required />
                        </div>

                        <x-card class="bg-base-200 dark:bg-base-300">
                            <div class="p-4">
                                <h3 class="text-lg font-semibold text-primary mb-3">Exam Summary</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                                    <div>
                                        <span class="font-medium">Course:</span>
                                        <span
                                            class="ml-2">{{ Course::find($selectedCourse)->name ?? 'Not selected' }}</span>
                                    </div>
                                    <div>
                                        <span class="font-medium">Categories:</span>
                                        <span class="ml-2">{{ count($selectedCategories) }} selected</span>
                                    </div>
                                    <div>
                                        <span class="font-medium">Students:</span>
                                        <span class="ml-2">{{ count($selectedStudents) }} selected</span>
                                    </div>
                                    <div>
                                        <span class="font-medium">Duration:</span>
                                        <span class="ml-2">{{ $duration }} minutes</span>
                                    </div>
                                </div>
                            </div>
                        </x-card>
                    </div>

                    <div class="flex justify-between mt-8 pt-6 border-t border-base-300 dark:border-base-200">
                        <x-button label="Previous" icon="o-arrow-left" class="btn-outline"
                            wire:click="previousStep" />
                        <x-button label="Schedule Exam" icon="o-calendar" class="btn-primary btn-lg"
                            wire:click="scheduleExam" spinner="scheduleExam" :disabled="!$this->validateCurrentStep()" />
                    </div>
                </div>
            @endif
        </x-card>
    </div>

    @if ($showSuccessModal)
        <x-modal wire:model="showSuccessModal" class="backdrop-blur">
            <x-card class="max-w-4xl">
                <div class="p-6">
                    <div class="text-center mb-6">
                        <x-icon name="o-check-circle" class="w-16 h-16 text-success mx-auto mb-4" />
                        <h2 class="text-2xl font-bold text-success mb-2">Exam Scheduled Successfully!</h2>
                        <p class="text-base-content/70">Exam has been scheduled for {{ count($scheduledExams) }}
                            student(s)</p>
                    </div>

                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-primary mb-4">Student Credentials</h3>
                        <div class="max-h-96 overflow-y-auto">
                            @if (count($scheduledExams) <= 5)
                                <div class="space-y-3">
                                    @foreach ($scheduledExams as $exam)
                                        <x-card class="bg-base-200 dark:bg-base-300">
                                            <div class="p-4">
                                                <div class="flex justify-between items-start mb-3">
                                                    <h4 class="font-semibold text-primary">{{ $exam['student_name'] }}
                                                    </h4>
                                                    <x-button label="Copy Credentials" icon="o-clipboard"
                                                        class="btn-primary btn-sm"
                                                        wire:click="copyStudentCredentials('{{ $exam['student_name'] }}', '{{ $exam['user_id'] }}', '{{ $exam['password'] }}')" />
                                                </div>
                                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                                                    <div><strong>Exam ID:</strong> {{ $exam['exam_id'] }}</div>
                                                    <div><strong>User ID:</strong> {{ $exam['user_id'] }}</div>
                                                    <div><strong>Password:</strong> {{ $exam['password'] }}</div>
                                                    <div><strong>Categories:</strong> {{ $exam['categories'] }}</div>
                                                </div>
                                            </div>
                                        </x-card>
                                    @endforeach
                                </div>
                            @else
                                <div class="space-y-3">
                                    @foreach (array_slice($scheduledExams, 0, 5) as $exam)
                                        <x-card class="bg-base-200 dark:bg-base-300">
                                            <div class="p-4">
                                                <div class="flex justify-between items-start mb-3">
                                                    <h4 class="font-semibold text-primary">{{ $exam['student_name'] }}
                                                    </h4>
                                                    <x-button label="Copy Credentials" icon="o-clipboard"
                                                        class="btn-primary btn-sm"
                                                        wire:click="copyStudentCredentials('{{ $exam['student_name'] }}', '{{ $exam['user_id'] }}', '{{ $exam['password'] }}')" />
                                                </div>
                                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                                                    <div><strong>Exam ID:</strong> {{ $exam['id'] }}</div>
                                                    <div><strong>User ID:</strong> {{ $exam['user_id'] }}</div>
                                                    <div><strong>Password:</strong> {{ $exam['password'] }}</div>
                                                    <div><strong>Categories:</strong> {{ $exam['categories'] }}</div>
                                                </div>
                                            </div>
                                        </x-card>
                                    @endforeach
                                    <div class="text-center p-4 bg-info/10 rounded-lg">
                                        <p class="text-info font-medium">
                                            And {{ count($scheduledExams) - 5 }} more students.
                                            Download CSV for complete list.
                                        </p>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="flex justify-between">
                        <x-button label="Download CSV" icon="o-arrow-down-tray" class="btn-outline"
                            wire:click="exportCredentials" />
                        <x-button label="Close" icon="o-x-mark" class="btn-primary"
                            wire:click="closeSuccessModal" />
                    </div>
                </div>
            </x-card>
        </x-modal>
    @endif
</div>
