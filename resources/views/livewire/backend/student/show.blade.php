<?php

use Mary\Traits\Toast;
use Livewire\Volt\Component;
use App\Models\{Student, Installment};
use App\Enums\InstallmentStatusEnum;
use Livewire\Attributes\{Layout, Title};
use App\Services\StudentQRService;

new class extends Component {
    use Toast;

    #[Title('Student Details')]
    public Student $student;

    // Payment modal
    public bool $showAddPaymentModal = false;
    public ?float $paymentAmount = null;
    public ?string $paymentNotes = '';
    public ?string $paymentDate = '';

    // Mark as Paid modal
    public bool $showMarkPaidModal = false;
    public ?Installment $selectedPayment = null;
    public ?string $paidNotes = '';

    // Delete payment modal
    public bool $showDeletePaymentModal = false;
    public ?int $deletePaymentId = null;

    public $dateConfig = ['altFormat' => 'd/m/Y'];

    public function mount(Student $student)
    {
        $this->student = $student;
    }

    // --- Payment Summary ---
    public function getPaymentSummary()
    {
        $courseFees = $this->student->course_fees ?? 0;
        $downPayment = $this->student->down_payment ?? 0;
        $totalPaidFromPayments = $this->student->installments->where('status', InstallmentStatusEnum::Paid)->sum('paid_amount');
        $totalPaid = $downPayment + $totalPaidFromPayments;
        $remainingBalance = max(0, $courseFees - $totalPaid);
        $paymentProgress = $courseFees > 0 ? ($totalPaid / $courseFees) * 100 : 0;
        $paidCount = $this->student->installments->where('status', InstallmentStatusEnum::Paid)->count();
        $pendingCount = $this->student->installments->where('status', InstallmentStatusEnum::Pending)->count();

        return [
            'courseFees' => $courseFees,
            'downPayment' => $downPayment,
            'totalPaid' => $totalPaid,
            'remainingBalance' => $remainingBalance,
            'paymentProgress' => $paymentProgress,
            'paidCount' => $paidCount,
            'pendingCount' => $pendingCount,
        ];
    }

    // --- Payment Rows for x-table ---
    public function getPaymentRows()
    {
        $rows = collect();
        $rowNum = 1;

        // Down payment as first row
        $downPayment = $this->student->down_payment ?? 0;
        if ($downPayment > 0) {
            $rows->push(
                (object) [
                    'id' => 'dp-' . $this->student->id,
                    'row_num' => $rowNum++,
                    'amount' => $downPayment,
                    'status_label' => 'Down Payment',
                    'status_class' => 'badge-success',
                    'paid_date' => $this->student->enrollment_date ? $this->student->enrollment_date->format('M d, Y') : '-',
                    'notes' => 'Down payment at enrollment',
                    'is_down_payment' => true,
                    'is_pending' => false,
                    'is_paid' => true,
                    'payment_id' => null,
                    'student_id' => $this->student->id,
                ],
            );
        }

        // Regular payments
        foreach ($this->student->installments->sortByDesc('created_at') as $payment) {
            $rows->push(
                (object) [
                    'id' => $payment->id,
                    'row_num' => $rowNum++,
                    'amount' => $payment->amount,
                    'status_label' => $payment->status->label(),
                    'status_class' => $payment->status_badge_class,
                    'paid_date' => $payment->formatted_paid_date,
                    'notes' => $payment->notes ?? '-',
                    'is_down_payment' => false,
                    'is_pending' => $payment->status->isPending(),
                    'is_paid' => $payment->status->isPaid(),
                    'payment_id' => $payment->id,
                    'student_id' => $this->student->id,
                ],
            );
        }

        return $rows;
    }

    // --- Add Payment ---
    public function openAddPaymentModal()
    {
        $this->paymentAmount = null;
        $this->paymentNotes = '';
        $this->paymentDate = '';
        $this->showAddPaymentModal = true;
    }

    public function closeAddPaymentModal()
    {
        $this->showAddPaymentModal = false;
        $this->paymentAmount = null;
        $this->paymentNotes = '';
        $this->paymentDate = '';
    }

    public function addPayment()
    {
        // Calculate remaining balance
        $totalPaid = ($this->student->down_payment ?? 0) + $this->student->installments->where('status', InstallmentStatusEnum::Paid)->sum('paid_amount');
        $remainingBalance = max(0, $this->student->course_fees - $totalPaid);

        $this->validate(
            [
                'paymentAmount' => 'required|numeric|min:0.01|max:' . $remainingBalance,
                'paymentNotes' => 'nullable|string|max:500',
                'paymentDate' => 'nullable|date',
            ],
            [
                'paymentAmount.max' => 'Payment amount cannot exceed the remaining balance of ₹' . number_format($remainingBalance, 2),
            ],
        );

        try {
            $payment = $this->student->installments()->create([
                'amount' => $this->paymentAmount,
                'status' => InstallmentStatusEnum::Pending,
                'paid_date' => $this->paymentDate ?: now(),
                'notes' => $this->paymentNotes,
            ]);

            // markAsPaid handles status update and sends notification
            $payment->markAsPaid($this->paymentAmount, $this->paymentNotes);

            $this->student->refresh();
            $this->closeAddPaymentModal();
            $this->success('Payment recorded successfully!', position: 'toast-bottom');
        } catch (\Exception $e) {
            $this->error('Failed to record payment: ' . $e->getMessage(), position: 'toast-bottom');
        }
    }

    // --- Mark Pending as Paid ---
    public function openMarkPaidModal($paymentId)
    {
        $this->selectedPayment = Installment::findOrFail($paymentId);
        $this->paidNotes = $this->selectedPayment->notes ?? '';
        $this->showMarkPaidModal = true;
    }

    public function closeMarkPaidModal()
    {
        $this->showMarkPaidModal = false;
        $this->selectedPayment = null;
        $this->paidNotes = '';
    }

    public function markPaymentAsPaid()
    {
        if (!$this->selectedPayment) {
            return;
        }

        try {
            $this->selectedPayment->markAsPaid($this->selectedPayment->amount, $this->paidNotes);

            $this->student->refresh();
            $this->closeMarkPaidModal();
            $this->success('Payment marked as paid!', position: 'toast-bottom');
        } catch (\Exception $e) {
            $this->error('Failed to mark payment as paid: ' . $e->getMessage(), position: 'toast-bottom');
        }
    }

    // --- Delete Payment ---
    public function openDeletePaymentModal($paymentId)
    {
        $this->deletePaymentId = $paymentId;
        $this->showDeletePaymentModal = true;
    }

    public function closeDeletePaymentModal()
    {
        $this->deletePaymentId = null;
        $this->showDeletePaymentModal = false;
    }

    public function deletePayment()
    {
        $payment = $this->student->installments()->findOrFail($this->deletePaymentId);

        if ($payment->status->isPaid()) {
            $this->error('Cannot delete a paid payment record.', position: 'toast-bottom');
            return;
        }

        $payment->delete();
        $this->student->refresh();
        $this->success('Payment record deleted!', position: 'toast-bottom');
        $this->closeDeletePaymentModal();
    }

    // --- QR Code ---
    public function regenerateQRCode()
    {
        try {
            if (!$this->student->qrCode) {
                $this->error('No QR code found for this student.');
                return;
            }

            $qrService = app(StudentQRService::class);

            if ($this->student->qrCode->qr_code_path && \Storage::disk('public')->exists($this->student->qrCode->qr_code_path)) {
                \Storage::disk('public')->delete($this->student->qrCode->qr_code_path);
            }

            $newQrData = $qrService->generateQrDataWithToken($this->student, $this->student->qrCode->qr_token);
            $qrCodePath = $qrService->generateQrCodeWithLogo($newQrData, $this->student->qrCode->id);

            $this->student->qrCode->update([
                'qr_code_path' => $qrCodePath,
                'qr_data' => $newQrData,
            ]);

            $this->student->refresh();
            $this->success('QR Code regenerated successfully!', position: 'toast-bottom');
        } catch (\Exception $e) {
            $this->error('Failed to regenerate QR code: ' . $e->getMessage(), position: 'toast-bottom');
        }
    }

    public function downloadQRCode()
    {
        if (!$this->student->qrCode) {
            $this->error('No QR code found for this student.');
            return;
        }

        $qrCodePath = $this->student->qrCode->qr_code_path;

        if ($qrCodePath && \Storage::disk('public')->exists($qrCodePath)) {
            $safeRegNo = preg_replace('/[^a-zA-Z0-9_-]/', '_', $this->student->tiitvt_reg_no);
            $filename = 'student_' . $safeRegNo . '_qr_code.png';

            if (strlen($filename) > 255) {
                $filename = 'student_' . substr($safeRegNo, 0, 200) . '_qr_code.png';
            }

            return \Storage::disk('public')->download($qrCodePath, $filename);
        }

        $this->error('QR Code file not found. Please generate it first.');
    }

    public function getQRCodeDataUri()
    {
        if ($this->student->qrCode) {
            $qrService = app(StudentQRService::class);
            return $qrService->generateQRCodeDataUri($this->student->qrCode->qr_data);
        }
        return null;
    }

    // --- Resend Registration Email ---
    public function resedRegisterMail()
    {
        try {
            $student = $this->student;
            $course = $student->course;
            $center = $student->center;

            $studentQR = $student->qrCode;
            if (!$studentQR) {
                $qrService = app(StudentQRService::class);
                $studentQR = $qrService->generateStudentQR($student);
            }

            $qrCodeDataUri = null;
            if ($studentQR) {
                $qrService = app(StudentQRService::class);
                $qrCodeDataUri = $qrService->generateQRCodeDataUri($studentQR->qr_data);
            }

            $data = [
                'studentName' => $student->first_name . ' ' . $student->surname,
                'tiitvtRegNo' => $student->tiitvt_reg_no,
                'courseName' => $course ? $course->name : 'N/A',
                'centerName' => $center ? $center->name : 'N/A',
                'enrollmentDate' => $student->enrollment_date ?: now()->format('d/m/Y'),
                'courseFees' => $student->course_fees,
                'downPayment' => $student->down_payment ?: 0,
                'qrCodeUrl' => $qrCodeDataUri,
            ];

            $result = \App\Helpers\EmailNotificationHelper::sendNotificationByType('registration_success', $student->email, $data, ['queue' => true]);

            if ($result) {
                $this->success('Registration email resent successfully!', position: 'toast-bottom');
            } else {
                $this->warning('Failed to resend registration email. Please check logs.', position: 'toast-bottom');
            }
        } catch (\Exception $e) {
            \Log::error('Failed to resend registration notification: ' . $e->getMessage(), [
                'student_id' => $student->id,
                'email' => $student->email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->warning('Failed to resend registration email. Please check logs.', position: 'toast-bottom');
        }
    }
}; ?>
@assets
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
@endassets
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
                        <span class="text-primary">{{ $student->tiitvt_reg_no }}</span>
                    </li>
                </ul>
            </div>
        </div>

        <div class="flex gap-3">
            <x-button tooltip="Resend Registration Mail" icon="o-envelope" class="btn-success btn-outline"
                wire:click="resedRegisterMail" spinner="resedRegisterMail" responsive />
            <x-button tooltip="Edit Student" icon="o-pencil" class="btn-primary btn-outline"
                link="{{ route('admin.student.edit', $student->id) }}" responsive />
            @php
                $route = request()->query('tab') ? 'admin.fees.index' : 'admin.student.index';
            @endphp
            <x-button label="Back" icon="o-arrow-left" class="btn-primary btn-outline" link="{{ route($route) }}"
                responsive />
        </div>
    </div>

    <hr class="mb-5">

    <!-- Student Information -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Basic Information -->
        <div class="lg:col-span-2">
            <x-card shadow class="lg:col-span-2 h-fit">
                <div class="flex items-center gap-4 mb-6">
                    @if ($student->student_image)
                        <div class="avatar avatar-online avatar-placeholder">
                            <div class="bg-neutral text-neutral-content w-16 rounded-md">
                                <img src="{{ asset('storage/' . $student->student_image) }}" alt="Student Image"
                                    class="w-full h-full object-cover ">
                            </div>
                        </div>
                    @else
                        <div class="avatar avatar-online avatar-placeholder">
                            <div class="bg-neutral text-neutral-content w-16 rounded-md">
                                <span class="text-xl">{{ substr($student->first_name, 0, 1) }}</span>
                            </div>
                        </div>
                    @endif

                    <div>
                        <h2 class="text-2xl font-bold">{{ $student->full_name }}</h2>
                        <p class="text-gray-600">{{ $student->tiitvt_reg_no }}</p>
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
                                    <label class="text-sm font-medium text-gray-600">Date of
                                        Birth</label>
                                    <p class="text-sm">{{ $student->date_of_birth->format('M d, Y') }}
                                    </p>
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
                        <div class="bg-base-200 p-4 rounded-lg">
                            @if ($student->address['street'])
                                <p class="text-sm">{{ $student->address['street'] }},</p>
                            @endif
                            @if ($student->address['city'])
                                <p class="text-sm">{{ $student->address['city'] }},</p>
                            @endif
                            @if ($student->address['state'])
                                <span class="text-sm">{{ $student->address['state'] }}</span>,
                            @endif
                            @if ($student->address['country'])
                                <span class="text-sm">{{ $student->address['country'] }}</span>,
                            @endif
                            @if ($student->address['pincode'])
                                <span class="text-sm">{{ $student->address['pincode'] }}</span>.
                            @endif
                        </div>
                    </div>
                @endif
            </x-card>

            <!-- Student Image & Signature -->
            @if ($student->student_signature_image && $student->student_image)
                <div class="mt-6">
                    <x-card shadow>
                        <h3 class="text-lg font-semibold text-primary mb-4">Student Image & Signature
                        </h3>
                        <div class="flex gap-5 flex-wrap">
                            <img src="{{ asset('storage/' . $student->student_image) }}" alt="Student Image"
                                class="max-w-md h-40 border rounded-lg">

                            <img src="{{ asset('storage/' . $student->student_signature_image) }}"
                                alt="Student Signature" class="max-w-md h-40 border rounded-lg">
                        </div>
                    </x-card>
                </div>
            @endif
        </div>

        <div class="grid gap-6">
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

                    @if ($student->courses->count() > 0)
                        <div>
                            <label class="text-sm font-medium text-gray-600">Courses
                                ({{ $student->courses->count() }})</label>
                            <div class="space-y-1">
                                @foreach ($student->courses as $course)
                                    <div class="flex items-center justify-between">
                                        <p class="text-sm font-medium">{{ $course->name }}</p>
                                        @if ($course->pivot->batch_time)
                                            <span class="text-xs text-gray-500">{{ $course->pivot->batch_time }}</span>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div>
                        <label class="text-sm font-medium text-gray-600">Course Fees</label>
                        <p class="text-lg font-bold text-primary">
                            ₹{{ number_format($student->course_fees, 2) }}
                        </p>
                    </div>

                    @if ($student->down_payment)
                        <div>
                            <label class="text-sm font-medium text-gray-600">Down Payment</label>
                            <p class="text-sm">₹{{ number_format($student->down_payment, 2) }}</p>
                            <a href="{{ route('receipt.payment', ['type' => 'down-payment', 'id' => $student->id]) }}"
                                target="_blank" class="btn btn-xs btn-outline btn-success mt-2">
                                Print Receipt
                            </a>
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

            <!-- QR Code Section -->
            <x-card shadow title="Student QR Code">
                @if ($student->qrCode)
                    <x-slot:menu>
                        <x-button tooltip-left="Regenerate QR Code" icon="o-arrow-path"
                            class="btn-sm btn-outline btn-warning" wire:click="regenerateQRCode" />
                        <x-button tooltip-left="Download" icon="o-arrow-down-tray"
                            class="btn-sm btn-outline btn-primary" wire:click="downloadQRCode" />
                    </x-slot:menu>

                    <div class="text-center">
                        <div class="mb-4">
                            <img src="{{ $this->getQRCodeDataUri() }}" alt="Student QR Code"
                                class="mx-auto border-2 border-gray-200 rounded-lg shadow-sm"
                                style="max-width: 200px; height: auto;">

                            <div class="mt-3">
                                <p class="text-sm text-gray-600">Registration No:
                                    <strong>{{ $student->tiitvt_reg_no }}</strong>
                                </p>
                                <p class="text-xs text-gray-500">Generated:
                                    {{ $student->qrCode->created_at->format('M d, Y H:i') }}</p>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="text-center py-8">
                        <div class="text-gray-400 mb-4">
                            <svg class="w-16 h-16 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z">
                                </path>
                            </svg>
                        </div>
                        <p class="text-gray-600 mb-2">No QR Code available</p>
                        <p class="text-sm text-gray-500">QR codes are generated during student
                            registration</p>
                    </div>
                @endif
            </x-card>
        </div>
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
                        <label class="text-sm font-medium text-gray-600">Additional
                            Qualification</label>
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

    <!-- Payment Log -->
    <div class="mt-6">
        <x-card shadow>
            @php
                $summary = $this->getPaymentSummary();
            @endphp

            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-primary">Payment Log</h3>
                <x-button label="Add Payment" icon="o-plus" class="btn-sm btn-primary btn-outline"
                    wire:click="openAddPaymentModal" />
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="stat bg-base-200 rounded-lg">
                    <div class="stat-title">Course Fees</div>
                    <div class="stat-value text-primary text-lg">
                        ₹{{ number_format($summary['courseFees'], 2) }}
                    </div>
                </div>

                <div class="stat bg-base-200 rounded-lg">
                    <div class="stat-title">Total Paid</div>
                    <div class="stat-value text-success text-lg">
                        ₹{{ number_format($summary['totalPaid'], 2) }}
                    </div>
                    @if ($summary['downPayment'] > 0)
                        <div class="text-xs text-success mt-1">Incl. Down Payment:
                            ₹{{ number_format($summary['downPayment'], 2) }}</div>
                    @endif
                </div>

                <div class="stat bg-base-200 rounded-lg">
                    <div class="stat-title">Remaining Balance</div>
                    <div class="stat-value text-warning text-lg">
                        ₹{{ number_format($summary['remainingBalance'], 2) }}
                    </div>
                </div>

                <div class="stat bg-base-200 rounded-lg">
                    <div class="stat-title">Payments</div>
                    <div class="stat-value text-info text-lg">
                        {{ $summary['paidCount'] + $summary['pendingCount'] }}
                    </div>
                </div>
            </div>

            <!-- Payment Progress -->
            <div class="mb-6">
                <div class="flex justify-between items-center mb-2">
                    <span class="text-sm font-medium">Payment Progress</span>
                    <span class="text-sm text-gray-600">{{ number_format($summary['paymentProgress'], 1) }}%</span>
                </div>
                <x-progress value="{{ $summary['paymentProgress'] }}" max="100" class="w-full" />
            </div>

            <!-- Payment Records Table -->
            @php $paymentRows = $this->getPaymentRows(); @endphp
            @php
                $paymentHeaders = [
                    ['key' => 'row_num', 'label' => '#', 'class' => 'w-12'],
                    ['key' => 'amount', 'label' => 'Amount', 'class' => 'w-32'],
                    ['key' => 'status_label', 'label' => 'Status', 'class' => 'w-24'],
                    ['key' => 'paid_date', 'label' => 'Paid Date', 'class' => 'w-32'],
                    ['key' => 'notes', 'label' => 'Notes', 'class' => 'w-48'],
                ];
            @endphp

            <x-table :headers="$paymentHeaders" :rows="$paymentRows">
                @scope('cell_amount', $row)
                    <span class="font-bold">₹{{ number_format($row->amount, 2) }}</span>
                @endscope
                @scope('cell_status_label', $row)
                    <span class="badge {{ $row->status_class }} text-xs">{{ $row->status_label }}</span>
                @endscope
                @scope('cell_notes', $row)
                    <span class="max-w-xs truncate block">{{ $row->notes }}</span>
                @endscope
                @scope('actions', $row)
                    <div class="flex gap-1">
                        @if ($row->is_down_payment)
                            <a href="{{ route('receipt.payment', ['type' => 'down-payment', 'id' => $row->student_id]) }}"
                                target="_blank" class="btn btn-xs btn-outline btn-success">
                                Receipt
                            </a>
                        @else
                            @if ($row->is_pending)
                                <button wire:click="openMarkPaidModal({{ $row->payment_id }})"
                                    class="btn btn-xs btn-outline btn-success">
                                    Mark Paid
                                </button>
                                <button wire:click="openDeletePaymentModal({{ $row->payment_id }})"
                                    class="btn btn-xs btn-outline btn-error">
                                    Delete
                                </button>
                            @endif
                            @if ($row->is_paid)
                                <a href="{{ route('receipt.payment', ['type' => 'installment', 'id' => $row->payment_id]) }}"
                                    target="_blank" class="btn btn-xs btn-outline btn-success">
                                    Receipt
                                </a>
                            @endif
                        @endif
                    </div>
                @endscope

                <x-slot:empty>
                    <x-empty icon="o-banknotes" message="No payments recorded yet" />
                </x-slot:empty>
            </x-table>
        </x-card>
    </div>

    <!-- Add Payment Modal -->
    <x-modal wire:model="showAddPaymentModal" title="Record Payment" class="backdrop-blur">
        <div class="space-y-4">
            <x-input label="Payment Amount" type="number" wire:model.live.debounce.500ms="paymentAmount"
                step="0.01" min="0.01" placeholder="Enter payment amount" icon="o-currency-rupee" />

            <x-datepicker label="Payment Date" wire:model.live="paymentDate" icon="o-calendar" :config="$dateConfig"
                hint="Leave blank for today's date" />

            <x-textarea label="Notes (Optional)" wire:model="paymentNotes" class="textarea textarea-bordered w-full"
                rows="3" placeholder="Add any notes about this payment..." />
        </div>

        <x-slot:actions>
            <x-button label="Cancel" @click="$wire.closeAddPaymentModal()" />
            <x-button label="Record Payment" class="btn-primary" wire:click="addPayment" :disabled="empty($paymentAmount) || ($paymentAmount ?? 0) <= 0"
                spinner="addPayment" />
        </x-slot:actions>
    </x-modal>

    <!-- Mark as Paid Modal -->
    <x-modal wire:model="showMarkPaidModal" title="Mark Payment as Paid" class="backdrop-blur">
        <div class="space-y-4">
            @if ($selectedPayment)
                <div class="flex justify-between items-center">
                    <span class="text-lg font-bold text-primary">Amount:
                        ₹{{ number_format($selectedPayment->amount, 2) }}</span>
                </div>
            @endif

            <x-textarea label="Notes (Optional)" wire:model="paidNotes" class="textarea textarea-bordered w-full"
                rows="3" placeholder="Add any notes..." />
        </div>

        <x-slot:actions>
            <x-button label="Cancel" @click="$wire.closeMarkPaidModal()" />
            <x-button label="Mark as Paid" class="btn-success" wire:click="markPaymentAsPaid"
                spinner="markPaymentAsPaid" />
        </x-slot:actions>
    </x-modal>

    <!-- Delete Payment Modal -->
    <x-modal wire:model="showDeletePaymentModal" title="Delete Payment" class="backdrop-blur">
        <div class="space-y-4">
            <div class="text-error">Are you sure you want to delete this payment record? This action cannot be undone.
            </div>
        </div>
        <x-slot:actions>
            <x-button label="Cancel" @click="$wire.closeDeletePaymentModal()" />
            <x-button label="Delete" class="btn-error" wire:click="deletePayment" spinner="deletePayment" />
        </x-slot:actions>
    </x-modal>
</div>
