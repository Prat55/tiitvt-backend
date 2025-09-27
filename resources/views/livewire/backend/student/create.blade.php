<?php

use Mary\Traits\Toast;
use Illuminate\View\View;
use Illuminate\Support\Str;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use App\Services\StudentQRService;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\{Layout, Title};
use App\Models\{Course, Center, Student};
use App\Enums\{RolesEnum, InstallmentStatusEnum};

new class extends Component {
    use WithFileUploads, Toast;
    #[Title('Student Admission')]
    public function mount(): void
    {
        // Initialize with default values and calculate installments if needed
        if (isset($this->course_fees) && $this->course_fees > 0) {
            $this->calculateInstallments();
        }

        if (hasAuthRole(RolesEnum::Center->value)) {
            $this->center_id = auth()->user()->center->id;
        }
    }

    public string $tiitvt_reg_no = '';
    public string $first_name = '';
    public string $fathers_name = '';
    public string $surname = '';
    public $config = [
        'aspectRatio' => 1,
    ];

    // Contact Information
    public array $address = [
        'street' => '',
        'city' => '',
        'state' => '',
        'pincode' => '',
        'country' => '',
    ];
    public string $telephone_no = '';
    public string $email = '';
    public string $mobile = '';

    // Personal Information
    public string $date_of_birth = '';
    public int $age = 0;

    // Academic Information
    public string $qualification = '';
    public string $additional_qualification = '';
    public string $reference = '';

    // Course and Batch Information
    public string $course_taken = '';
    public string $batch_time = '';
    public string $scheme_given = '';

    // Fees Information
    public float $course_fees = 0;
    public float $down_payment = 0;
    public int $no_of_installments = 0;
    public string $installment_date = '';

    // Calculated fields for display
    public float $remaining_amount = 0;
    public float $total_payable = 0;
    public array $installment_breakdown = [];

    // Installment editing
    public bool $edit_installment_amounts = false;
    public array $editable_installment_amounts = [];

    // Notification
    public bool $send_notification = false;

    // Computed properties for validation
    public function getFeesValidationErrors(): array
    {
        $errors = [];

        if ($this->down_payment > $this->course_fees) {
            $errors[] = 'Down payment cannot exceed course fees';
        }

        if ($this->no_of_installments > 24) {
            $errors[] = 'Maximum 24 installments allowed';
        }

        if ($this->no_of_installments > 0 && $this->remaining_amount <= 0) {
            $errors[] = 'Cannot create installments when remaining amount is zero or negative';
        }

        return $errors;
    }

    // Helper method to format currency
    public function formatCurrency($amount): string
    {
        return '₹' . number_format($amount, 2);
    }

    // Additional Fields
    public string $enrollment_date = '';
    public string $incharge_name = '';

    // Relationships
    public int $center_id = 0;
    public $course_id = 0;

    // File uploads
    public $student_signature_image;
    public $student_image;

    public $dateConfig = ['altFormat' => 'd/m/Y'];

    // Validation rules
    protected function rules(): array
    {
        return [
            'first_name' => 'required|string|max:100',
            'fathers_name' => 'required|string|max:100',
            'surname' => 'nullable|string|max:100',
            'address' => 'nullable|array',
            'address.street' => 'required|string|max:190',
            'address.city' => 'required|string|max:100',
            'address.state' => 'required|string|max:100',
            'address.pincode' => 'required|string|max:10',
            'address.country' => 'required|string|max:100',
            'telephone_no' => 'nullable|string|max:20',
            'email' => 'required|email|max:180',
            'mobile' => 'nullable|string|max:15',
            'date_of_birth' => 'required|date',
            'age' => 'required|integer|min:0|max:150',
            'qualification' => 'required|string|max:500',
            'additional_qualification' => 'nullable|string|max:500',
            'reference' => 'nullable|string|max:100',
            'batch_time' => 'required|string|max:100',
            'scheme_given' => 'nullable|string|max:500',
            'course_fees' => 'required|numeric|min:0',
            'down_payment' => 'nullable|numeric|min:0|lte:course_fees',
            'no_of_installments' => 'nullable|integer|min:0',
            'installment_date' => 'nullable|date',
            'enrollment_date' => 'nullable|date',
            'incharge_name' => 'nullable|string|max:100',
            'center_id' => 'required|exists:centers,id',
            'course_id' => 'required|exists:courses,id',
            'student_signature_image' => 'nullable|image|max:2048',
            'student_image' => 'nullable|image|max:2048',
        ];
    }

    // Validation messages
    protected function messages(): array
    {
        return [
            'first_name.required' => 'First name is required.',
            'fathers_name.required' => 'Father\'s name is required.',
            'email.required' => 'Email is required.',
            'email.email' => 'Please enter a valid email address.',
            'course_fees.required' => 'Course fees is required.',
            'down_payment.lte' => 'Down payment cannot exceed course fees.',
            'center_id.required' => 'Please select a center.',
            'course_id.required' => 'Please select a course.',
            'address.street.required' => 'Street field is required',
            'address.city.required' => 'City field is required',
            'address.state.required' => 'State field is required',
            'address.pincode.required' => 'Pincode field is required',
            'address.country.required' => 'Contry field is required',
            'address.street.required' => 'Street field is required',
            'address.city.required' => 'City field is required',
            'address.state.required' => 'State field is required',
            'address.pincode.required' => 'Pincode field is required',
            'address.country.required' => 'Contry field is required',
            'address.street.max' => 'Street may not be greater than 190 characters.',
            'address.city.max' => 'City may not be greater than 100 characters.',
            'address.state.max' => 'State may not be greater than 100 characters.',
            'address.pincode.max' => 'Pincode may not be greater than 10 characters.',
            'address.country.max' => 'Country may not be greater than 100 characters.',
        ];
    }

    // Save student
    public function save(): void
    {
        // Additional validation for fees
        if ($this->course_fees > 0) {
            if ($this->down_payment > $this->course_fees) {
                $this->error('Down payment cannot exceed course fees.', position: 'toast-bottom');
                return;
            }

            if ($this->no_of_installments > 0 && $this->remaining_amount <= 0) {
                $this->error('Cannot create installments when remaining amount is zero or negative.', position: 'toast-bottom');
                return;
            }
        }

        $this->validate();

        $data = [
            'first_name' => $this->first_name,
            'fathers_name' => $this->fathers_name,
            'surname' => $this->surname,
            'address' => $this->address,
            'telephone_no' => $this->telephone_no,
            'email' => $this->email,
            'mobile' => $this->mobile,
            'date_of_birth' => $this->date_of_birth ?: null,
            'age' => $this->age ?: null,
            'qualification' => $this->qualification,
            'additional_qualification' => $this->additional_qualification,
            'reference' => $this->reference,
            'batch_time' => $this->batch_time,
            'scheme_given' => $this->scheme_given,
            'course_fees' => $this->course_fees,
            'down_payment' => $this->down_payment ?: null,
            'no_of_installments' => $this->no_of_installments ?: null,
            'installment_date' => $this->installment_date ?: null,
            'enrollment_date' => $this->enrollment_date ?: null,
            'incharge_name' => $this->incharge_name,
            'center_id' => $this->center_id,
            'course_id' => $this->course_id,
        ];

        if ($this->student_signature_image) {
            $data['student_signature_image'] = $this->student_signature_image->store('students/signatures', 'public');
        }
        if ($this->student_image) {
            $data['student_image'] = $this->student_image->store('students/images', 'public');
        }

        $student = Student::create($data);

        // Generate QR code for the student
        $studentQRService = new StudentQRService();
        $studentQRService->generateStudentQR($student);

        // Create installments if specified
        if ($this->no_of_installments > 0 && $this->remaining_amount > 0 && $this->installment_date) {
            $this->createInstallments($student);
        }

        if ($this->send_notification) {
            $this->sendNotification($student);
        }

        $this->success('Student admitted successfully!', position: 'toast-bottom');
        $this->redirect(route('admin.student.index'));

        // try {

        // } catch (\Exception $e) {
        //     $this->error('Failed to admit student. Please try again.', position: 'toast-bottom');
        // }
    }

    // Reset form
    public function resetForm(): void
    {
        $this->reset(['first_name', 'fathers_name', 'surname', 'address', 'telephone_no', 'email', 'mobile', 'date_of_birth', 'age', 'qualification', 'additional_qualification', 'reference', 'batch_time', 'scheme_given', 'course_fees', 'down_payment', 'no_of_installments', 'installment_date', 'enrollment_date', 'incharge_name', 'center_id', 'course_id', 'student_signature_image', 'student_image']);
        $this->resetValidation();
        $this->address = [
            'street' => '',
            'city' => '',
            'state' => '',
            'pincode' => '',
            'country' => '',
        ];
        $this->remaining_amount = 0;
        $this->total_payable = 0;
        $this->installment_breakdown = [];
        $this->success('Form reset successfully!', position: 'toast-bottom');
    }

    // Remove uploaded file
    public function removeFile($property): void
    {
        $this->$property = null;
        $this->success('File removed successfully!', position: 'toast-bottom');
    }

    // Calculate age from date of birth
    public function updatedDateOfBirth(): void
    {
        if ($this->date_of_birth) {
            $dob = \Carbon\Carbon::parse($this->date_of_birth);
            $this->age = $dob->age;
        }
    }

    public function calculateInstallments(): void
    {
        $this->remaining_amount = 0;
        $this->total_payable = $this->course_fees;
        $this->installment_breakdown = [];

        if ($this->course_fees > 0) {
            if ($this->down_payment > $this->course_fees) {
                $this->down_payment = $this->course_fees;
            }

            // Calculate remaining amount after down payment
            $this->remaining_amount = $this->course_fees - ($this->down_payment ?? 0);

            // Generate installment breakdown if number of installments is specified
            if ($this->no_of_installments > 0 && $this->remaining_amount > 0) {
                $installmentAmount = round($this->remaining_amount / $this->no_of_installments, 2);

                // Generate installment breakdown
                $this->installment_breakdown = [];
                $remainingForLastInstallment = $this->remaining_amount;

                for ($i = 1; $i <= $this->no_of_installments; $i++) {
                    if ($i == $this->no_of_installments) {
                        // Last installment gets the remaining amount to avoid rounding errors
                        $amount = round($remainingForLastInstallment, 2);
                    } else {
                        $amount = $installmentAmount;
                        $remainingForLastInstallment -= $amount;
                    }

                    $this->installment_breakdown[] = [
                        'installment_no' => $i,
                        'amount' => $amount,
                        'due_date' => $this->installment_date
                            ? \Carbon\Carbon::parse($this->installment_date)
                                ->addMonths($i - 1)
                                ->format('d/m/Y')
                            : 'TBD',
                    ];
                }
            }
        }
    }

    // Create installments in database
    public function createInstallments($student): void
    {
        if (empty($this->installment_breakdown)) {
            return;
        }

        foreach ($this->installment_breakdown as $installment) {
            $dueDate = \Carbon\Carbon::parse($this->installment_date)->addMonths($installment['installment_no'] - 1);

            \App\Models\Installment::create([
                'student_id' => $student->id,
                'installment_no' => $installment['installment_no'],
                'amount' => $installment['amount'],
                'due_date' => $dueDate,
                'status' => 'pending',
            ]);
        }
    }

    // Update installments in database with custom amounts
    public function updateInstallmentsWithCustomAmounts($student): void
    {
        if (empty($this->installment_breakdown)) {
            return;
        }

        // Delete existing pending installments
        $student
            ->installments()
            ->whereIn('status', ['pending', 'overdue'])
            ->delete();

        // Create new installments with custom amounts
        foreach ($this->installment_breakdown as $installment) {
            $dueDate = \Carbon\Carbon::parse($this->installment_date)->addMonths($installment['installment_no'] - 1);

            \App\Models\Installment::create([
                'student_id' => $student->id,
                'installment_no' => $installment['installment_no'],
                'amount' => $installment['amount'],
                'due_date' => $dueDate,
                'status' => 'pending',
            ]);
        }
    }

    // Send registration success notification
    private function sendNotification($student): void
    {
        try {
            // Get course and center details
            $course = \App\Models\Course::find($this->course_id);
            $center = \App\Models\Center::find($this->center_id);

            // Calculate monthly installment amount if applicable
            $monthlyInstallment = 0;
            if ($this->no_of_installments > 0 && $this->remaining_amount > 0) {
                $monthlyInstallment = round($this->remaining_amount / $this->no_of_installments, 2);
            }

            // Get student QR code or generate if it doesn't exist
            $studentQR = $student->qrCode;
            if (!$studentQR) {
                $qrService = new StudentQRService();
                $studentQR = $qrService->generateStudentQR($student);
            }
            $qrCodeUrl = $studentQR ? route('student.qr.verify', $studentQR->qr_token) : null;
            $qrCodePath = $studentQR ? $studentQR->qr_code_path : null;

            // Prepare data for email
            $data = [
                'studentName' => $student->first_name . ' ' . $student->surname,
                'tiitvtRegNo' => $student->tiitvt_reg_no,
                'courseName' => $course ? $course->name : 'N/A',
                'centerName' => $center ? $center->name : 'N/A',
                'enrollmentDate' => $this->enrollment_date ?: now()->format('d/m/Y'),
                'courseFees' => $this->course_fees,
                'downPayment' => $this->down_payment ?: 0,
                'noOfInstallments' => $this->no_of_installments ?: 0,
                'monthlyInstallment' => $monthlyInstallment,
                'qrCodeUrl' => $qrCodeUrl,
                'qrCodePath' => $qrCodePath,
            ];

            // Send email using EmailNotificationHelper
            $result = \App\Helpers\EmailNotificationHelper::sendNotificationByType('registration_success', $student->email, $data, ['queue' => true]);

            if ($result) {
                $this->success('Registration success email sent to student!', position: 'toast-bottom');
            } else {
                $this->warning('Failed to send registration email. Please check logs.', position: 'toast-bottom');
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to send registration notification: ' . $e->getMessage(), [
                'student_id' => $student->id,
                'email' => $student->email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->warning('Failed to send registration email. Please check logs.', position: 'toast-bottom');
        }
    }

    public function updatedCourseFees(): void
    {
        // Ensure course_fees is a valid number, default to 0 if empty or invalid
        if (empty($this->course_fees) || !is_numeric($this->course_fees)) {
            $this->course_fees = 0;
        } else {
            $this->course_fees = (float) $this->course_fees;
        }

        // Clear related fields if course fees is 0 or empty
        if ($this->course_fees <= 0) {
            $this->down_payment = 0;
            $this->no_of_installments = 0;
            $this->installment_breakdown = [];
            $this->remaining_amount = 0;
            $this->total_payable = 0;
        } else {
            // Recalculate installments if course fees is valid
            $this->calculateInstallments();
        }
    }

    public function updatedDownPayment(): void
    {
        // Ensure down_payment is a valid number, default to 0 if empty or invalid
        if (empty($this->down_payment) || !is_numeric($this->down_payment)) {
            $this->down_payment = 0;
        } else {
            $this->down_payment = (float) $this->down_payment;
        }

        // Ensure down payment doesn't exceed course fees
        if ($this->down_payment > $this->course_fees) {
            $this->down_payment = $this->course_fees;
        }

        // Clear installments if down payment equals course fees
        if ($this->down_payment == $this->course_fees) {
            $this->no_of_installments = 0;
            $this->installment_breakdown = [];
            $this->remaining_amount = 0;
        }

        $this->calculateInstallments();
    }

    public function updatedNoOfInstallments(): void
    {
        // Ensure no_of_installments is a valid number, default to 0 if empty or invalid
        if (empty($this->no_of_installments) || !is_numeric($this->no_of_installments)) {
            $this->no_of_installments = 0;
        } else {
            $this->no_of_installments = (int) $this->no_of_installments;
        }

        // Ensure number of installments is reasonable
        if ($this->no_of_installments > 24) {
            $this->no_of_installments = 24;
        }

        // Clear breakdown if no installments
        if ($this->no_of_installments <= 0) {
            $this->installment_breakdown = [];
            $this->remaining_amount = $this->course_fees - ($this->down_payment ?? 0);
        } else {
            // Calculate installments if number is valid
            $this->calculateInstallments();
        }
    }

    public function updatedInstallmentDate(): void
    {
        $this->calculateInstallments();
    }

    public function updatedCenterId(): void
    {
        if ($this->center_id) {
            $this->tiitvt_reg_no = Student::generateUniqueTiitvtRegNo($this->center_id);
        }
    }

    public function updatedCourseId(): void
    {
        if ($this->course_id) {
            $course = Course::find($this->course_id);
            if ($course && $course->price) {
                $this->course_fees = $course->price;
                $this->calculateInstallments();
            }
        } else {
            $this->course_fees = 0;
            $this->calculateInstallments();
        }
    }

    // Toggle installment amount editing mode
    public function toggleInstallmentEditing(): void
    {
        $this->edit_installment_amounts = !$this->edit_installment_amounts;

        if ($this->edit_installment_amounts) {
            // Initialize editable amounts with current breakdown
            $this->editable_installment_amounts = [];
            foreach ($this->installment_breakdown as $installment) {
                $this->editable_installment_amounts[$installment['installment_no']] = $installment['amount'];
            }
        }
    }

    // Update individual installment amount
    public function updatedEditableInstallmentAmounts($value, $key): void
    {
        // Validate the amount
        $amount = (float) $value;
        if ($amount < 0) {
            $this->editable_installment_amounts[$key] = 0;
            $this->error('Installment amount cannot be negative.', position: 'toast-bottom');
            return;
        }

        $this->editable_installment_amounts[$key] = $amount;
        $this->validateInstallmentAmounts();
    }

    // Validate that total installment amounts equal remaining amount
    public function validateInstallmentAmounts(): bool
    {
        if (empty($this->editable_installment_amounts)) {
            return true;
        }

        $totalAmount = array_sum($this->editable_installment_amounts);
        $difference = abs($totalAmount - $this->remaining_amount);

        // Allow small rounding differences (up to 0.01)
        if ($difference > 0.01) {
            $this->error('Total installment amounts must equal the remaining amount (' . $this->formatCurrency($this->remaining_amount) . '). Current total: ' . $this->formatCurrency($totalAmount), position: 'toast-bottom');
            return false;
        }

        return true;
    }

    // Apply custom installment amounts
    public function applyCustomInstallmentAmounts(): void
    {
        if (!$this->validateInstallmentAmounts()) {
            return;
        }

        // Update the installment breakdown with custom amounts
        foreach ($this->installment_breakdown as $index => $installment) {
            $installmentNo = $installment['installment_no'];
            if (isset($this->editable_installment_amounts[$installmentNo])) {
                $this->installment_breakdown[$index]['amount'] = $this->editable_installment_amounts[$installmentNo];
            }
        }

        $this->edit_installment_amounts = false;
        $this->success('Custom installment amounts applied successfully!', position: 'toast-bottom');
    }

    // Reset to equal distribution
    public function resetToEqualDistribution(): void
    {
        $this->calculateInstallments();
        $this->edit_installment_amounts = false;
        $this->editable_installment_amounts = [];
        $this->success('Installment amounts reset to equal distribution!', position: 'toast-bottom');
    }

    public function rendering(View $view)
    {
        // Auto-generate TIITVT registration number if empty
        if (empty($this->tiitvt_reg_no)) {
            $this->tiitvt_reg_no = Student::generateUniqueTiitvtRegNo($this->center_id);
        }

        // Set enrollment date to today if empty
        if (empty($this->enrollment_date)) {
            $this->enrollment_date = now()->format('Y-m-d');
        }

        $view->centers = Center::active()
            ->latest()
            ->get(['id', 'name']);

        $view->courses = Course::active()
            ->latest()
            ->get(['id', 'name', 'price']);
    }
}; ?>
@section('cdn')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/gh/robsontenorio/mary@0.44.2/libs/currency/currency.js">
    </script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css" />
@endsection
<div>
    <!-- Header -->
    <div class="flex justify-between items-start lg:items-center flex-col lg:flex-row mt-3 mb-5 gap-2">
        <div>
            <h1 class="text-2xl font-bold">
                Student Admission
            </h1>
            <div class="breadcrumbs text-sm">
                <ul class="flex">
                    <li>
                        <a href="{{ route('admin.index') }}" wire:navigate>
                            Dashboard
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('admin.student.index') }}" wire:navigate>
                            Students
                        </a>
                    </li>
                    <li>
                        Student Admission
                    </li>
                </ul>
            </div>
        </div>

        <div class="flex gap-3">
            <x-button label="Reset Form" icon="o-arrow-path" class="btn-outline" wire:click="resetForm" responsive />
            <x-button label="Back to Students" icon="o-arrow-left" class="btn-primary btn-outline"
                link="{{ route('admin.student.index') }}" responsive />
        </div>
    </div>
    <hr class="mb-5">
    <x-card shadow>
        <form wire:submit="save" class="space-y-6">
            <!-- Basic Information -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div class="md:col-span-2">
                    <h3 class="text-lg font-semibold text-primary">Basic Information</h3>
                </div>

                <x-input label="TIITVT Registration No" wire:model="tiitvt_reg_no" placeholder="Auto-generated"
                    icon="o-identification" readonly />

                <x-input label="First Name" wire:model="first_name" placeholder="Enter first name" icon="o-user" />

                <x-input label="Father's Name" wire:model="fathers_name" placeholder="Enter father's name"
                    icon="o-user" />

                <x-input label="Surname" wire:model="surname" placeholder="Enter surname (optional)" icon="o-user" />
            </div>

            <!-- Course and Batch Information -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div class="md:col-span-2">
                    <h3 class="text-lg font-semibold text-primary">Course and Batch Information</h3>
                </div>

                @role(RolesEnum::Admin->value)
                    <x-choices-offline label="Center" wire:model.live="center_id" placeholder="Select center"
                        icon="o-building-office" :options="$centers" single searchable clearable />
                @endrole

                <x-choices-offline label="Course" wire:model.live="course_id" placeholder="Select course"
                    icon="o-academic-cap" :options="$courses" single searchable clearable
                    hint="Course price will be automatically loaded when a course is selected" />

                <x-input label="Batch Time" wire:model="batch_time" placeholder="Enter batch time (optional)" />

                <x-textarea label="Scheme Given" wire:model="scheme_given" placeholder="Enter scheme details (optional)"
                    icon="o-document-text" rows="3" />
            </div>

            <!-- Contact Information -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div class="md:col-span-2">
                    <h3 class="text-lg font-semibold text-primary">Contact Information</h3>
                </div>

                <x-input label="Email" wire:model="email" placeholder="Enter email address" icon="o-envelope"
                    type="email" />

                <x-input label="Mobile Number" wire:model="mobile" placeholder="Enter mobile number" icon="o-phone" />

                <x-input label="Telephone Number" wire:model="telephone_no"
                    placeholder="Enter telephone number (optional)" icon="fas.tty" />

                <x-input label="Street Address" wire:model="address.street" placeholder="Enter street address"
                    icon="o-map-pin" />

                <x-input label="City" wire:model="address.city" placeholder="Enter city" icon="o-building-office" />

                <x-input label="State" wire:model="address.state" placeholder="Enter state" icon="o-map" />

                <x-input label="Pincode" wire:model="address.pincode" placeholder="Enter pincode" icon="o-map-pin" />

                <x-input label="Country" wire:model="address.country" placeholder="Enter country" icon="o-flag" />
            </div>

            <!-- Personal Information -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div class="md:col-span-2">
                    <h3 class="text-lg font-semibold text-primary">Personal Information</h3>
                </div>

                <x-datepicker label="Date of Birth" wire:model.live="date_of_birth" icon="o-calendar"
                    :config="$dateConfig" />

                <x-input label="Age" wire:model="age" type="number" placeholder="Auto-calculated"
                    icon="o-user" readonly />
            </div>

            <!-- Academic Information -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div class="md:col-span-2">
                    <h3 class="text-lg font-semibold text-primary">Academic Information</h3>
                </div>

                <x-textarea label="Qualification" wire:model="qualification"
                    placeholder="Enter educational qualification" icon="o-academic-cap" rows="3" />

                <x-textarea label="Additional Qualification" wire:model="additional_qualification"
                    placeholder="Enter additional qualifications (optional)" icon="o-academic-cap" rows="3" />

                <x-input label="Reference" wire:model="reference" placeholder="Enter reference (optional)"
                    icon="o-user-group" />
            </div>

            <!-- Fees Information -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div class="md:col-span-2">
                    <h3 class="text-lg font-semibold text-primary">Fees Information</h3>
                    <x-alert title="How it works:" icon="o-exclamation-triangle" class="alert-info mt-3 text-white"
                        description="Enter the course fees, optional down payment, and number of installments. The system will automatically calculate the remaining amount and divide it equally among installments. The last installment may vary slightly to account for rounding."
                        dismissible />

                </div>

                <x-input label="Course Fees" wire:model.live="course_fees" placeholder="Enter course fees"
                    icon="o-currency-rupee" />

                <x-input label="Down Payment" wire:model.live="down_payment"
                    placeholder="Enter down payment (optional)" icon="o-currency-rupee"
                    hint="Cannot exceed course fees" />

                <x-input label="Number of Installments" wire:model.live="no_of_installments" type="number"
                    placeholder="Enter number of installments (optional)" icon="o-calculator" min="0"
                    hint="Leave empty if no installments" />

                <x-datepicker label="Installment Date (optional)" wire:model.live="installment_date"
                    icon="o-calendar" :config="$dateConfig" />

                <x-datepicker label="Enrollment Date" wire:model="enrollment_date" icon="o-calendar"
                    :config="$dateConfig" />

                <x-input label="Incharge Name" wire:model="incharge_name"
                    placeholder="Enter incharge name (optional)" icon="o-user" />
            </div>

            <!-- Fees Summary -->
            @if ($course_fees > 0)
                <div class="grid grid-cols-1 gap-6">
                    <div>
                        <h3 class="text-lg font-semibold text-primary mb-4">Fees Summary</h3>

                        <div class="bg-base-200 rounded-lg p-4 space-y-3">
                            <div class="flex justify-between items-center">
                                <span class="font-medium">Total Course Fees:</span>
                                <span class="font-bold text-lg">{{ $this->formatCurrency($total_payable) }}</span>
                            </div>

                            @if ($down_payment > 0)
                                <div class="flex justify-between items-center">
                                    <span class="font-medium">Down Payment:</span>
                                    <span
                                        class="font-bold text-success">-{{ $this->formatCurrency($down_payment) }}</span>
                                </div>
                            @endif

                            <div class="flex justify-between items-center border-t pt-3">
                                <span class="font-medium">Remaining Amount:</span>
                                <span
                                    class="font-bold text-lg text-primary">{{ $this->formatCurrency($remaining_amount) }}</span>
                            </div>

                            @if ($no_of_installments > 0 && count($installment_breakdown) > 0)
                                <div class="mt-4">
                                    <div class="flex justify-between items-center mb-3">
                                        <h4 class="font-semibold text-base">Installment Breakdown
                                            ({{ $no_of_installments }} installments)</h4>
                                        <div class="flex gap-2">
                                            @if (!$edit_installment_amounts)
                                                <x-button label="Edit Amounts" icon="o-pencil"
                                                    class="btn-sm btn-outline"
                                                    wire:click="toggleInstallmentEditing" />
                                            @else
                                                <x-button label="Apply" icon="o-check" class="btn-sm btn-primary"
                                                    wire:click="applyCustomInstallmentAmounts" />
                                                <x-button label="Reset" icon="o-arrow-path"
                                                    class="btn-sm btn-outline"
                                                    wire:click="resetToEqualDistribution" />
                                                <x-button label="Cancel" icon="o-x-mark" class="btn-sm btn-ghost"
                                                    wire:click="toggleInstallmentEditing" />
                                            @endif
                                        </div>
                                    </div>

                                    <!-- Display calculated installment amount -->
                                    <div class="bg-base-100 rounded-lg p-3 border mb-3">
                                        <div class="flex justify-between items-center">
                                            <span class="font-medium">Monthly Installment Amount:</span>
                                            <span
                                                class="font-bold text-lg text-primary">{{ $this->formatCurrency($installment_breakdown[0]['amount'] ?? 0) }}</span>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                                        @foreach ($installment_breakdown as $installment)
                                            <div class="bg-base-100 rounded-lg p-3 border">
                                                <div class="text-sm text-gray-600">Installment
                                                    {{ $installment['installment_no'] }}</div>

                                                @if ($edit_installment_amounts)
                                                    <x-input
                                                        wire:model="editable_installment_amounts.{{ $installment['installment_no'] }}"
                                                        type="number" step="0.01" min="0"
                                                        placeholder="Enter amount" />
                                                @else
                                                    <div class="font-bold text-lg">
                                                        {{ $this->formatCurrency($installment['amount']) }}</div>
                                                @endif

                                                <div class="text-xs text-gray-500 mt-1">Due:
                                                    {{ $installment['due_date'] }}</div>
                                            </div>
                                        @endforeach
                                    </div>

                                    @if ($edit_installment_amounts)
                                        <div class="mt-3 p-3 bg-warning/10 border border-warning/20 rounded-lg">
                                            <div class="flex items-center gap-2 text-warning">
                                                <x-icon name="o-exclamation-triangle" class="w-4 h-4" />
                                                <span class="font-medium">Custom Amounts Mode</span>
                                            </div>
                                            <p class="text-sm text-warning mt-1">
                                                Total must equal remaining amount:
                                                {{ $this->formatCurrency($remaining_amount) }}
                                            </p>
                                        </div>
                                    @else
                                        <div class="mt-3 text-xs bg-base-100 p-2 rounded-md">
                                            <x-alert title="Note:" icon="o-exclamation-triangle"
                                                description="Installment amounts are calculated equally. Click 'Edit Amounts' to customize individual installment amounts. The last installment may vary slightly to account for rounding." />
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            <!-- Student Signature & Image -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h3 class="text-lg font-semibold text-primary">Student Image</h3>
                    <div class="space-y-2 mt-3">
                        <x-file wire:model="student_image" accept="image/*" placeholder="Upload student image"
                            icon="o-photo" hint="Max 2MB" crop-after-change :crop-config="$config">
                            <img src="https://placehold.co/300x300?text=Image" alt="Student Image"
                                class="w-32 h-32 object-cover rounded-lg">
                        </x-file>
                    </div>
                </div>

                <div>
                    <h3 class="text-lg font-semibold text-primary">Student Signature</h3>
                    <div class="space-y-2 mt-3">
                        <x-file wire:model="student_signature_image" accept="image/*"
                            placeholder="Upload student signature" icon="o-photo" hint="Max 2MB" crop-after-change
                            :crop-config="$config">
                            <img src="https://placehold.co/300x300?text=Signature" alt="Signature"
                                class="w-32 h-32 object-cover rounded-lg">
                        </x-file>
                    </div>
                </div>

                <div>
                    <x-checkbox label="Send Notification" wire:model="send_notification"
                        hint="Clicking on this will send a notification to the student with course details." />
                </div>
            </div>

            <!-- Form Actions -->
            <div class="flex justify-end gap-3 pt-6 border-t">
                <x-button label="Cancel" icon="o-x-mark" class="btn-error btn-soft btn-sm"
                    link="{{ route('admin.student.index') }}" />
                <x-button label="Admit Student" icon="o-plus" class="btn-primary btn-sm btn-soft" type="submit"
                    spinner="save" />
            </div>
        </form>
    </x-card>
</div>
