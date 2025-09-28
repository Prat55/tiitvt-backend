<?php

use Mary\Traits\Toast;
use App\Enums\RolesEnum;
use Livewire\Volt\Component;
use App\Enums\InstallmentStatusEnum;
use App\Models\{Student, Installment};
use App\Services\StudentQRService;
use Livewire\Attributes\{Layout, Title};

new class extends Component {
    use Toast;

    #[Title('Student Details')]
    public $student;
    public $selectedInstallment = null;
    public $showStatusModal = false;
    public $showBulkUpdateModal = false;
    public $newStatus = '';
    public $paidAmount = '';
    public $notes = '';
    public $selectedInstallments = [];
    public $bulkStatus = '';
    public $bulkNotes = '';
    public $bulkPaymentMethod = '';
    public $bulkChequeNumber = '';
    public $bulkWithdrawnDate;
    public $showPartialPaymentModal = false;
    public $partialPaymentAmount = '';
    public $partialPaymentNotes = '';
    public $paymentMethod = '';
    public $chequeNumber = '';
    public $withdrawnDate;
    public $partialPaymentMethod = '';
    public $partialChequeNumber = '';
    public $partialWithdrawnDate;

    public function mount($student)
    {
        if (hasAuthRole(RolesEnum::Admin->value)) {
            $this->student = Student::with(['qrCode', 'center', 'course'])->findOrFail($student);
        } else {
            $this->student = Student::with(['qrCode', 'center', 'course'])
                ->where('center_id', auth()->user()->center->id)
                ->findOrFail($student);
        }

        // Automatically check for overdue installments when component loads
        $this->checkAndMarkOverdueInstallments();
    }

    public function openStatusModal($installmentId)
    {
        $this->selectedInstallment = Installment::findOrFail($installmentId);
        $this->newStatus = $this->selectedInstallment->status;
        $this->paidAmount = $this->selectedInstallment->amount;
        $this->notes = $this->selectedInstallment->notes ?? '';
        $this->paymentMethod = $this->selectedInstallment->payment_method?->value ?? '';
        $this->chequeNumber = $this->selectedInstallment->cheque_number ?? '';
        $this->withdrawnDate = $this->selectedInstallment->withdrawn_date?->format('Y-m-d') ?? '';
        $this->showStatusModal = true;
    }

    public function closeStatusModal()
    {
        $this->showStatusModal = false;
        $this->selectedInstallment = null;
        $this->newStatus = '';
        $this->paidAmount = '';
        $this->notes = '';
        $this->paymentMethod = '';
        $this->chequeNumber = '';
        $this->withdrawnDate;
    }

    public function openBulkUpdateModal()
    {
        $this->showBulkUpdateModal = true;
    }

    public function closeBulkUpdateModal()
    {
        $this->showBulkUpdateModal = false;
        $this->selectedInstallments = [];
        $this->bulkStatus = '';
        $this->bulkNotes = '';
        $this->bulkPaymentMethod = '';
        $this->bulkChequeNumber = '';
        $this->bulkWithdrawnDate;
    }

    public function openPartialPaymentModal($installmentId)
    {
        $this->selectedInstallment = Installment::findOrFail($installmentId);
        $this->partialPaymentAmount = '';
        $this->partialPaymentNotes = '';
        $this->partialPaymentMethod = '';
        $this->partialChequeNumber = '';
        $this->partialWithdrawnDate;
        $this->showPartialPaymentModal = true;
    }

    public function closePartialPaymentModal()
    {
        $this->showPartialPaymentModal = false;
        $this->selectedInstallment = null;
        $this->partialPaymentAmount = '';
        $this->partialPaymentNotes = '';
        $this->partialPaymentMethod = '';
        $this->partialChequeNumber = '';
        $this->partialWithdrawnDate;
    }

    public function toggleInstallmentSelection($installmentId)
    {
        if (in_array($installmentId, $this->selectedInstallments)) {
            $this->selectedInstallments = array_diff($this->selectedInstallments, [$installmentId]);
        } else {
            $this->selectedInstallments[] = $installmentId;
        }
    }

    public function clearInstallmentSelection()
    {
        $this->selectedInstallments = [];
    }

    public function updateInstallmentStatus()
    {
        if (!$this->selectedInstallment) {
            return;
        }

        // Custom validation for paid amount when status is partial
        if ($this->isStatusPartial($this->newStatus)) {
            if (empty($this->paidAmount) || !is_numeric($this->paidAmount) || $this->paidAmount <= 0) {
                $this->addError('paidAmount', 'Paid amount is required and must be a valid positive number when status is partial.');
                return;
            }
            if ($this->paidAmount >= $this->selectedInstallment->amount) {
                $this->addError('paidAmount', 'For partial payment, the amount must be less than the full installment amount.');
                return;
            }
        }

        $this->validate([
            'newStatus' => 'required',
            'notes' => 'nullable|string|max:500',
            'paymentMethod' => 'nullable|in:cash,cheque',
            'chequeNumber' => 'required_if:paymentMethod,cheque|nullable|string|max:50',
            'withdrawnDate' => 'required_if:paymentMethod,cheque|nullable|date',
        ]);

        try {
            $installment = $this->selectedInstallment;

            if ($this->isStatusPartial($this->newStatus)) {
                $paymentMethod = null;
                if ($this->paymentMethod) {
                    try {
                        $paymentMethod = \App\Enums\PaymentMethodEnum::from($this->paymentMethod);
                    } catch (\ValueError $e) {
                        $this->addError('paymentMethod', 'Invalid payment method selected.');
                        return;
                    }
                }

                // Convert withdrawnDate to Carbon instance if provided
                $withdrawnDate = null;
                if ($this->withdrawnDate) {
                    try {
                        $withdrawnDate = \Carbon\Carbon::parse($this->withdrawnDate);
                    } catch (\Exception $e) {
                        $this->addError('withdrawnDate', 'Invalid withdrawn date format.');
                        return;
                    }
                }

                $installment->addPartialPayment($this->paidAmount, $this->notes, $paymentMethod, $this->chequeNumber, $withdrawnDate);
                $this->success('Partial payment added successfully!', position: 'toast-bottom');
            } elseif ($this->isStatusPaid($this->newStatus)) {
                $paymentMethod = null;
                if ($this->paymentMethod) {
                    try {
                        $paymentMethod = \App\Enums\PaymentMethodEnum::from($this->paymentMethod);
                    } catch (\ValueError $e) {
                        $this->addError('paymentMethod', 'Invalid payment method selected.');
                        return;
                    }
                }

                // Convert withdrawnDate to Carbon instance if provided
                $withdrawnDate = null;
                if ($this->withdrawnDate) {
                    try {
                        $withdrawnDate = \Carbon\Carbon::parse($this->withdrawnDate);
                    } catch (\Exception $e) {
                        $this->addError('withdrawnDate', 'Invalid withdrawn date format.');
                        return;
                    }
                }

                $installment->markAsPaid($installment->amount, $this->notes, $paymentMethod, $this->chequeNumber, $withdrawnDate);
                $this->success('Installment marked as fully paid!', position: 'toast-bottom');
            } elseif ($this->isStatusOverdue($this->newStatus)) {
                $installment->markAsOverdue();
                $installment->update(['notes' => $this->notes]);
                $this->success('Installment marked as overdue!', position: 'toast-bottom');
            } else {
                // Reset to pending
                $installment->update([
                    'status' => InstallmentStatusEnum::Pending->value,
                    'paid_date' => null,
                    'paid_amount' => null,
                    'notes' => $this->notes,
                ]);
                $this->success('Installment reset to pending!', position: 'toast-bottom');
            }

            // Refresh the student data
            $this->student->refresh();
            $this->closeStatusModal();

            // Recalculate overdue status after update
            $this->checkAndMarkOverdueInstallments();
        } catch (\Exception $e) {
            // Log the actual error for debugging
            \Log::error('Installment status update failed: ' . $e->getMessage(), [
                'installment_id' => $this->selectedInstallment?->id,
                'newStatus' => $this->newStatus,
                'paidAmount' => $this->paidAmount,
                'paymentMethod' => $this->paymentMethod,
                'trace' => $e->getTraceAsString(),
            ]);

            $this->error('Failed to update installment status: ' . $e->getMessage(), position: 'toast-bottom');
        }
    }

    public function bulkUpdateInstallmentStatus()
    {
        if (empty($this->selectedInstallments)) {
            $this->error('Please select at least one installment to update.', position: 'toast-bottom');
            return;
        }

        $this->validate([
            'bulkStatus' => 'required|in:' . implode(',', InstallmentStatusEnum::values()),
            'bulkNotes' => 'nullable|string|max:500',
            'bulkPaymentMethod' => 'nullable|in:cash,cheque',
            'bulkChequeNumber' => 'required_if:bulkPaymentMethod,cheque|nullable|string|max:50',
            'bulkWithdrawnDate' => 'required_if:bulkPaymentMethod,cheque|nullable|date',
        ]);

        try {
            $updatedCount = 0;
            $installments = Installment::whereIn('id', $this->selectedInstallments)->get();

            foreach ($installments as $installment) {
                if ($this->isStatusPaid($this->bulkStatus)) {
                    $paymentMethod = $this->bulkPaymentMethod ? \App\Enums\PaymentMethodEnum::from($this->bulkPaymentMethod) : null;

                    // Convert withdrawnDate to Carbon instance if provided
                    $withdrawnDate = null;
                    if ($this->bulkWithdrawnDate) {
                        try {
                            $withdrawnDate = \Carbon\Carbon::parse($this->bulkWithdrawnDate);
                        } catch (\Exception $e) {
                            // Skip this installment if date is invalid
                            continue;
                        }
                    }

                    $installment->markAsPaid($installment->amount, $this->bulkNotes, $paymentMethod, $this->bulkChequeNumber, $withdrawnDate);
                } elseif ($this->isStatusOverdue($this->bulkStatus)) {
                    $installment->markAsOverdue();
                    $installment->update(['notes' => $this->bulkNotes]);
                } else {
                    // Reset to pending
                    $installment->update([
                        'status' => InstallmentStatusEnum::Pending->value,
                        'paid_date' => null,
                        'paid_amount' => null,
                        'notes' => $this->bulkNotes,
                    ]);
                }
                $updatedCount++;
            }

            $this->success("Successfully updated {$updatedCount} installments!", position: 'toast-bottom');

            // Refresh the student data
            $this->student->refresh();
            $this->closeBulkUpdateModal();

            // Recalculate overdue status after bulk update
            $this->checkAndMarkOverdueInstallments();
        } catch (\Exception $e) {
            $this->error('Failed to update installments. Please try again.', position: 'toast-bottom');
        }
    }

    public function addPartialPayment()
    {
        if (!$this->selectedInstallment) {
            return;
        }

        $this->validate([
            'partialPaymentAmount' => 'required|numeric|min:0.01|max:' . $this->selectedInstallment->getRemainingAmount(),
            'partialPaymentNotes' => 'nullable|string|max:500',
            'partialPaymentMethod' => 'nullable|in:cash,cheque',
            'partialChequeNumber' => 'required_if:partialPaymentMethod,cheque|nullable|string|max:50',
            'partialWithdrawnDate' => 'required_if:partialPaymentMethod,cheque|nullable|date',
        ]);

        $installment = $this->selectedInstallment;
        $paymentMethod = $this->partialPaymentMethod ? \App\Enums\PaymentMethodEnum::from($this->partialPaymentMethod) : null;
        $installment->addPartialPayment($this->partialPaymentAmount, $this->partialPaymentNotes, $paymentMethod, $this->partialChequeNumber, $this->partialWithdrawnDate);

        $this->success('Partial payment added successfully!', position: 'toast-bottom');

        // Refresh the student data
        $this->student->refresh();
        $this->closePartialPaymentModal();

        // Recalculate overdue status after update
        $this->checkAndMarkOverdueInstallments();

        // try {
        // } catch (\Exception $e) {
        //     $this->error('Failed to add partial payment. Please try again.', position: 'toast-bottom');
        // }
    }

    public function getStatusOptions()
    {
        return collect(InstallmentStatusEnum::all())
            ->map(function ($status) {
                return ['id' => $status->value, 'name' => $status->label()];
            })
            ->toArray();
    }

    public function getBulkStatusOptions()
    {
        return collect(InstallmentStatusEnum::all())
            ->filter(function ($status) {
                // Exclude partial status from bulk update options
                return $status !== InstallmentStatusEnum::Partial;
            })
            ->map(function ($status) {
                return ['id' => $status->value, 'name' => $status->label()];
            })
            ->toArray();
    }

    public function getPaymentMethodOptions()
    {
        return collect(\App\Enums\PaymentMethodEnum::cases())
            ->map(function ($method) {
                return ['id' => $method->value, 'name' => $method->label()];
            })
            ->toArray();
    }

    public function isStatusPaid($status)
    {
        if (is_string($status)) {
            return $status === 'paid';
        }

        if (is_object($status) && isset($status->value)) {
            return $status->value === 'paid';
        }

        return false;
    }

    public function isStatusOverdue($status)
    {
        if (is_string($status)) {
            return $status === 'overdue';
        }

        if (is_object($status) && isset($status->value)) {
            return $status->value === 'overdue';
        }

        return false;
    }

    public function isStatusPartial($status)
    {
        if (is_string($status)) {
            return $status === 'partial';
        }

        if (is_object($status) && isset($status->value)) {
            return $status->value === 'partial';
        }

        return false;
    }

    public function getInstallmentSummary()
    {
        $installments = $this->student->installments;
        $total = $installments->sum('amount');

        // Calculate total paid amount (including partial payments)
        $totalPaid = $installments->sum('paid_amount');

        // Calculate overdue amount more comprehensively using new model methods
        $overdueAmount = 0;
        $overdueCount = 0;
        $partialCount = 0;
        $partialAmount = 0;

        foreach ($installments as $installment) {
            if ($installment->status->isOverdue()) {
                $overdueAmount += $installment->getRemainingAmount();
                $overdueCount++;
            } elseif ($installment->shouldBeOverdue()) {
                // Include installments that should be overdue (past due date but not fully paid)
                $overdueAmount += $installment->getOverdueAmount();
                if ($installment->getOverdueAmount() > 0) {
                    $overdueCount++;
                }
            } elseif ($installment->status->isPartial()) {
                $partialCount++;
                $partialAmount += $installment->getRemainingAmount();
            }
        }

        $pending = $installments->filter(fn($installment) => $installment->status->isPending())->sum('amount');
        $pendingCount = $installments->filter(fn($installment) => $installment->status->isPending())->count();

        return [
            'total' => $total,
            'paid' => $totalPaid,
            'pending' => $pending,
            'partial' => $partialAmount,
            'overdue' => $overdueAmount,
            'pendingCount' => $pendingCount,
            'partialCount' => $partialCount,
            'overdueCount' => $overdueCount,
            'paymentProgress' => $total > 0 ? ($totalPaid / $total) * 100 : 0,
        ];
    }

    public function markOverdueInstallments()
    {
        try {
            $overdueCount = 0;
            $pendingInstallments = $this->student->installments->filter(fn($installment) => $installment->status->isPending());

            foreach ($pendingInstallments as $installment) {
                if ($installment->isOverdue()) {
                    $installment->markAsOverdue();
                    $overdueCount++;
                }
            }

            if ($overdueCount > 0) {
                $this->success("Marked {$overdueCount} installment(s) as overdue!", position: 'toast-bottom');
                $this->student->refresh();

                // Recalculate overdue status after marking
                $this->checkAndMarkOverdueInstallments();
            } else {
                $this->info('No overdue installments found.', position: 'toast-bottom');
            }
        } catch (\Exception $e) {
            $this->error('Failed to mark overdue installments. Please try again.', position: 'toast-bottom');
        }
    }

    public function markSpecificInstallmentAsOverdue($installmentId)
    {
        try {
            $installment = $this->student->installments()->findOrFail($installmentId);

            if ($installment->status->isPending()) {
                $installment->markAsOverdue();
                $this->success("Installment {$installment->installment_no} marked as overdue!", position: 'toast-bottom');

                // Refresh and recalculate
                $this->student->refresh();
                $this->checkAndMarkOverdueInstallments();
            } else {
                $this->error('Only pending installments can be marked as overdue.', position: 'toast-bottom');
            }
        } catch (\Exception $e) {
            $this->error('Failed to mark installment as overdue. Please try again.', position: 'toast-bottom');
        }
    }

    public function refreshOverdueStatus()
    {
        try {
            // Check and mark overdue installments
            $this->checkAndMarkOverdueInstallments();

            // Refresh the student data
            $this->student->refresh();

            $this->success('Student data and installments refreshed. Overdue status recalculated.', position: 'toast-bottom');
        } catch (\Exception $e) {
            $this->error('Failed to refresh student data. Please try again.', position: 'toast-bottom');
        }
    }

    public function getNextDueInstallment()
    {
        return $this->student->installments->filter(fn($installment) => $installment->status->isPending() && $installment->due_date >= now())->sortBy('due_date')->first();
    }

    public function getOverdueInstallments()
    {
        return $this->student->installments->filter(fn($installment) => $installment->status->isOverdue())->sortBy('due_date');
    }

    public function getAllOverdueInstallments()
    {
        $installments = $this->student->installments;
        $overdueInstallments = collect();

        foreach ($installments as $installment) {
            if ($installment->status->isOverdue() || $installment->shouldBeOverdue()) {
                $overdueInstallments->push($installment);
            }
        }

        return $overdueInstallments->sortBy('due_date');
    }

    public function checkAndMarkOverdueInstallments()
    {
        try {
            $overdueCount = 0;
            $pendingInstallments = $this->student->installments->filter(fn($installment) => $installment->status->isPending());

            foreach ($pendingInstallments as $installment) {
                if ($installment->isOverdue()) {
                    $installment->markAsOverdue();
                    $overdueCount++;
                }
            }

            if ($overdueCount > 0) {
                // Refresh the student data to reflect changes
                $this->student->refresh();
            }
        } catch (\Exception $e) {
            // Silently handle errors during automatic check
        }
    }

    public function regenerateQRCode()
    {
        try {
            if (!$this->student->qrCode) {
                $this->error('No QR code found for this student.');
                return;
            }

            $qrService = new StudentQRService();

            // Delete the old QR code file if it exists
            if ($this->student->qrCode->qr_code_path && \Storage::disk('public')->exists($this->student->qrCode->qr_code_path)) {
                \Storage::disk('public')->delete($this->student->qrCode->qr_code_path);
            }

            // Generate new QR data with just the verification URL
            $newQrData = $qrService->generateQrDataWithToken($this->student, $this->student->qrCode->qr_token);

            // Regenerate the QR code with logo using the new data
            $qrCodePath = $qrService->generateQrCodeWithLogo($newQrData, $this->student->qrCode->id);

            // Update the QR code record with the new path and data
            $this->student->qrCode->update([
                'qr_code_path' => $qrCodePath,
                'qr_data' => $newQrData,
            ]);

            // Refresh the student data
            $this->student->refresh();

            $this->success('QR Code regenerated successfully with logo and verification URL only!', position: 'toast-bottom');
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
            // Sanitize the filename by removing/replacing invalid characters
            $safeRegNo = preg_replace('/[^a-zA-Z0-9_-]/', '_', $this->student->tiitvt_reg_no);
            $filename = 'student_' . $safeRegNo . '_qr_code.png';

            // Ensure filename is not too long (max 255 characters)
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
            $qrService = new StudentQRService();
            return $qrService->generateQRCodeDataUri($this->student->qrCode->qr_data);
        }
        return null;
    }

    public function resedRegisterMail()
    {
        //
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
                        <span class="text-primary">{{ $student->tiitvt_reg_no }}</span>
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
                        <h3 class="text-lg font-semibold text-primary mb-4">Student Image & Signature</h3>
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
                            <p class="text-sm">{{ $student->no_of_installments }} installments</p>
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
                        <p class="text-sm text-gray-500">QR codes are generated during student registration</p>
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

    <!-- Installments -->
    @if ($student->installments && $student->installments->count() > 0)
        <div class="mt-6">
            <x-card shadow>
                @php
                    $summary = $this->getInstallmentSummary();
                @endphp

                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-primary">Installment Details</h3>
                    <div class="flex gap-2">
                        <x-button label="Refresh Overdue" icon="o-arrow-path" class="btn-sm btn-outline btn-info"
                            wire:click="refreshOverdueStatus" tooltip="Refresh overdue status" responsive />
                        <x-button label="Mark Overdue" icon="o-exclamation-triangle"
                            class="btn-sm btn-outline btn-warning" wire:click="markOverdueInstallments"
                            tooltip="Mark overdue installments" responsive />
                    </div>
                </div>

                <!-- Summary Cards -->
                <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
                    <div class="stat bg-base-200 rounded-lg">
                        <div class="stat-title">Total Amount</div>
                        <div class="stat-value text-primary text-lg">₹{{ number_format($summary['total'], 2) }}</div>
                    </div>
                    <div class="stat bg-base-200 rounded-lg">
                        <div class="stat-title">Paid Amount</div>
                        <div class="stat-value text-success text-lg">₹{{ number_format($summary['paid'], 2) }}</div>
                    </div>
                    <div class="stat bg-base-200 rounded-lg">
                        <div class="stat-title">Partial Amount</div>
                        <div class="stat-value text-info text-lg">₹{{ number_format($summary['partial'], 2) }}
                            @if ($summary['partial'] > 0)
                                <div class="text-xs text-info mt-1">({{ $summary['partialCount'] }} partial)</div>
                            @endif
                        </div>
                    </div>
                    <div class="stat bg-base-200 rounded-lg">
                        <div class="stat-title">Pending Amount</div>
                        <div class="stat-value text-warning text-lg">₹{{ number_format($summary['pending'], 2) }}
                        </div>
                    </div>
                    <div class="stat bg-base-200 rounded-lg">
                        <div class="stat-title">Overdue Amount</div>
                        <div class="stat-value text-error text-lg">
                            ₹{{ number_format($summary['overdue'], 2) }}
                            @if ($summary['overdue'] > 0)
                                <div class="text-xs text-error mt-1">({{ $summary['overdueCount'] }} overdue)</div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Additional Summary Info -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                    <div class="stat bg-base-200 rounded-lg">
                        <div class="stat-title">Pending Count</div>
                        <div class="stat-value text-warning text-lg">{{ $summary['pendingCount'] }}</div>
                    </div>
                    <div class="stat bg-base-200 rounded-lg">
                        <div class="stat-title">Partial Count</div>
                        <div class="stat-value text-info text-lg">{{ $summary['partialCount'] }}</div>
                    </div>
                    <div class="stat bg-base-200 rounded-lg">
                        <div class="stat-title">Overdue Count</div>
                        <div class="stat-value text-error text-lg">{{ $summary['overdueCount'] }}</div>
                    </div>
                    <div class="stat bg-base-200 rounded-lg">
                        <div class="stat-title">Next Due</div>
                        <div class="stat-value text-info text-lg">
                            @if ($this->getNextDueInstallment())
                                {{ $this->getNextDueInstallment()->formatted_due_date }}
                            @else
                                N/A
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Payment Progress -->
                <div class="mb-6">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-sm font-medium">Payment Progress</span>
                        <span
                            class="text-sm text-gray-600">{{ number_format($summary['paymentProgress'], 1) }}%</span>
                    </div>
                    <x-progress value="{{ $summary['paymentProgress'] }}" max="100" class="w-full" />
                </div>

                <!-- Overdue Installments Alert -->
                @if ($summary['overdue'] > 0)
                    <div class="mb-6 p-4 bg-error/10 border border-error rounded-lg">
                        <div class="flex items-center gap-2 mb-2">
                            <x-icon name="o-exclamation-triangle" class="text-error" />
                            <h4 class="text-lg font-semibold text-error">Overdue Installments</h4>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                            <div>
                                <strong>Total Overdue Amount:</strong>
                                <span class="text-error font-bold">₹{{ number_format($summary['overdue'], 2) }}</span>
                            </div>
                            <div>
                                <strong>Overdue Count:</strong>
                                <span class="text-error font-bold">{{ $summary['overdueCount'] }}</span>
                                installment(s)
                            </div>
                            <div>
                                <strong>Action Required:</strong>
                                <span class="text-error">Immediate attention needed</span>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Debug Information (Development Only) -->
                @if (config('app.debug'))
                    <div class="mb-6 p-4 bg-base-300 rounded-lg">
                        <h4 class="text-sm font-semibold text-primary mb-2">Debug Information</h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">
                            <div>
                                <strong>Total Installments:</strong> {{ $student->installments->count() }}<br>
                                <strong>Pending:</strong> {{ $summary['pendingCount'] }}<br>
                                <strong>Overdue:</strong> {{ $summary['overdueCount'] }}<br>
                                <strong>Paid:</strong>
                                {{ $student->installments->filter(fn($installment) => $installment->status->isPaid())->count() }}
                            </div>
                            <div>
                                <strong>Overdue Amount:</strong> ₹{{ number_format($summary['overdue'], 2) }}<br>
                                <strong>Pending Amount:</strong> ₹{{ number_format($summary['pending'], 2) }}<br>
                                <strong>Paid Amount:</strong> ₹{{ number_format($summary['paid'], 2) }}
                            </div>
                            <div>
                                <strong>Next Due:</strong>
                                @if ($this->getNextDueInstallment())
                                    {{ $this->getNextDueInstallment()->formatted_due_date }}
                                @else
                                    N/A
                                @endif
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Installment Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach ($student->installments as $installment)
                        <x-card
                            class="bg-base-200 rounded-lg p-4 hover:shadow-md transition-shadow {{ $installment->shouldBeOverdue() ? 'border-error border-2' : '' }}">
                            <!-- Selection Checkbox -->
                            <div class="flex justify-between items-start mb-2">
                                <div class="flex items-center gap-2">
                                    <input type="checkbox" class="checkbox checkbox-sm checkbox-primary"
                                        wire:click="toggleInstallmentSelection({{ $installment->id }})"
                                        @if (in_array($installment->id, $selectedInstallments)) checked @endif
                                        @if ($installment->status->isPaid()) disabled @endif>
                                    <span class="text-sm font-medium text-gray-600">Installment
                                        {{ $installment->installment_no }}</span>
                                </div>
                                <span class="badge {{ $installment->status_badge_class }} text-xs">
                                    {{ $installment->status->label() }}
                                </span>
                            </div>

                            <div class="text-lg font-bold text-primary mb-1">
                                ₹{{ number_format($installment->amount, 2) }}
                            </div>

                            <div class="text-xs text-gray-500">
                                Due: {{ $installment->formatted_due_date }}
                                @if ($installment->shouldBeOverdue())
                                    <span class="text-error font-medium">(OVERDUE)</span>
                                @endif
                            </div>

                            @if ($installment->status->isPaid())
                                <div class="text-xs text-success mt-1">
                                    Paid: {{ $installment->formatted_paid_date }}
                                </div>
                                @if ($installment->paid_amount != $installment->amount)
                                    <div class="text-xs text-warning mt-1">
                                        Amount: ₹{{ number_format($installment->paid_amount, 2) }}
                                    </div>
                                @endif
                                @if ($installment->payment_method)
                                    <div class="text-xs text-gray-600 mt-1">
                                        Method: {{ $installment->payment_method->label() }}
                                    </div>
                                @endif
                                @if ($installment->cheque_number)
                                    <div class="text-xs text-gray-600 mt-1">
                                        Cheque: {{ $installment->cheque_number }}
                                    </div>
                                @endif
                                @if ($installment->withdrawn_date)
                                    <div class="text-xs text-gray-600 mt-1">
                                        Withdrawn: {{ $installment->withdrawn_date->format('d/m/Y') }}
                                    </div>
                                @endif
                            @elseif ($installment->status->isPartial())
                                <div class="text-xs text-info mt-1">
                                    Partial: ₹{{ number_format($installment->paid_amount, 2) }} paid
                                </div>
                                <div class="text-xs text-warning mt-1">
                                    Remaining: ₹{{ number_format($installment->getRemainingAmount(), 2) }}
                                </div>
                                @if ($installment->payment_method)
                                    <div class="text-xs text-gray-600 mt-1">
                                        Method: {{ $installment->payment_method->label() }}
                                    </div>
                                @endif
                                @if ($installment->cheque_number)
                                    <div class="text-xs text-gray-600 mt-1">
                                        Cheque: {{ $installment->cheque_number }}
                                    </div>
                                @endif
                                @if ($installment->withdrawn_date)
                                    <div class="text-xs text-gray-600 mt-1">
                                        Withdrawn: {{ $installment->withdrawn_date->format('d/m/Y') }}
                                    </div>
                                @endif
                            @endif

                            @if ($installment->notes)
                                <div class="text-xs text-gray-600 mt-1 italic">
                                    "{{ $installment->notes }}"
                                </div>
                            @endif

                            <div class="mt-3">
                                <div class="flex gap-2 flex-wrap">
                                    <button wire:click="openStatusModal({{ $installment->id }})"
                                        class="btn btn-sm btn-outline btn-primary flex-1">
                                        Update Status
                                    </button>
                                    @if (($installment->status->isPending() || $installment->status->isPartial()) && $installment->getRemainingAmount() > 0)
                                        <button wire:click="openPartialPaymentModal({{ $installment->id }})"
                                            class="btn btn-sm btn-outline btn-info">
                                            Add Payment
                                        </button>
                                    @endif
                                    @if ($installment->status->isPending() && $installment->shouldBeOverdue())
                                        <button wire:click="markSpecificInstallmentAsOverdue({{ $installment->id }})"
                                            class="btn btn-sm btn-outline btn-error">
                                            Mark Overdue
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </x-card>
                    @endforeach
                </div>

                <!-- Bulk Actions Info -->
                @if (count($selectedInstallments) > 0)
                    <div class="mt-4 p-3 bg-info/10 border border-info rounded-lg">
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-info">
                                {{ count($selectedInstallments) }} installment(s) selected
                            </span>
                            <div class="flex gap-2">
                                <x-button label="Clear Selection" class="btn-xs btn-ghost"
                                    wire:click="clearInstallmentSelection" />
                                <x-button label="Update Selected" class="btn-xs btn-primary"
                                    wire:click="openBulkUpdateModal" />
                            </div>
                        </div>
                    </div>
                @endif
            </x-card>
        </div>
    @endif

    <!-- Installment Status Update Modal -->
    <x-modal wire:model="showStatusModal" title="Update Installment Status" class="backdrop-blur">
        <div class="space-y-4">
            <div class="flex justify-between items-center">
                <span class="text-lg font-bold text-primary">Installment
                    {{ $selectedInstallment?->installment_no }}</span>
                <span
                    class="text-lg font-bold text-primary">₹{{ number_format($selectedInstallment?->amount, 2) }}</span>
            </div>

            <x-select label="Status" wire:model.live="newStatus" class="select select-bordered w-full"
                :options="$this->getStatusOptions()" />

            @if ($this->isStatusPartial($newStatus))
                <x-input label="Paid Amount" type="number" wire:model="paidAmount" step="0.01" min="0.01"
                    :max="$selectedInstallment?->amount" class="input input-bordered w-full" placeholder="Enter partial payment amount"
                    hint="Maximum: ₹{{ number_format($selectedInstallment?->amount, 2) }}"></x-input>

                <x-select label="Payment Method" wire:model.live="paymentMethod"
                    class="select select-bordered w-full" :options="$this->getPaymentMethodOptions()" placeholder="Select payment method" />

                @if ($paymentMethod === 'cheque')
                    <x-input label="Cheque Number" wire:model="chequeNumber" class="input input-bordered w-full"
                        placeholder="Enter cheque number" />
                    <x-input label="Withdrawn Date" type="date" wire:model="withdrawnDate"
                        class="input input-bordered w-full" />
                @endif
            @elseif ($this->isStatusPaid($newStatus))
                <x-select label="Payment Method" wire:model.live="paymentMethod"
                    class="select select-bordered w-full" :options="$this->getPaymentMethodOptions()" placeholder="Select payment method" />

                @if ($paymentMethod === 'cheque')
                    <x-input label="Cheque Number" wire:model="chequeNumber" class="input input-bordered w-full"
                        placeholder="Enter cheque number" />
                    <x-input label="Withdrawn Date" type="date" wire:model="withdrawnDate"
                        class="input input-bordered w-full" />
                @endif
            @endif

            <x-textarea label="Notes (Optional)" wire:model="notes" class="textarea textarea-bordered w-full"
                rows="3" placeholder="Add any notes about this installment..."></x-textarea>
        </div>

        <x-slot:actions>
            <x-button label="Cancel" @click="$wire.showStatusModal = false" />
            <x-button label="Update Status" class="btn-primary" wire:click="updateInstallmentStatus" />
        </x-slot:actions>
    </x-modal>

    <!-- Bulk Installment Status Update Modal -->
    <x-modal wire:model="showBulkUpdateModal" title="Bulk Update Installment Status" class="backdrop-blur">
        <div class="space-y-4">
            <x-alert class="alert-info" icon="o-information-circle" title="Info"
                description="You have selected {{ count($selectedInstallments) }} installment(s) for bulk update." />

            <div>
                <x-select label="New Status" wire:model.live="bulkStatus" class="select select-bordered w-full"
                    :options="$this->getBulkStatusOptions()" />
            </div>

            @if ($this->isStatusPaid($bulkStatus))
                <div>
                    <x-select label="Payment Method" wire:model.live="bulkPaymentMethod"
                        class="select select-bordered w-full" :options="$this->getPaymentMethodOptions()" placeholder="Select payment method" />
                </div>

                @if ($bulkPaymentMethod === 'cheque')
                    <div>
                        <x-input label="Cheque Number" wire:model="bulkChequeNumber"
                            class="input input-bordered w-full" placeholder="Enter cheque number" />
                    </div>
                    <div>
                        <x-input label="Withdrawn Date" type="date" wire:model="bulkWithdrawnDate"
                            class="input input-bordered w-full" />
                    </div>
                @endif
            @endif

            <div>
                <x-textarea label="Notes (Optional)" wire:model="bulkNotes" class="textarea textarea-bordered w-full"
                    rows="3" placeholder="Add notes for all selected installments..." />
            </div>

            <x-alert class="alert-warning" icon="o-exclamation-triangle" title="Warning"
                description="This action will update all selected installments to the chosen status. This cannot be undone." />
        </div>

        <x-slot:actions>
            <x-button label="Cancel" @click="$wire.showBulkUpdateModal = false" />
            <x-button label="Update All Selected" class="btn-primary" wire:click="bulkUpdateInstallmentStatus"
                :disabled="!empty($bulkStatus)" />
        </x-slot:actions>
    </x-modal>

    <!-- Partial Payment Modal -->
    <x-modal wire:model="showPartialPaymentModal" title="Add Partial Payment" class="backdrop-blur">
        <div class="space-y-4">
            <div class="flex justify-between items-center">
                <span class="text-lg font-bold text-primary">Installment
                    {{ $selectedInstallment?->installment_no }}</span>
                <div class="text-right">
                    <div class="text-lg font-bold text-primary">₹{{ number_format($selectedInstallment?->amount, 2) }}
                    </div>
                    @if ($selectedInstallment?->status->isPartial())
                        <div class="text-sm text-info">Already paid:
                            ₹{{ number_format($selectedInstallment?->paid_amount, 2) }}</div>
                        <div class="text-sm text-warning">Remaining:
                            ₹{{ number_format($selectedInstallment?->getRemainingAmount(), 2) }}</div>
                    @endif
                </div>
            </div>

            <x-input label="Payment Amount" type="number" wire:model.live="partialPaymentAmount" step="0.01"
                min="0.01" :max="$selectedInstallment?->getRemainingAmount()" placeholder="Enter payment amount"
                hint="Max: ₹{{ number_format($selectedInstallment?->getRemainingAmount(), 2) }}" />

            <x-select label="Payment Method" wire:model="partialPaymentMethod" class="select select-bordered w-full"
                :options="$this->getPaymentMethodOptions()" placeholder="Select payment method" />

            @if ($partialPaymentMethod === 'cheque')
                <x-input label="Cheque Number" wire:model="partialChequeNumber" placeholder="Enter cheque number" />
                <x-input label="Withdrawn Date" type="date" wire:model="partialWithdrawnDate" />
            @endif

            <x-textarea label="Notes (Optional)" wire:model="partialPaymentNotes"
                class="textarea textarea-bordered w-full" rows="3"
                placeholder="Add any notes about this payment..."></x-textarea>

            <x-alert class="alert-info" icon="o-information-circle" title="Info"
                description="If the payment amount equals or exceeds the remaining amount, the installment will be marked as fully paid." />
        </div>

        <x-slot:actions>
            <x-button label="Cancel" @click="$wire.showPartialPaymentModal = false" />
            <x-button label="Add Payment" class="btn-primary" wire:click="addPartialPayment" :disabled="empty($partialPaymentAmount) || $partialPaymentAmount <= 0" />
        </x-slot:actions>
    </x-modal>
</div>
