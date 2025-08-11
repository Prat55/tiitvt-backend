<?php

use Mary\Traits\Toast;
use App\Models\Student;
use App\Models\Installment;
use Livewire\Volt\Component;
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

    public function mount($student)
    {
        // If $student is a string (ID), fetch the model
        if (is_string($student)) {
            $this->student = Student::findOrFail($student);
        } else {
            $this->student = $student;
        }
    }

    public function openStatusModal($installmentId)
    {
        $this->selectedInstallment = Installment::findOrFail($installmentId);
        $this->newStatus = $this->selectedInstallment->status;
        $this->paidAmount = $this->selectedInstallment->amount;
        $this->notes = $this->selectedInstallment->notes ?? '';
        $this->showStatusModal = true;
    }

    public function closeStatusModal()
    {
        $this->showStatusModal = false;
        $this->selectedInstallment = null;
        $this->newStatus = '';
        $this->paidAmount = '';
        $this->notes = '';
    }

    public function openBulkUpdateModal()
    {
        $this->showBulkUpdateModal = true;
    }

    public function closeBulkUpdateModal()
    {
        $this->showBulkUpdateModal = false;
        $this->selectedInstallments = [];
    }

    public function toggleInstallmentSelection($installmentId)
    {
        if (in_array($installmentId, $this->selectedInstallments)) {
            $this->selectedInstallments = array_diff($this->selectedInstallments, [$installmentId]);
        } else {
            $this->selectedInstallments[] = $installmentId;
        }
    }

    public function selectAllInstallments()
    {
        $this->selectedInstallments = $this->student->installments->pluck('id')->toArray();
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

        $this->validate([
            'newStatus' => 'required|in:pending,paid,overdue',
            'paidAmount' => 'required_if:newStatus,paid|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $installment = $this->selectedInstallment;

            if ($this->newStatus === 'paid') {
                $installment->markAsPaid($this->paidAmount, $this->notes);
                $this->success('Installment marked as paid successfully!', position: 'toast-bottom');
            } elseif ($this->newStatus === 'overdue') {
                $installment->markAsOverdue();
                $installment->update(['notes' => $this->notes]);
                $this->success('Installment marked as overdue!', position: 'toast-bottom');
            } else {
                // Reset to pending
                $installment->update([
                    'status' => 'pending',
                    'paid_date' => null,
                    'paid_amount' => null,
                    'notes' => $this->notes,
                ]);
                $this->success('Installment reset to pending!', position: 'toast-bottom');
            }

            // Refresh the student data
            $this->student->refresh();
            $this->closeStatusModal();
        } catch (\Exception $e) {
            $this->error('Failed to update installment status. Please try again.', position: 'toast-bottom');
        }
    }

    public function bulkUpdateInstallmentStatus()
    {
        if (empty($this->selectedInstallments)) {
            $this->error('Please select at least one installment to update.', position: 'toast-bottom');
            return;
        }

        $this->validate([
            'bulkStatus' => 'required|in:pending,paid,overdue',
            'bulkNotes' => 'nullable|string|max:500',
        ]);

        try {
            $updatedCount = 0;
            $installments = Installment::whereIn('id', $this->selectedInstallments)->get();

            foreach ($installments as $installment) {
                if ($this->bulkStatus === 'paid') {
                    $installment->markAsPaid($installment->amount, $this->bulkNotes);
                } elseif ($this->bulkStatus === 'overdue') {
                    $installment->markAsOverdue();
                    $installment->update(['notes' => $this->bulkNotes]);
                } else {
                    // Reset to pending
                    $installment->update([
                        'status' => 'pending',
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
        } catch (\Exception $e) {
            $this->error('Failed to update installments. Please try again.', position: 'toast-bottom');
        }
    }

    public function getStatusOptions()
    {
        return [['id' => 'pending', 'name' => 'Pending'], ['id' => 'paid', 'name' => 'Paid'], ['id' => 'overdue', 'name' => 'Overdue']];
    }

    public function getInstallmentSummary()
    {
        $installments = $this->student->installments;
        $total = $installments->sum('amount');
        $paid = $installments->where('status', 'paid')->sum('paid_amount');
        $pending = $installments->where('status', '!=', 'paid')->sum('amount');
        $overdue = $installments->where('status', 'overdue')->sum('amount');
        $pendingCount = $installments->where('status', 'pending')->count();
        $overdueCount = $installments->where('status', 'overdue')->count();

        return [
            'total' => $total,
            'paid' => $paid,
            'pending' => $pending,
            'overdue' => $overdue,
            'pendingCount' => $pendingCount,
            'overdueCount' => $overdueCount,
            'paymentProgress' => $total > 0 ? ($paid / $total) * 100 : 0,
        ];
    }

    public function markOverdueInstallments()
    {
        try {
            $overdueCount = 0;
            $pendingInstallments = $this->student->installments()->where('status', 'pending')->get();

            foreach ($pendingInstallments as $installment) {
                if ($installment->isOverdue()) {
                    $installment->markAsOverdue();
                    $overdueCount++;
                }
            }

            if ($overdueCount > 0) {
                $this->success("Marked {$overdueCount} installment(s) as overdue!", position: 'toast-bottom');
                $this->student->refresh();
            } else {
                $this->info('No overdue installments found.', position: 'toast-bottom');
            }
        } catch (\Exception $e) {
            $this->error('Failed to mark overdue installments. Please try again.', position: 'toast-bottom');
        }
    }

    public function getNextDueInstallment()
    {
        return $this->student->installments()->where('status', 'pending')->where('due_date', '>=', now())->orderBy('due_date')->first();
    }

    public function getOverdueInstallments()
    {
        return $this->student->installments()->where('status', 'overdue')->orderBy('due_date')->get();
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
                @if ($student->student_image)
                    <div class="avatar avatar-online avatar-placeholder">
                        <div class="bg-neutral text-neutral-content w-16 rounded-md">
                            <img src="{{ asset('storage/' . $student->student_image) }}" alt="Student Image"
                                class="w-full h-full object-cover rounded-full">
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
                        <x-button label="Mark Overdue" icon="o-exclamation-triangle"
                            class="btn-sm btn-outline btn-warning" wire:click="markOverdueInstallments" />
                        <x-button label="Bulk Update" icon="o-arrow-path" class="btn-sm btn-outline btn-primary"
                            wire:click="openBulkUpdateModal" />
                    </div>
                </div>

                <!-- Summary Cards -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                    <div class="stat bg-base-200 rounded-lg">
                        <div class="stat-title">Total Amount</div>
                        <div class="stat-value text-primary text-lg">₹{{ number_format($summary['total'], 2) }}</div>
                    </div>
                    <div class="stat bg-base-200 rounded-lg">
                        <div class="stat-title">Paid Amount</div>
                        <div class="stat-value text-success text-lg">₹{{ number_format($summary['paid'], 2) }}</div>
                    </div>
                    <div class="stat bg-base-200 rounded-lg">
                        <div class="stat-title">Pending Amount</div>
                        <div class="stat-value text-warning text-lg">₹{{ number_format($summary['pending'], 2) }}
                        </div>
                    </div>
                    <div class="stat bg-base-200 rounded-lg">
                        <div class="stat-title">Overdue Amount</div>
                        <div class="stat-value text-error text-lg">₹{{ number_format($summary['overdue'], 2) }}</div>
                    </div>
                </div>

                <!-- Additional Summary Info -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <div class="stat bg-base-200 rounded-lg">
                        <div class="stat-title">Pending Count</div>
                        <div class="stat-value text-warning text-lg">{{ $summary['pendingCount'] }}</div>
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

                <!-- Installment Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach ($student->installments as $installment)
                        <x-card
                            class="bg-base-200 rounded-lg p-4 hover:shadow-md transition-shadow {{ $installment->isOverdue() ? 'border-error border-2' : '' }}">
                            <!-- Selection Checkbox -->
                            <div class="flex justify-between items-start mb-2">
                                <div class="flex items-center gap-2">
                                    <input type="checkbox" class="checkbox checkbox-sm checkbox-primary"
                                        wire:click="toggleInstallmentSelection({{ $installment->id }})"
                                        @if (in_array($installment->id, $selectedInstallments)) checked @endif>
                                    <span class="text-sm font-medium text-gray-600">Installment
                                        {{ $installment->installment_no }}</span>
                                </div>
                                <span class="badge {{ $installment->status_badge_class }} text-xs">
                                    {{ ucfirst($installment->status) }}
                                </span>
                            </div>

                            <div class="text-lg font-bold text-primary mb-1">
                                ₹{{ number_format($installment->amount, 2) }}
                            </div>

                            <div class="text-xs text-gray-500">
                                Due: {{ $installment->formatted_due_date }}
                                @if ($installment->isOverdue())
                                    <span class="text-error font-medium">(OVERDUE)</span>
                                @endif
                            </div>

                            @if ($installment->status === 'paid')
                                <div class="text-xs text-success mt-1">
                                    Paid: {{ $installment->formatted_paid_date }}
                                </div>
                                @if ($installment->paid_amount != $installment->amount)
                                    <div class="text-xs text-warning mt-1">
                                        Amount: ₹{{ number_format($installment->paid_amount, 2) }}
                                    </div>
                                @endif
                            @endif

                            @if ($installment->notes)
                                <div class="text-xs text-gray-600 mt-1 italic">
                                    "{{ $installment->notes }}"
                                </div>
                            @endif

                            <div class="mt-3">
                                <button wire:click="openStatusModal({{ $installment->id }})"
                                    class="btn btn-sm btn-outline btn-primary w-full">
                                    Update Status
                                </button>
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

    <!-- Installment Status Update Modal -->
    <x-modal wire:model="showStatusModal" title="Update Installment Status" class="backdrop-blur">
        <div class="space-y-4">
            <div class="flex justify-between items-center">
                <span class="text-lg font-bold text-primary">Installment
                    {{ $selectedInstallment?->installment_no }}</span>
                <span
                    class="text-lg font-bold text-primary">₹{{ number_format($selectedInstallment?->amount, 2) }}</span>
            </div>

            <x-select label="Status" wire:model="newStatus" class="select select-bordered w-full"
                :options="$this->getStatusOptions()" />

            @if ($newStatus === 'paid')
                <x-input label="Paid Amount" type="number" wire:model="paidAmount" step="0.01" min="0"
                    class="input input-bordered w-full" placeholder="Enter paid amount"></x-input>
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
                <x-select label="New Status" wire:model="bulkStatus" class="select select-bordered w-full"
                    :options="$this->getStatusOptions()" />
            </div>

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
                :disabled="empty($bulkStatus)" />
        </x-slot:actions>
    </x-modal>
</div>
