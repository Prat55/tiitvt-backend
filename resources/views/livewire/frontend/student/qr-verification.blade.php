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
    }

    public function resetForm()
    {
        $this->reset(['tiitvt_reg_no', 'date_of_birth', 'student', 'verified', 'error_message']);
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
}; ?>
@section('cdn')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
@endsection
<div class="min-h-screen py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md mx-auto">
        <div class="bg-base-100 shadow-lg rounded-lg overflow-hidden relative">
            <div class="absolute top-0 right-0">
                <x-theme-toggle class="w-12 h-12 btn-sm" lightTheme="light" darkTheme="dark" />
            </div>

            <!-- Header -->
            <div class="bg-primary px-6 py-4">
                <h1 class="text-xl font-semibold text-white text-center">
                    Student Verification
                </h1>
                <p class="text-primary-content text-sm text-center mt-1">
                    Verify your identity to view registration details
                </p>
            </div>

            <div class="p-6">
                @if (!$verified)
                    <!-- Verification Form -->
                    <div class="space-y-6">
                        <div class="text-center">
                            <div
                                class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-primary mb-4">
                                <x-icon name="o-identification" class="h-6 w-6 text-primary-content" />
                            </div>
                            <h2 class="text-lg font-medium text-primary-content">Verify Your Identity</h2>
                            <p class="text-sm text-gray-600 mt-2">
                                Please enter your TIITVT Registration Number and Date of Birth to view your registration
                                details.
                            </p>
                        </div>

                        @if ($error_message)
                            <x-alert class="alert-error" title="Oops! Something went wrong"
                                description="{{ $error_message }}" />
                        @endif

                        <form wire:submit.prevent="verifyStudent" class="space-y-4">
                            <div>
                                <x-input lable="TIITVT Registration Number" id="tiitvt_reg_no"
                                    wire:model="tiitvt_reg_no" icon="o-identification"
                                    placeholder="Enter your TIITVT Registration Number" readonly />
                            </div>

                            <div>
                                <x-datepicker label="Date of Birth" wire:model="date_of_birth" icon="o-calendar"
                                    required />
                            </div>

                            <div class="flex space-x-3 justify-center">
                                <x-button label="Verify Identity" type="submit" icon="o-check" class="btn-primary"
                                    spinner="verifyStudent" />

                                <x-button label="Reset" type="button" icon="o-x-mark" class="btn-error"
                                    wire:click="resetForm" spinner="resetForm" />
                            </div>
                        </form>
                    </div>
                @else
                    <!-- Student Details Display -->
                    <div class="space-y-6">
                        <div class="text-center">
                            <div
                                class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-primary mb-4">
                                <x-icon name="o-check" class="h-6 w-6 text-primary-content" />
                            </div>
                            <h2 class="text-lg font-medium text-primary-content">Verification Successful</h2>
                            <p class="text-sm text-gray-600 mt-2">
                                Your identity has been verified. Here are your registration details:
                            </p>
                        </div>

                        <div class="bg-base-100 rounded-lg p-4 space-y-4">
                            <div class="grid grid-cols-1 gap-4">
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Full Name</label>
                                    <p class="text-lg font-semibold text-primary-content">{{ $student->full_name }}</p>
                                </div>

                                <div>
                                    <label class="text-sm font-medium text-gray-500">TIITVT Registration Number</label>
                                    <p class="text-lg font-semibold text-primary-content">{{ $student->tiitvt_reg_no }}
                                    </p>
                                </div>

                                <div>
                                    <label
                                        class="text-sm font-medium text-gray-500">Course{{ $student->courses->count() > 1 ? 's' : '' }}</label>
                                    @if ($student->courses->count() > 0)
                                        @if ($student->courses->count() == 1)
                                            <p class="text-lg font-semibold text-primary-content">
                                                {{ $student->courses->first()->name }}</p>
                                        @else
                                            <div class="space-y-1">
                                                <p class="text-lg font-semibold text-primary-content">
                                                    {{ $student->courses->first()->name }}</p>
                                                <p class="text-sm text-gray-400">+{{ $student->courses->count() - 1 }}
                                                    more course{{ $student->courses->count() > 2 ? 's' : '' }}</p>
                                            </div>
                                        @endif
                                    @else
                                        <p class="text-lg font-semibold text-primary-content">N/A</p>
                                    @endif
                                </div>

                                <div>
                                    <label class="text-sm font-medium text-gray-500">Center</label>
                                    <p class="text-lg font-semibold text-primary-content">
                                        {{ $student->center->name ?? 'N/A' }}
                                    </p>
                                </div>

                                <div>
                                    <label class="text-sm font-medium text-gray-500">Enrollment Date</label>
                                    <p class="text-lg font-semibold text-primary-content">
                                        {{ $student->enrollment_date ? $student->enrollment_date->format('d M Y') : 'N/A' }}
                                    </p>
                                </div>

                                <div>
                                    <label class="text-sm font-medium text-gray-500">Course Fees</label>
                                    <p class="text-lg font-semibold text-primary-content">
                                        ₹{{ number_format($student->course_fees, 2) }}
                                    </p>
                                </div>

                                @if ($student->down_payment)
                                    <div>
                                        <label class="text-sm font-medium text-gray-500">Down Payment</label>
                                        <p class="text-lg font-semibold text-primary-content">
                                            ₹{{ number_format($student->down_payment, 2) }}
                                        </p>
                                    </div>
                                @endif

                                @if ($student->no_of_installments)
                                    <div>
                                        <label class="text-sm font-medium text-gray-500">Installments</label>
                                        <p class="text-lg font-semibold text-primary-content">
                                            {{ $student->no_of_installments }} installments
                                        </p>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Exam Results and Certificates Section -->
                        @if ($student->examResults && $student->examResults->count() > 0)
                            <div class="bg-base-100 rounded-lg p-4 space-y-4">
                                <h3 class="text-lg font-semibold text-primary-content border-b pb-2">
                                    <x-icon name="o-academic-cap" class="inline w-5 h-5 mr-2" />
                                    Exam Results & Certificates
                                </h3>

                                @php
                                    $groupedResults = $student->examResults->groupBy('exam_id');
                                @endphp

                                @foreach ($groupedResults as $examId => $examResults)
                                    @php
                                        $exam = $examResults->first()->exam;
                                        $overallPercentage = $examResults->avg('percentage');
                                        $totalMarks = $examResults->sum('total_points') ?: $examResults->count() * 100;
                                        $totalMarksObtained =
                                            $examResults->sum('points_earned') ?: $examResults->sum('score');
                                        $overallGrade = $this->calculateGrade($overallPercentage);
                                    @endphp

                                    <div class="border rounded-lg p-4 space-y-3">
                                        <div class="flex justify-between items-start">
                                            <div>
                                                <h4 class="font-semibold text-primary-content">
                                                    {{ $exam->course->name }}</h4>
                                                <p class="text-sm text-gray-600">{{ $exam->name }}</p>
                                                <p class="text-xs text-gray-500">
                                                    {{ $exam->date ? $exam->date->format('d M Y') : 'Date not set' }}
                                                </p>
                                            </div>
                                            <div class="text-right">
                                                <div class="text-2xl font-bold text-primary">
                                                    {{ number_format($overallPercentage, 1) }}%</div>
                                                <div class="text-sm font-semibold text-gray-600">{{ $overallGrade }}
                                                </div>
                                                <div class="text-xs text-gray-500">
                                                    {{ $totalMarksObtained }}/{{ $totalMarks }} marks
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Category-wise Results -->
                                        <div class="space-y-2">
                                            <h5 class="text-sm font-medium text-gray-700">Category-wise Results:</h5>
                                            <div class="grid grid-cols-1 gap-2">
                                                @foreach ($examResults as $result)
                                                    @php
                                                        $categoryGrade = $this->calculateGrade($result->percentage);
                                                    @endphp
                                                    <div
                                                        class="flex justify-between items-center bg-gray-50 rounded px-3 py-2">
                                                        <div>
                                                            <span
                                                                class="text-sm font-medium">{{ $result->category->name ?? 'Unknown Category' }}</span>
                                                        </div>
                                                        <div class="text-right">
                                                            <span
                                                                class="text-sm font-semibold">{{ number_format($result->percentage, 1) }}%</span>
                                                            <span
                                                                class="text-xs text-gray-600 ml-2">{{ $categoryGrade }}</span>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>

                                        <!-- Certificate Link -->
                                        <div class="pt-2 border-t">
                                            <a href="{{ route('certificate.exam.preview', str_replace('/', '_', $student->tiitvt_reg_no)) }}"
                                                target="_blank"
                                                class="inline-flex items-center text-sm text-primary hover:text-primary-focus font-medium">
                                                <x-icon name="o-document" class="w-4 h-4 mr-1" />
                                                View Certificate
                                            </a>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="bg-base-100 rounded-lg p-4 space-y-4">
                                <h3 class="text-lg font-semibold text-primary-content border-b pb-2">
                                    <x-icon name="o-academic-cap" class="inline w-5 h-5 mr-2" />
                                    Exam Results & Certificates
                                </h3>
                                <div class="text-center py-8">
                                    <x-icon name="o-document-text" class="w-12 h-12 text-gray-400 mx-auto mb-3" />
                                    <p class="text-gray-500">No exam results available yet.</p>
                                    <p class="text-sm text-gray-400">Results will appear here once exams are completed.
                                    </p>
                                </div>
                            </div>
                        @endif
                    </div>
                @endif
            </div>

            <!-- Footer -->
            <div class="bg-base-100 px-6 py-4 text-center">
                <p class="text-xs text-gray-500">
                    This verification system is secure and your information is protected.
                </p>
            </div>
        </div>
    </div>
</div>
