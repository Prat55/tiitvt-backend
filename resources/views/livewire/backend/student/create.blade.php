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
use App\Enums\RolesEnum;

new class extends Component {
    use WithFileUploads, Toast;

    #[Title('Student Admission')]
    public function mount(): void
    {
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

    // Calculated fields for display
    public float $remaining_amount = 0;
    public float $total_payable = 0;

    // Notification
    public bool $send_notification = false;

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
    public array $course_ids = [];

    // File uploads
    public $student_signature_image;
    public $student_image;

    public $dateConfig = ['altFormat' => 'd/m/Y'];

    // Validation rules
    protected function rules(): array
    {
        return [
            'first_name' => 'required|string|max:100',
            'fathers_name' => 'nullable|string|max:100',
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
            'batch_time' => 'nullable|string|max:100',
            'scheme_given' => 'nullable|string|max:500',
            'course_fees' => 'required|numeric|min:0',
            'down_payment' => 'nullable|numeric|min:0|lte:course_fees',
            'enrollment_date' => 'nullable|date',
            'incharge_name' => 'nullable|string|max:100',
            'center_id' => 'required|exists:centers,id',
            'course_ids' => 'required|array|min:1',
            'course_ids.*' => 'exists:courses,id',
            'student_signature_image' => 'nullable|image|max:2048',
            'student_image' => 'nullable|image|max:2048',
        ];
    }

    // Validation messages
    protected function messages(): array
    {
        return [
            'first_name.required' => 'First name is required.',
            'email.required' => 'Email is required.',
            'email.email' => 'Please enter a valid email address.',
            'course_fees.required' => 'Course fees is required.',
            'down_payment.lte' => 'Down payment cannot exceed course fees.',
            'center_id.required' => 'Please select a center.',
            'course_ids.required' => 'Please select at least one course.',
            'course_ids.min' => 'Please select at least one course.',
            'address.street.required' => 'Street field is required',
            'address.city.required' => 'City field is required',
            'address.state.required' => 'State field is required',
            'address.pincode.required' => 'Pincode field is required',
            'address.country.required' => 'Country field is required',
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
            'course_fees' => $this->course_fees,
            'down_payment' => $this->down_payment ?: null,
            'enrollment_date' => $this->enrollment_date ?: null,
            'incharge_name' => $this->incharge_name,
            'center_id' => $this->center_id,
        ];

        if ($this->student_signature_image) {
            $data['student_signature_image'] = $this->student_signature_image->store('students/signatures', 'public');
        }
        if ($this->student_image) {
            $data['student_image'] = $this->student_image->store('students/images', 'public');
        }

        $student = Student::create($data);

        // Enroll student in selected courses
        if (!empty($this->course_ids)) {
            $courseEnrollments = [];
            foreach ($this->course_ids as $courseId) {
                $courseEnrollments[$courseId] = [
                    'enrollment_date' => $this->enrollment_date ?: now(),
                    'course_taken' => $this->course_taken,
                    'batch_time' => $this->batch_time,
                    'scheme_given' => $this->scheme_given,
                    'incharge_name' => $this->incharge_name,
                ];
            }
            $student->courses()->attach($courseEnrollments);
        }

        // Generate QR code for the student
        $studentQRService = app(StudentQRService::class);
        $studentQRService->generateStudentQR($student);

        if ($this->send_notification) {
            $this->sendNotification($student);
        }

        $this->success('Student admitted successfully!', position: 'toast-bottom');
        $this->redirect(route('admin.student.index'));
    }

    // Reset form
    public function resetForm(): void
    {
        $this->reset(['first_name', 'fathers_name', 'surname', 'address', 'telephone_no', 'email', 'mobile', 'date_of_birth', 'age', 'qualification', 'additional_qualification', 'reference', 'batch_time', 'scheme_given', 'course_fees', 'down_payment', 'enrollment_date', 'incharge_name', 'center_id', 'course_ids', 'student_signature_image', 'student_image']);
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

    // Recalculate fees summary
    public function calculateFeesSummary(): void
    {
        $this->total_payable = $this->course_fees;

        if ($this->down_payment > $this->course_fees) {
            $this->down_payment = $this->course_fees;
        }

        $this->remaining_amount = max(0, $this->course_fees - ($this->down_payment ?? 0));
    }

    // Send registration success notification
    private function sendNotification($student): void
    {
        try {
            $center = \App\Models\Center::find($this->center_id);

            // Get student QR code or generate if it doesn't exist
            $studentQR = $student->qrCode;
            if (!$studentQR) {
                $qrService = app(StudentQRService::class);
                $studentQR = $qrService->generateStudentQR($student);
            }

            // Generate QR code data URI for email
            $qrCodeDataUri = null;
            if ($studentQR) {
                $qrService = app(StudentQRService::class);
                $qrCodeDataUri = $qrService->generateQRCodeDataUri($studentQR->qr_data);
            }

            // Prepare data for email
            $data = [
                'studentName' => $student->first_name . ' ' . $student->surname,
                'tiitvtRegNo' => $student->tiitvt_reg_no,
                'courseName' => $student->courses->pluck('name')->implode(', ') ?: 'N/A',
                'centerName' => $center ? $center->name : 'N/A',
                'enrollmentDate' => $this->enrollment_date ?: now()->format('d/m/Y'),
                'courseFees' => $this->course_fees,
                'downPayment' => $this->down_payment ?: 0,
                'remainingBalance' => $this->remaining_amount,
                'qrCodeUrl' => $qrCodeDataUri,
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
        if (empty($this->course_fees) || !is_numeric($this->course_fees)) {
            $this->course_fees = 0;
        } else {
            $this->course_fees = (float) $this->course_fees;
        }

        if ($this->course_fees <= 0) {
            $this->down_payment = 0;
            $this->remaining_amount = 0;
            $this->total_payable = 0;
        } else {
            $this->calculateFeesSummary();
        }
    }

    public function updatedDownPayment(): void
    {
        if (empty($this->down_payment) || !is_numeric($this->down_payment)) {
            $this->down_payment = 0;
        } else {
            $this->down_payment = (float) $this->down_payment;
        }

        if ($this->down_payment > $this->course_fees) {
            $this->down_payment = $this->course_fees;
        }

        $this->calculateFeesSummary();
    }

    public function updatedCenterId(): void
    {
        if ($this->center_id) {
            $this->tiitvt_reg_no = Student::generateUniqueTiitvtRegNo($this->center_id);
        }
    }

    public function updatedCourseIds(): void
    {
        if (!empty($this->course_ids)) {
            $totalFees = 0;
            foreach ($this->course_ids as $courseId) {
                $course = Course::find($courseId);
                if ($course && $course->price) {
                    $totalFees += $course->price;
                }
            }

            if ($totalFees > 0) {
                $this->course_fees = $totalFees;
                $this->calculateFeesSummary();
            }
        } else {
            $this->course_fees = 0;
            $this->calculateFeesSummary();
        }
    }

    // Computed properties
    public function getSelectedCoursesProperty()
    {
        if (empty($this->course_ids)) {
            return collect();
        }

        return Course::whereIn('id', $this->course_ids)->get();
    }

    public function getTotalCourseFeesProperty()
    {
        return $this->getSelectedCoursesProperty()->sum('price');
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

                <x-input label="Father's Name" wire:model="fathers_name" placeholder="Enter father's name (optional)"
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

                <x-choices-offline label="Courses" wire:model.live="course_ids" placeholder="Select courses"
                    icon="o-academic-cap" :options="$courses" searchable clearable
                    hint="Select one or more courses for the student" />

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
                        description="Enter the course fees and optional down payment. Payments can be recorded manually from the student details page after admission."
                        dismissible />
                </div>

                <x-input label="Course Fees" wire:model.live="course_fees" placeholder="Enter course fees"
                    icon="o-currency-rupee"
                    hint="{{ $this->selectedCourses->count() > 0 ? 'Automatically calculated from selected courses' : 'Enter total course fees' }}" />

                <x-input label="Down Payment" wire:model.live="down_payment"
                    placeholder="Enter down payment (optional)" icon="o-currency-rupee"
                    hint="Cannot exceed course fees" />

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
                            @if ($this->selectedCourses->count() > 0)
                                <div class="mb-4">
                                    <h5 class="font-medium text-sm text-gray-600 mb-2">Course Breakdown:</h5>
                                    @foreach ($this->selectedCourses as $course)
                                        <div class="flex justify-between items-center text-sm py-1">
                                            <span>{{ $course->name }}</span>
                                            <span class="font-medium">₹{{ number_format($course->price, 2) }}</span>
                                        </div>
                                    @endforeach
                                    <div class="border-t border-gray-300 pt-2 mt-2">
                                        <div class="flex justify-between items-center">
                                            <span class="font-medium">Subtotal:</span>
                                            <span
                                                class="font-bold">{{ $this->formatCurrency($this->totalCourseFees) }}</span>
                                        </div>
                                    </div>
                                </div>
                            @endif

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
