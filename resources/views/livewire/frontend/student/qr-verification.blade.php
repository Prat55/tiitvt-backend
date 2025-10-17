<?php

use Carbon\Carbon;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Services\StudentQRService;
use App\Models\{Student, StudentQR};

new class extends Component {
    #[Layout('components.layouts.guest')]
    public $studentQR;
    public $tiitvt_reg_no = '';
    public $date_of_birth = '';
    public $student = null;
    public $verified = false;
    public $error_message = '';

    public function mount($token)
    {
        $studentQRService = app(StudentQRService::class);
        $this->studentQR = $studentQRService->verifyStudentQR($token);
        $this->tiitvt_reg_no = $this->studentQR->student->tiitvt_reg_no;

        if (!$this->studentQR) {
            abort(404, 'Student QR code not found or has been deactivated.');
        }

        // Check if student is already verified in session
        if (session()->has('qr_verified_student_' . $this->studentQR->student_id)) {
            $this->student = session('qr_verified_student_' . $this->studentQR->student_id);
            $this->verified = true;
        }
    }

    public function verifyStudent()
    {
        $this->reset(['error_message']);

        // Validate inputs
        if (empty($this->tiitvt_reg_no) || empty($this->date_of_birth)) {
            $this->error_message = 'Please enter Date of Birth.';
            return;
        }

        // Find student by registration number and date of birth
        $student = Student::select('id', 'tiitvt_reg_no', 'date_of_birth', 'first_name', 'fathers_name', 'surname', 'center_id', 'enrollment_date', 'course_fees', 'down_payment', 'no_of_installments')
            ->where('tiitvt_reg_no', $this->tiitvt_reg_no)
            ->where('date_of_birth', $this->date_of_birth)
            ->with(['center', 'courses', 'examResults.exam.course', 'examResults.category'])
            ->first();

        if (!$student) {
            $this->error_message = 'Invalid Date of Birth. Please check your details and try again.';
            return;
        }

        // Verify that this student matches the QR code
        if ($student->id !== $this->studentQR->student_id) {
            $this->error_message = 'The provided details do not match this QR code.';
            return;
        }

        $this->student = $student;
        $this->verified = true;

        // Store student data in session for persistence across page refreshes
        session(['qr_verified_student_' . $student->id => $student]);
    }

    public function resetForm()
    {
        $this->reset(['tiitvt_reg_no', 'date_of_birth', 'student', 'verified', 'error_message']);

        // Clear session data when form is reset
        if ($this->studentQR && $this->studentQR->student_id) {
            session()->forget('qr_verified_student_' . $this->studentQR->student_id);
        }
    }

    /**
     * Calculate grade based on percentage
     */
    private function calculateGrade($percentage)
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
        return 'F';
    }

    /**
     * Logout and clear session data
     */
    public function logout()
    {
        // Clear session data
        if ($this->studentQR && $this->studentQR->student_id) {
            session()->forget('qr_verified_student_' . $this->studentQR->student_id);
        }

        // Reset component state
        $this->reset(['student', 'verified', 'error_message']);
    }
}; ?>
@section('cdn')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
@endsection
<div class="min-h-screen bg-base-300 py-4 px-2 sm:py-8 sm:px-4 lg:px-8">
    <div class="max-w-4xl mx-auto">
        <!-- Header Card -->
        <div class="bg-base-100 shadow-xl rounded-xl sm:rounded-2xl overflow-hidden relative mb-4 sm:mb-8">
            <div class="absolute top-2 right-2 sm:top-4 sm:right-4 flex gap-1 sm:gap-2">
                @if ($verified)
                    <x-button icon="o-arrow-right-on-rectangle" wire:click="logout"
                        class="w-8 h-8 sm:w-12 sm:h-12 btn-xs sm:btn-sm btn-ghost btn-circle text-white"
                        tooltip-left="Logout" />
                @endif
                <x-theme-toggle class="w-8 h-8 sm:w-12 sm:h-12 btn-xs sm:btn-sm text-white" lightTheme="light"
                    darkTheme="dark" />
            </div>

            <!-- Header -->
            <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-4 py-4 sm:px-8 sm:py-6">
                <div class="text-center">
                    <div
                        class="mx-auto flex items-center justify-center h-12 w-12 sm:h-16 sm:w-16 rounded-full bg-white/20 mb-3 sm:mb-4">
                        <x-icon name="o-academic-cap" class="h-6 w-6 sm:h-8 sm:w-8 text-white" />
                    </div>
                    <h1 class="text-xl sm:text-2xl lg:text-3xl font-bold text-white mb-2">
                        Student Verification Portal
                    </h1>
                    <p class="text-blue-100 text-sm sm:text-base lg:text-lg">
                        TIITVT - Technical Institute of Information Technology & Vocational Training
                    </p>
                </div>
            </div>

            <div class="p-4 sm:p-8">
                @if (!$verified)
                    <!-- Verification Form -->
                    <div class="max-w-md mx-auto">
                        <div class="text-center mb-6 sm:mb-8">
                            <div
                                class="mx-auto flex items-center justify-center h-12 w-12 sm:h-16 sm:w-16 rounded-full bg-blue-100 mb-3 sm:mb-4">
                                <x-icon name="o-identification" class="h-6 w-6 sm:h-8 sm:w-8 text-blue-600" />
                            </div>
                            <h2 class="text-lg sm:text-2xl font-semibold mb-2">Verify Your Identity</h2>
                            <p class="text-sm sm:text-base">
                                Enter your TIITVT Registration Number and Date of Birth to access your academic records
                            </p>
                        </div>

                        @if ($error_message)
                            <x-alert class="alert-error mb-6" title="Verification Failed"
                                description="{{ $error_message }}" />
                        @endif

                        <form wire:submit.prevent="verifyStudent" class="space-y-6">
                            <div>
                                <x-input label="TIITVT Registration Number" id="tiitvt_reg_no"
                                    wire:model="tiitvt_reg_no" icon="o-identification"
                                    placeholder="Enter your TIITVT Registration Number" readonly />
                            </div>

                            <div>
                                <x-datepicker label="Date of Birth" wire:model="date_of_birth" icon="o-calendar"
                                    required />
                            </div>

                            <div class="flex space-x-4 justify-center">
                                <x-button label="Verify Identity" type="submit" icon="o-check"
                                    class="btn-primary btn-lg" spinner="verifyStudent" />
                                <x-button label="Reset" type="button" icon="o-x-mark" class="btn-outline btn-lg"
                                    wire:click="resetForm" spinner="resetForm" />
                            </div>
                        </form>
                    </div>
                @else
                    <!-- Student Information Display -->
                    <div class="space-y-4 sm:space-y-8">
                        <!-- Student Profile Header -->
                        <x-card class="bg-base-200">
                            <div class="flex flex-col sm:flex-row items-center space-y-4 sm:space-y-0 sm:space-x-6">
                                <div class="flex-shrink-0">
                                    <div
                                        class="h-16 w-16 sm:h-20 sm:w-20 rounded-full bg-gradient-to-r from-green-500 to-emerald-500 flex items-center justify-center">
                                        <x-icon name="o-user" class="h-8 w-8 sm:h-10 sm:w-10 text-white" />
                                    </div>
                                </div>
                                <div class="flex-1 text-center sm:text-left">
                                    <h2 class="text-2xl sm:text-3xl font-bold mb-1">
                                        {{ $student->first_name }}
                                        @if ($student->fathers_name)
                                            {{ $student->fathers_name }}
                                        @endif
                                        @if ($student->surname)
                                            {{ $student->surname }}
                                        @endif
                                    </h2>
                                    <p class="text-lg sm:text-xl mb-2">{{ $student->tiitvt_reg_no }}</p>
                                    <div
                                        class="flex flex-col sm:flex-row items-center sm:space-x-4 space-y-2 sm:space-y-0 text-sm">
                                        <span class="flex items-center">
                                            <x-icon name="o-building-office" class="h-4 w-4 mr-1" />
                                            {{ $student->center->name ?? 'N/A' }}
                                        </span>
                                        @if ($student->enrollment_date)
                                            <span class="flex items-center">
                                                <x-icon name="o-calendar" class="h-4 w-4 mr-1" />
                                                Enrolled:
                                                {{ \Carbon\Carbon::parse($student->enrollment_date)->format('d M Y') }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </x-card>

                        <!-- Information Grid -->
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6">
                            <!-- Personal Information -->
                            <x-card title="Personal Information" class="bg-base-200">
                                <div class="space-y-3 sm:space-y-4">
                                    <div class="flex justify-between items-center py-2 border-b border-base-300">
                                        <span class="text-xs sm:text-sm font-medium">Full Name</span>
                                        <span class="text-xs sm:text-sm font-semibold text-right">
                                            {{ $student->first_name }}
                                            @if ($student->fathers_name)
                                                {{ $student->fathers_name }}
                                            @endif
                                            @if ($student->surname)
                                                {{ $student->surname }}
                                            @endif
                                        </span>
                                    </div>
                                    <div class="flex justify-between items-center py-2 border-b border-base-100">
                                        <span class="text-xs sm:text-sm font-medium">Registration Number</span>
                                        <span
                                            class="text-xs sm:text-sm font-semibold">{{ $student->tiitvt_reg_no }}</span>
                                    </div>
                                    @if ($student->date_of_birth)
                                        <div class="flex justify-between items-center py-2 border-b border-base-100">
                                            <span class="text-xs sm:text-sm font-medium">Date of Birth</span>
                                            <span
                                                class="text-xs sm:text-sm font-semibold">{{ \Carbon\Carbon::parse($student->date_of_birth)->format('d M Y') }}</span>
                                        </div>
                                    @endif
                                    @if ($student->email)
                                        <div class="flex justify-between items-center py-2 border-b border-base-100">
                                            <span class="text-xs sm:text-sm font-medium">Email</span>
                                            <span class="text-xs sm:text-sm font-semibold">{{ $student->email }}</span>
                                        </div>
                                    @endif
                                    @if ($student->mobile)
                                        <div class="flex justify-between items-center py-2 border-b border-base-100">
                                            <span class="text-xs sm:text-sm font-medium">Mobile</span>
                                            <span
                                                class="text-xs sm:text-sm font-semibold">{{ $student->mobile }}</span>
                                        </div>
                                    @endif
                                </div>
                            </x-card>

                            <!-- Academic Information -->
                            <x-card title="Academic Information" class="bg-base-200">
                                <div class="space-y-3 sm:space-y-4">
                                    <div class="flex justify-between items-center py-2 border-b border-base-100">
                                        <span class="text-xs sm:text-sm font-medium">Center</span>
                                        <span
                                            class="text-xs sm:text-sm font-semibold">{{ $student->center->name ?? 'N/A' }}</span>
                                    </div>
                                    @if ($student->enrollment_date)
                                        <div class="flex justify-between items-center py-2 border-b border-base-100">
                                            <span class="text-xs sm:text-sm font-medium">Enrollment Date</span>
                                            <span
                                                class="text-xs sm:text-sm font-semibold">{{ \Carbon\Carbon::parse($student->enrollment_date)->format('d M Y') }}</span>
                                        </div>
                                    @endif
                                    @if ($student->qualification)
                                        <div class="flex justify-between items-center py-2 border-b border-base-100">
                                            <span class="text-xs sm:text-sm font-medium">Qualification</span>
                                            <span
                                                class="text-xs sm:text-sm font-semibold">{{ $student->qualification }}</span>
                                        </div>
                                    @endif
                                    @if ($student->additional_qualification)
                                        <div class="flex justify-between items-center py-2 border-b border-base-100">
                                            <span class="text-xs sm:text-sm font-medium">Additional
                                                Qualification</span>
                                            <span
                                                class="text-xs sm:text-sm font-semibold">{{ $student->additional_qualification }}</span>
                                        </div>
                                    @endif
                                </div>
                            </x-card>
                        </div>

                        <!-- Courses Enrolled -->
                        @if ($student->courses && $student->courses->count() > 0)
                            <x-card title="Enrolled Courses" class="bg-base-200">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                                    @foreach ($student->courses as $course)
                                        <div class="bg-base-100 rounded-lg p-3 sm:p-4">
                                            <div class="flex items-start space-x-3">
                                                <div class="flex-shrink-0">
                                                    <div
                                                        class="h-8 w-8 sm:h-10 sm:w-10 rounded-lg bg-blue-500 flex items-center justify-center">
                                                        <x-icon name="o-book-open"
                                                            class="h-4 w-4 sm:h-5 sm:w-5 text-white" />
                                                    </div>
                                                </div>
                                                <div class="flex-1 min-w-0">
                                                    <h4 class="text-sm sm:text-base font-semibold mb-1 truncate">
                                                        {{ $course->name }}
                                                    </h4>
                                                    @if ($course->pivot->enrollment_date)
                                                        <p class="text-xs sm:text-sm">
                                                            Enrolled:
                                                            {{ \Carbon\Carbon::parse($course->pivot->enrollment_date)->format('d M Y') }}
                                                        </p>
                                                    @endif
                                                    @if ($course->pivot->batch_time)
                                                        <p class="text-xs sm:text-sm">
                                                            Batch: {{ $course->pivot->batch_time }}
                                                        </p>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </x-card>
                        @endif

                        <!-- Exam Results -->
                        @if ($student->examResults && $student->examResults->count() > 0)
                            <x-card title="Exam Results" class="bg-base-200">
                                @php
                                    $groupedResults = $student->examResults->groupBy('exam_id');
                                @endphp

                                <div class="space-y-4 sm:space-y-6">
                                    @foreach ($groupedResults as $examId => $examResults)
                                        @php
                                            $exam = $examResults->first()->exam;
                                            $overallPercentage = $examResults->avg('percentage');
                                            $totalMarks =
                                                $examResults->sum('total_points') ?: $examResults->count() * 100;
                                            $totalMarksObtained =
                                                $examResults->sum('points_earned') ?: $examResults->sum('score');
                                            $overallGrade = $this->calculateGrade($overallPercentage);
                                        @endphp

                                        <div class="bg-base-100 rounded-xl p-4 sm:p-6">
                                            <div
                                                class="flex flex-col sm:flex-row sm:justify-between sm:items-start mb-4 space-y-3 sm:space-y-0">
                                                <div class="flex-1">
                                                    <h4 class="text-lg sm:text-xl font-bold mb-1">
                                                        {{ $exam->course->name }}</h4>
                                                    <p class="text-sm sm:text-base mb-1">{{ $exam->name }}</p>
                                                    <p class="text-xs sm:text-sm">
                                                        {{ $exam->date ? \Carbon\Carbon::parse($exam->date)->format('d M Y') : 'Date not set' }}
                                                    </p>
                                                </div>
                                                <div class="text-center sm:text-right">
                                                    <div class="text-2xl sm:text-3xl font-bold mb-1">
                                                        {{ number_format($overallPercentage, 1) }}%
                                                    </div>
                                                    <div class="text-base sm:text-lg font-semibold mb-1">
                                                        {{ $overallGrade }}</div>
                                                    <div class="text-xs sm:text-sm">
                                                        {{ $totalMarksObtained }}/{{ $totalMarks }} marks
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Category-wise Results -->
                                            <div class="space-y-3">
                                                <h5 class="text-xs sm:text-sm font-semibold uppercase tracking-wide">
                                                    Category-wise Results</h5>
                                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 sm:gap-3">
                                                    @foreach ($examResults as $result)
                                                        @php
                                                            $categoryGrade = $this->calculateGrade($result->percentage);
                                                        @endphp
                                                        <div
                                                            class="flex justify-between items-center bg-base-300 rounded-lg px-3 py-2 sm:px-4 sm:py-3">
                                                            <div>
                                                                <span class="text-xs sm:text-sm font-medium">
                                                                    {{ $result->category->name ?? 'Unknown Category' }}
                                                                </span>
                                                            </div>
                                                            <div class="text-right">
                                                                <span class="text-xs sm:text-sm font-semibold">
                                                                    {{ number_format($result->percentage, 1) }}%
                                                                </span>
                                                                <span class="text-xs ml-2">{{ $categoryGrade }}</span>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </x-card>
                        @else
                            <x-card title="Exam Results" class="bg-base-200">
                                <div class="text-center py-8 sm:py-12">
                                    <x-icon name="o-document-text"
                                        class="h-12 w-12 sm:h-16 sm:w-16 mx-auto mb-3 sm:mb-4" />
                                    <h3 class="text-base sm:text-lg font-semibold mb-2">No Exam Results Available</h3>
                                    <p class="text-sm sm:text-base">Exam results will appear here once you complete
                                        your examinations.</p>
                                </div>
                            </x-card>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center text-xs sm:text-sm mt-4 sm:mt-8">
            <p class="flex items-center justify-center space-x-2">
                <x-icon name="o-shield-check" class="h-3 w-3 sm:h-4 sm:w-4" />
                <span>Secure Student Verification System</span>
            </p>
            <p class="mt-1">Generated on {{ now()->format('F d, Y \a\t g:i A') }}</p>
        </div>
    </div>
</div>
