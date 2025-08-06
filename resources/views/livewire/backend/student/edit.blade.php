<?php

use App\Models\Student;
use App\Models\Center;
use App\Models\Course;
use Mary\Traits\Toast;
use Illuminate\Support\Str;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\{Layout, Title};

new class extends Component {
    use WithFileUploads, Toast;

    // Form properties
    #[Title('Edit Student')]
    public Student $student;

    // Basic Information
    public string $tiitvt_reg_no = '';
    public string $first_name = '';
    public string $middle_name = '';
    public string $last_name = '';
    public string $fathers_name = '';
    public string $surname = '';

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
    public float $installment_amount = 0;

    // Additional Fields
    public string $enrollment_date = '';
    public string $incharge_name = '';

    // Relationships
    public int $center_id = 0;
    public int $course_id = 0;

    // File uploads
    public $student_signature_image;

    // Status
    public string $status = 'active';

    public function mount(Student $student)
    {
        $this->student = $student;
        $this->loadStudentData();
    }

    private function loadStudentData()
    {
        $this->tiitvt_reg_no = $this->student->tiitvt_reg_no;
        $this->first_name = $this->student->first_name;
        $this->middle_name = $this->student->middle_name ?? '';
        $this->last_name = $this->student->last_name ?? '';
        $this->fathers_name = $this->student->fathers_name;
        $this->surname = $this->student->surname ?? '';

        $this->address = $this->student->address ?? [
            'street' => '',
            'city' => '',
            'state' => '',
            'pincode' => '',
            'country' => '',
        ];

        $this->telephone_no = $this->student->telephone_no ?? '';
        $this->email = $this->student->email;
        $this->mobile = $this->student->mobile ?? '';

        $this->date_of_birth = $this->student->date_of_birth ? $this->student->date_of_birth->format('Y-m-d') : '';
        $this->age = $this->student->age ?? 0;

        $this->qualification = $this->student->qualification ?? '';
        $this->additional_qualification = $this->student->additional_qualification ?? '';
        $this->reference = $this->student->reference ?? '';

        $this->course_taken = $this->student->course_taken ?? '';
        $this->batch_time = $this->student->batch_time ?? '';
        $this->scheme_given = $this->student->scheme_given ?? '';

        $this->course_fees = $this->student->course_fees;
        $this->down_payment = $this->student->down_payment ?? 0;
        $this->no_of_installments = $this->student->no_of_installments ?? 0;
        $this->installment_date = $this->student->installment_date ? $this->student->installment_date->format('Y-m-d') : '';
        $this->installment_amount = $this->student->installment_amount ?? 0;

        $this->enrollment_date = $this->student->enrollment_date ? $this->student->enrollment_date->format('Y-m-d') : '';
        $this->incharge_name = $this->student->incharge_name ?? '';

        $this->center_id = $this->student->center_id;
        $this->course_id = $this->student->course_id;

        $this->status = $this->student->status;
    }

    // Validation rules
    protected function rules(): array
    {
        return [
            'tiitvt_reg_no' => 'required|string|max:50|unique:students,tiitvt_reg_no,' . $this->student->id,
            'first_name' => 'required|string|max:100',
            'middle_name' => 'nullable|string|max:100',
            'last_name' => 'nullable|string|max:100',
            'fathers_name' => 'required|string|max:100',
            'surname' => 'nullable|string|max:100',
            'address' => 'nullable|array',
            'address.street' => 'nullable|string|max:255',
            'address.city' => 'nullable|string|max:100',
            'address.state' => 'nullable|string|max:100',
            'address.pincode' => 'nullable|string|max:10',
            'address.country' => 'nullable|string|max:100',
            'telephone_no' => 'nullable|string|max:20',
            'email' => 'required|email|max:180|unique:students,email,' . $this->student->id,
            'mobile' => 'nullable|string|max:15',
            'date_of_birth' => 'nullable|date',
            'age' => 'nullable|integer|min:0|max:150',
            'qualification' => 'nullable|string|max:500',
            'additional_qualification' => 'nullable|string|max:500',
            'reference' => 'nullable|string|max:100',
            'course_taken' => 'nullable|string|max:100',
            'batch_time' => 'nullable|string|max:100',
            'scheme_given' => 'nullable|string|max:500',
            'course_fees' => 'required|numeric|min:0',
            'down_payment' => 'nullable|numeric|min:0',
            'no_of_installments' => 'nullable|integer|min:0',
            'installment_date' => 'nullable|date',
            'installment_amount' => 'nullable|numeric|min:0',
            'enrollment_date' => 'nullable|date',
            'incharge_name' => 'nullable|string|max:100',
            'center_id' => 'required|exists:centers,id',
            'course_id' => 'required|exists:courses,id',
            'student_signature_image' => 'nullable|image|max:2048',
            'status' => 'required|in:active,inactive',
        ];
    }

    // Validation messages
    protected function messages(): array
    {
        return [
            'tiitvt_reg_no.required' => 'TIITVT Registration Number is required.',
            'tiitvt_reg_no.unique' => 'This TIITVT Registration Number already exists.',
            'first_name.required' => 'First name is required.',
            'fathers_name.required' => 'Father\'s name is required.',
            'email.required' => 'Email is required.',
            'email.unique' => 'This email already exists.',
            'email.email' => 'Please enter a valid email address.',
            'course_fees.required' => 'Course fees is required.',
            'center_id.required' => 'Please select a center.',
            'course_id.required' => 'Please select a course.',
        ];
    }

    // Update student
    public function save(): void
    {
        $this->validate();

        try {
            $data = [
                'first_name' => $this->first_name,
                'middle_name' => $this->middle_name,
                'last_name' => $this->last_name,
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
                'course_taken' => $this->course_taken,
                'batch_time' => $this->batch_time,
                'scheme_given' => $this->scheme_given,
                'course_fees' => $this->course_fees,
                'down_payment' => $this->down_payment ?: null,
                'no_of_installments' => $this->no_of_installments ?: null,
                'installment_date' => $this->installment_date ?: null,
                'installment_amount' => $this->installment_amount ?: null,
                'enrollment_date' => $this->enrollment_date ?: null,
                'incharge_name' => $this->incharge_name,
                'center_id' => $this->center_id,
                'course_id' => $this->course_id,
                'status' => $this->status,
            ];

            if ($this->student_signature_image) {
                // Delete old image if exists
                if ($this->student->student_signature_image) {
                    Storage::disk('public')->delete($this->student->student_signature_image);
                }
                $data['student_signature_image'] = $this->student_signature_image->store('students/signatures', 'public');
            }

            $this->student->update($data);

            $this->success('Student updated successfully!', position: 'toast-bottom');
            $this->redirect(route('admin.student.show', $this->student->id));
        } catch (\Exception $e) {
            $this->error('Failed to update student. Please try again.', position: 'toast-bottom');
        }
    }

    // Calculate age from date of birth
    public function updatedDateOfBirth(): void
    {
        if ($this->date_of_birth) {
            $dob = \Carbon\Carbon::parse($this->date_of_birth);
            $this->age = $dob->age;
        }
    }

    // Calculate installment amount
    public function updatedCourseFees(): void
    {
        if ($this->course_fees && $this->down_payment) {
            $remaining = $this->course_fees - $this->down_payment;
            if ($this->no_of_installments > 0) {
                $this->installment_amount = $remaining / $this->no_of_installments;
            }
        }
    }

    public function updatedDownPayment(): void
    {
        $this->updatedCourseFees();
    }

    public function updatedNoOfInstallments(): void
    {
        $this->updatedCourseFees();
    }
}; ?>

<div>
    <!-- Header -->
    <div class="flex justify-between items-start lg:items-center flex-col lg:flex-row mt-3 mb-5 gap-2">
        <div>
            <h1 class="text-2xl font-bold">
                Edit Student
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
                        <a href="{{ route('admin.student.show', $student->id) }}" wire:navigate>
                            {{ $student->full_name }}
                        </a>
                    </li>
                    <li>
                        Edit
                    </li>
                </ul>
            </div>
        </div>
        <div class="flex gap-3">
            <x-button label="View Student" icon="o-eye" class="btn-primary btn-outline"
                link="{{ route('admin.student.show', $student->id) }}" responsive />
            <x-button label="Back to Students" icon="o-arrow-left" class="btn-primary btn-outline"
                link="{{ route('admin.student.index') }}" responsive />
        </div>
    </div>

    <hr class="mb-5">

    <!-- Form -->
    <x-card shadow>
        <form wire:submit="save" class="space-y-6">
            <!-- Basic Information -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="md:col-span-2">
                    <h3 class="text-lg font-semibold text-primary">Basic Information</h3>
                </div>

                <x-input label="TIITVT Registration No" wire:model="tiitvt_reg_no"
                    placeholder="Enter registration number" icon="o-identification" readonly />

                <x-input label="First Name" wire:model="first_name" placeholder="Enter first name" icon="o-user" />

                <x-input label="Middle Name" wire:model="middle_name" placeholder="Enter middle name (optional)"
                    icon="o-user" />

                <x-input label="Last Name" wire:model="last_name" placeholder="Enter last name (optional)"
                    icon="o-user" />

                <x-input label="Father's Name" wire:model="fathers_name" placeholder="Enter father's name"
                    icon="o-user" />

                <x-input label="Surname" wire:model="surname" placeholder="Enter surname (optional)" icon="o-user" />
            </div>

            <!-- Contact Information -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="md:col-span-2">
                    <h3 class="text-lg font-semibold text-primary">Contact Information</h3>
                </div>

                <x-input label="Email" wire:model="email" placeholder="Enter email address" icon="o-envelope"
                    type="email" />

                <x-input label="Mobile Number" wire:model="mobile" placeholder="Enter mobile number" icon="o-phone" />

                <x-input label="Telephone Number" wire:model="telephone_no"
                    placeholder="Enter telephone number (optional)" icon="o-phone" />

                <x-input label="Street Address" wire:model="address.street" placeholder="Enter street address"
                    icon="o-map-pin" />

                <x-input label="City" wire:model="address.city" placeholder="Enter city" icon="o-building-office" />

                <x-input label="State" wire:model="address.state" placeholder="Enter state" icon="o-map" />

                <x-input label="Pincode" wire:model="address.pincode" placeholder="Enter pincode" icon="o-map-pin" />

                <x-input label="Country" wire:model="address.country" placeholder="Enter country" icon="o-flag" />
            </div>

            <!-- Personal Information -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="md:col-span-2">
                    <h3 class="text-lg font-semibold text-primary">Personal Information</h3>
                </div>

                <x-input label="Date of Birth" wire:model="date_of_birth" type="date" icon="o-calendar" />

                <x-input label="Age" wire:model="age" type="number" placeholder="Auto-calculated" icon="o-user"
                    readonly />
            </div>

            <!-- Academic Information -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
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

            <!-- Course and Batch Information -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="md:col-span-2">
                    <h3 class="text-lg font-semibold text-primary">Course and Batch Information</h3>
                </div>

                <x-select label="Center" wire:model="center_id" placeholder="Select center"
                    icon="o-building-office">
                    @foreach (\App\Models\Center::active()->get() as $center)
                        <option value="{{ $center->id }}">{{ $center->name }}</option>
                    @endforeach
                </x-select>

                <x-select label="Course" wire:model="course_id" placeholder="Select course" icon="o-academic-cap">
                    @foreach (\App\Models\Course::active()->get() as $course)
                        <option value="{{ $course->id }}">{{ $course->name }} -
                            ₹{{ number_format($course->fee, 2) }}</option>
                    @endforeach
                </x-select>

                <x-input label="Course Taken" wire:model="course_taken" placeholder="Enter course taken (optional)"
                    icon="o-book-open" />

                <x-input label="Batch Time" wire:model="batch_time" placeholder="Enter batch time (optional)"
                    icon="o-clock" />

                <x-textarea label="Scheme Given" wire:model="scheme_given"
                    placeholder="Enter scheme details (optional)" icon="o-document-text" rows="3" />
            </div>

            <!-- Fees Information -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="md:col-span-2">
                    <h3 class="text-lg font-semibold text-primary">Fees Information</h3>
                </div>

                <x-input label="Course Fees" wire:model="course_fees" type="number" step="0.01"
                    placeholder="Enter course fees" icon="o-currency-rupee" />

                <x-input label="Down Payment" wire:model="down_payment" type="number" step="0.01"
                    placeholder="Enter down payment (optional)" icon="o-currency-rupee" />

                <x-input label="Number of Installments" wire:model="no_of_installments" type="number"
                    placeholder="Enter number of installments (optional)" icon="o-calculator" />

                <x-input label="Installment Date" wire:model="installment_date" type="date"
                    placeholder="Select installment date (optional)" icon="o-calendar" />

                <x-input label="Installment Amount" wire:model="installment_amount" type="number" step="0.01"
                    placeholder="Auto-calculated" icon="o-currency-rupee" readonly />

                <x-input label="Enrollment Date" wire:model="enrollment_date" type="date" icon="o-calendar" />

                <x-input label="Incharge Name" wire:model="incharge_name"
                    placeholder="Enter incharge name (optional)" icon="o-user" />
            </div>

            <!-- Student Signature -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="md:col-span-2">
                    <h3 class="text-lg font-semibold text-primary">Student Signature</h3>
                </div>

                <div class="space-y-2">
                    <label class="label">
                        <span class="label-text font-medium">Student Signature Image (Optional)</span>
                    </label>

                    @if ($student->student_signature_image)
                        <div class="mb-4">
                            <label class="text-sm font-medium text-gray-600 mb-2 block">Current Signature</label>
                            <img src="{{ asset('storage/' . $student->student_signature_image) }}"
                                alt="Current Signature" class="max-w-xs h-auto border rounded-lg">
                        </div>
                    @endif

                    <x-file wire:model="student_signature_image" accept="image/*"
                        placeholder="Upload new signature (optional)" icon="o-photo">
                        <div
                            class="w-32 h-32 border-2 border-dashed border-gray-300 rounded-lg flex items-center justify-center">
                            <span class="text-gray-500 text-sm">New Signature</span>
                        </div>
                    </x-file>
                </div>
            </div>

            <!-- Status -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="md:col-span-2">
                    <h3 class="text-lg font-semibold text-primary">Status</h3>
                </div>

                <x-select label="Status" wire:model="status" icon="o-check-circle">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </x-select>
            </div>

            <!-- Form Actions -->
            <div class="flex justify-end gap-3 pt-6 border-t">
                <x-button label="Cancel" icon="o-x-mark" class="btn-error btn-soft btn-sm"
                    link="{{ route('admin.student.show', $student->id) }}" />
                <x-button label="Update Student" icon="o-check" class="btn-primary btn-sm btn-soft" type="submit"
                    spinner="save" />
            </div>
        </form>
    </x-card>
</div>
