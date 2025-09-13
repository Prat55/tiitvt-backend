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

        if (!$this->studentQR) {
            abort(404, 'Student QR code not found or has been deactivated.');
        }
    }

    public function verifyStudent()
    {
        $this->reset(['error_message']);

        // Validate inputs
        if (empty($this->tiitvt_reg_no) || empty($this->date_of_birth)) {
            $this->error_message = 'Please enter both TIITVT Registration Number and Date of Birth.';
            return;
        }

        // Find student by registration number and date of birth
        $student = Student::where('tiitvt_reg_no', $this->tiitvt_reg_no)
            ->where('date_of_birth', $this->date_of_birth)
            ->with(['center', 'course'])
            ->first();

        if (!$student) {
            $this->error_message = 'Invalid TIITVT Registration Number or Date of Birth. Please check your details and try again.';
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
                            <div class="bg-error border border-error-content rounded-md p-4">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <x-icon name="o-x-mark" class="h-5 w-5 text-error-content" />
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm text-error">{{ $error_message }}</p>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <form wire:submit.prevent="verifyStudent" class="space-y-4">
                            <div>
                                <x-input lable="TIITVT Registration Number" id="tiitvt_reg_no"
                                    wire:model="tiitvt_reg_no" icon="o-identification"
                                    placeholder="Enter your TIITVT Registration Number" required />
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
                            <h2 class="text-lg font-medium text-gray-900">Verification Successful</h2>
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
                                    <label class="text-sm font-medium text-gray-500">Course</label>
                                    <p class="text-lg font-semibold text-primary-content">
                                        {{ $student->course->name ?? 'N/A' }}
                                    </p>
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

                        <div class="text-center">
                            <x-button label="Verify Another Student" type="button" icon="o-check" class="btn-primary"
                                wire:click="resetForm" />
                        </div>
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
