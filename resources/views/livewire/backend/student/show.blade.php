<?php

use Livewire\Volt\Component;
use Livewire\Attributes\{Layout, Title};

new class extends Component {
    #[Title('Student Details')]
    public $student;

    public function mount($student)
    {
        $this->student = $student;
    }
}; ?>

<div>
    <!-- Header -->
    <div class="flex justify-between items-start lg:items-center flex-col lg:flex-row mt-3 mb-5 gap-2">
        <div>
            <h1 class="text-2xl font-bold">
                Student Details
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
                        {{ $student->full_name }}
                    </li>
                </ul>
            </div>
        </div>
        <div class="flex gap-3">
            <x-button label="Edit Student" icon="o-pencil" class="btn-primary btn-outline"
                link="{{ route('admin.student.edit', $student->id) }}" responsive />
            <x-button label="Back to Students" icon="o-arrow-left" class="btn-primary btn-outline"
                link="{{ route('admin.student.index') }}" responsive />
        </div>
    </div>

    <hr class="mb-5">

    <!-- Student Information -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Basic Information -->
        <x-card shadow class="lg:col-span-2">
            <div class="flex items-center gap-4 mb-6">
                <div class="avatar">
                    <div
                        class="w-16 h-16 rounded-full bg-primary text-primary-content flex items-center justify-center text-xl font-bold">
                        {{ substr($student->first_name, 0, 1) }}
                    </div>
                </div>
                <div>
                    <h2 class="text-2xl font-bold">{{ $student->full_name }}</h2>
                    <p class="text-gray-600">{{ $student->tiitvt_reg_no }}</p>
                    <span class="badge badge-sm {{ $student->status === 'active' ? 'badge-success' : 'badge-error' }}">
                        {{ ucfirst($student->status) }}
                    </span>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Basic Details -->
                <div class="space-y-4">
                    <h3 class="text-lg font-semibold text-primary">Basic Information</h3>

                    <div class="space-y-3">
                        <div>
                            <label class="text-sm font-medium text-gray-600">Father's Name</label>
                            <p class="text-sm">{{ $student->fathers_name }}</p>
                        </div>

                        @if ($student->surname)
                            <div>
                                <label class="text-sm font-medium text-gray-600">Surname</label>
                                <p class="text-sm">{{ $student->surname }}</p>
                            </div>
                        @endif

                        @if ($student->date_of_birth)
                            <div>
                                <label class="text-sm font-medium text-gray-600">Date of Birth</label>
                                <p class="text-sm">{{ $student->date_of_birth->format('M d, Y') }}</p>
                            </div>
                        @endif

                        @if ($student->age)
                            <div>
                                <label class="text-sm font-medium text-gray-600">Age</label>
                                <p class="text-sm">{{ $student->age }} years</p>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Contact Information -->
                <div class="space-y-4">
                    <h3 class="text-lg font-semibold text-primary">Contact Information</h3>

                    <div class="space-y-3">
                        <div>
                            <label class="text-sm font-medium text-gray-600">Email</label>
                            <p class="text-sm">{{ $student->email }}</p>
                        </div>

                        @if ($student->mobile)
                            <div>
                                <label class="text-sm font-medium text-gray-600">Mobile</label>
                                <p class="text-sm">{{ $student->mobile }}</p>
                            </div>
                        @endif

                        @if ($student->telephone_no)
                            <div>
                                <label class="text-sm font-medium text-gray-600">Telephone</label>
                                <p class="text-sm">{{ $student->telephone_no }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Address -->
            @if ($student->address && is_array($student->address))
                <div class="mt-6">
                    <h3 class="text-lg font-semibold text-primary mb-3">Address</h3>
                    <div class="bg-gray-50 p-4 rounded-lg">
                        @if ($student->address['street'])
                            <p class="text-sm">{{ $student->address['street'] }}</p>
                        @endif
                        @if ($student->address['city'])
                            <p class="text-sm">{{ $student->address['city'] }}</p>
                        @endif
                        @if ($student->address['state'])
                            <p class="text-sm">{{ $student->address['state'] }}</p>
                        @endif
                        @if ($student->address['pincode'])
                            <p class="text-sm">{{ $student->address['pincode'] }}</p>
                        @endif
                        @if ($student->address['country'])
                            <p class="text-sm">{{ $student->address['country'] }}</p>
                        @endif
                    </div>
                </div>
            @endif
        </x-card>

        <!-- Course & Fees Information -->
        <x-card shadow>
            <h3 class="text-lg font-semibold text-primary mb-4">Course & Fees</h3>

            <div class="space-y-4">
                @if ($student->center)
                    <div>
                        <label class="text-sm font-medium text-gray-600">Center</label>
                        <p class="text-sm font-medium">{{ $student->center->name }}</p>
                    </div>
                @endif

                @if ($student->course)
                    <div>
                        <label class="text-sm font-medium text-gray-600">Course</label>
                        <p class="text-sm font-medium">{{ $student->course->name }}</p>
                    </div>
                @endif

                <div>
                    <label class="text-sm font-medium text-gray-600">Course Fees</label>
                    <p class="text-lg font-bold text-primary">₹{{ number_format($student->course_fees, 2) }}</p>
                </div>

                @if ($student->down_payment)
                    <div>
                        <label class="text-sm font-medium text-gray-600">Down Payment</label>
                        <p class="text-sm">₹{{ number_format($student->down_payment, 2) }}</p>
                    </div>
                @endif

                @if ($student->no_of_installments)
                    <div>
                        <label class="text-sm font-medium text-gray-600">Installments</label>
                        <p class="text-sm">{{ $student->no_of_installments }} ×
                            ₹{{ number_format($student->installment_amount, 2) }}</p>
                    </div>
                @endif

                @if ($student->enrollment_date)
                    <div>
                        <label class="text-sm font-medium text-gray-600">Enrollment Date</label>
                        <p class="text-sm">{{ $student->enrollment_date->format('M d, Y') }}</p>
                    </div>
                @endif
            </div>
        </x-card>
    </div>

    <!-- Additional Information -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
        <!-- Academic Information -->
        <x-card shadow>
            <h3 class="text-lg font-semibold text-primary mb-4">Academic Information</h3>

            <div class="space-y-4">
                @if ($student->qualification)
                    <div>
                        <label class="text-sm font-medium text-gray-600">Qualification</label>
                        <p class="text-sm">{{ $student->qualification }}</p>
                    </div>
                @endif

                @if ($student->additional_qualification)
                    <div>
                        <label class="text-sm font-medium text-gray-600">Additional Qualification</label>
                        <p class="text-sm">{{ $student->additional_qualification }}</p>
                    </div>
                @endif

                @if ($student->reference)
                    <div>
                        <label class="text-sm font-medium text-gray-600">Reference</label>
                        <p class="text-sm">{{ $student->reference }}</p>
                    </div>
                @endif
            </div>
        </x-card>

        <!-- Course Details -->
        <x-card shadow>
            <h3 class="text-lg font-semibold text-primary mb-4">Course Details</h3>

            <div class="space-y-4">
                @if ($student->course_taken)
                    <div>
                        <label class="text-sm font-medium text-gray-600">Course Taken</label>
                        <p class="text-sm">{{ $student->course_taken }}</p>
                    </div>
                @endif

                @if ($student->batch_time)
                    <div>
                        <label class="text-sm font-medium text-gray-600">Batch Time</label>
                        <p class="text-sm">{{ $student->batch_time }}</p>
                    </div>
                @endif

                @if ($student->scheme_given)
                    <div>
                        <label class="text-sm font-medium text-gray-600">Scheme Given</label>
                        <p class="text-sm">{{ $student->scheme_given }}</p>
                    </div>
                @endif

                @if ($student->incharge_name)
                    <div>
                        <label class="text-sm font-medium text-gray-600">Incharge Name</label>
                        <p class="text-sm">{{ $student->incharge_name }}</p>
                    </div>
                @endif
            </div>
        </x-card>
    </div>

    <!-- Student Signature -->
    @if ($student->student_signature_image)
        <div class="mt-6">
            <x-card shadow>
                <h3 class="text-lg font-semibold text-primary mb-4">Student Signature</h3>
                <div class="flex justify-center">
                    <img src="{{ asset('storage/' . $student->student_signature_image) }}" alt="Student Signature"
                        class="max-w-md h-auto border rounded-lg">
                </div>
            </x-card>
        </div>
    @endif
</div>
