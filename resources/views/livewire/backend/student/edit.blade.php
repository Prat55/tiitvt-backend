<?php

use Mary\Traits\Toast;
use App\Enums\RolesEnum;
use Illuminate\Support\Str;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\{Layout, Title};
use App\Models\{Student, Center, Course};

new class extends Component {
    use WithFileUploads, Toast;

    // Form properties
    #[Title('Edit Student')]
    public Student $student;

    // Basic Information
    public string $tiitvt_reg_no = '';
    public string $first_name = '';
    public string $fathers_name = '';
    public ?string $surname = '';

    // Contact Information
    public array $address = [
        'street' => '',
        'city' => '',
        'state' => '',
        'pincode' => '',
        'country' => '',
    ];
    public ?string $telephone_no = '';
    public string $email = '';
    public ?string $mobile = '';

    // Personal Information
    public ?string $date_of_birth = '';
    public ?int $age = 0;

    // Academic Information
    public ?string $qualification = '';
    public ?string $additional_qualification = '';
    public ?string $reference = '';

    // Course and Batch Information
    public ?string $course_taken = '';
    public ?string $batch_time = '';
    public ?string $scheme_given = '';

    // Fees Information
    public float $course_fees = 0;
    public ?float $down_payment = 0;
    public ?int $no_of_installments = 0;
    public ?string $installment_date = '';

    // Calculated fields for display
    public float $remaining_amount = 0;
    public float $total_payable = 0;
    public array $installment_breakdown = [];

    // Installment management
    public array $existing_installments = [];
    public float $total_paid_amount = 0;
    public float $total_pending_amount = 0;
    public int $paid_installments_count = 0;
    public int $pending_installments_count = 0;

    // Installment editing
    public bool $edit_installment_amounts = false;
    public array $editable_installment_amounts = [];

    // Additional Fields
    public ?string $enrollment_date = '';
    public ?string $incharge_name = '';

    // Relationships
    public int $center_id = 0;
    public int $course_id = 0;

    // File uploads
    public $student_signature_image;
    public $student_image;

    // Config for file uploads
    public $config = [
        'aspectRatio' => 1,
    ];

    public $dateConfig = ['altFormat' => 'd/m/Y'];

    public function mount(Student $student)
    {
        $this->student = $student;
        $this->loadStudentData();
        $this->loadExistingInstallments();
        $this->checkOverdueInstallments();
        $this->calculateInstallments();
    }

    private function loadStudentData()
    {
        $this->tiitvt_reg_no = $this->student->tiitvt_reg_no ?? '';
        $this->first_name = $this->student->first_name ?? '';
        $this->fathers_name = $this->student->fathers_name ?? '';
        $this->surname = $this->student->surname ?? '';

        $this->address = $this->student->address ?? [
            'street' => '',
            'city' => '',
            'state' => '',
            'pincode' => '',
            'country' => '',
        ];

        $this->telephone_no = $this->student->telephone_no ?? '';
        $this->email = $this->student->email ?? '';
        $this->mobile = $this->student->mobile ?? '';

        $this->date_of_birth = $this->student->date_of_birth ? $this->student->date_of_birth->format('Y-m-d') : '';
        $this->age = $this->student->age ?? 0;

        $this->qualification = $this->student->qualification ?? '';
        $this->additional_qualification = $this->student->additional_qualification ?? '';
        $this->reference = $this->student->reference ?? '';

        $this->course_taken = $this->student->course_taken ?? '';
        $this->batch_time = $this->student->batch_time ?? '';
        $this->scheme_given = $this->student->scheme_given ?? '';

        $this->course_fees = $this->student->course_fees ?? 0;
        $this->down_payment = $this->student->down_payment ?? 0;
        $this->no_of_installments = $this->student->no_of_installments ?? 0;
        $this->installment_date = $this->student->installment_date ? $this->student->installment_date->format('Y-m-d') : '';

        $this->enrollment_date = $this->student->enrollment_date ? $this->student->enrollment_date->format('Y-m-d') : '';
        $this->incharge_name = $this->student->incharge_name ?? '';

        $this->center_id = $this->student->center_id ?? 0;
        $this->course_id = $this->student->course_id ?? 0;
    }

    // Load existing installments and calculate totals
    private function loadExistingInstallments(): void
    {
        $this->existing_installments = $this->student->installments()->orderBy('installment_no')->get()->toArray();

        $this->total_paid_amount = $this->student->installments()->where('status', 'paid')->sum('amount');

        $this->total_pending_amount = $this->student
            ->installments()
            ->whereIn('status', ['pending', 'overdue'])
            ->sum('amount');

        $this->paid_installments_count = $this->student->installments()->where('status', 'paid')->count();

        $this->pending_installments_count = $this->student
            ->installments()
            ->whereIn('status', ['pending', 'overdue'])
            ->count();
    }

    // Check and update overdue installments
    private function checkOverdueInstallments(): void
    {
        $overdueInstallments = $this->student->installments()->where('status', 'pending')->where('due_date', '<', now())->get();

        foreach ($overdueInstallments as $installment) {
            $installment->markAsOverdue();
        }

        // Reload installments data after updating statuses
        if ($overdueInstallments->count() > 0) {
            $this->loadExistingInstallments();
        }
    }

    // Helper method to format currency
    public function formatCurrency($amount): string
    {
        return '₹' . number_format($amount, 2);
    }

    // Calculate installments
    public function calculateInstallments(): void
    {
        $this->remaining_amount = 0;
        $this->total_payable = $this->course_fees ?? 0;
        $this->installment_breakdown = [];

        if (($this->course_fees ?? 0) > 0) {
            if (($this->down_payment ?? 0) > ($this->course_fees ?? 0)) {
                $this->down_payment = $this->course_fees;
            }

            // Calculate remaining amount after down payment
            $this->remaining_amount = ($this->course_fees ?? 0) - ($this->down_payment ?? 0);

            // Generate installment breakdown if number of installments is specified
            if (($this->no_of_installments ?? 0) > 0 && $this->remaining_amount > 0) {
                // If we have existing installments, preserve paid ones and recalculate only pending ones
                if (!empty($this->existing_installments)) {
                    $this->calculateInstallmentsWithExisting();
                } else {
                    $this->calculateNewInstallments();
                }
            }
        }
    }

    // Calculate installments when there are existing ones
    private function calculateInstallmentsWithExisting(): void
    {
        $paidAmount = $this->total_paid_amount;
        $remainingAfterPaid = $this->remaining_amount - $paidAmount;

        // If all installments are paid, no need to recalculate
        if ($remainingAfterPaid <= 0) {
            $this->installment_breakdown = [];
            return;
        }

        // Calculate how many pending installments we need
        $pendingInstallmentsNeeded = $this->no_of_installments - $this->paid_installments_count;

        if ($pendingInstallmentsNeeded <= 0) {
            // All installments are paid, no pending ones needed
            $this->installment_breakdown = [];
            return;
        }

        // Calculate amount per pending installment
        $installmentAmount = round($remainingAfterPaid / $pendingInstallmentsNeeded, 2);
        $remainingForLastInstallment = $remainingAfterPaid;

        $this->installment_breakdown = [];

        // Add existing paid installments to breakdown (for display purposes)
        foreach ($this->existing_installments as $installment) {
            if ($installment['status'] === 'paid') {
                $this->installment_breakdown[] = [
                    'installment_no' => $installment['installment_no'],
                    'amount' => $installment['amount'],
                    'due_date' => \Carbon\Carbon::parse($installment['due_date'])->format('d/m/Y'),
                    'status' => 'paid',
                    'paid_date' => $installment['paid_date'] ? \Carbon\Carbon::parse($installment['paid_date'])->format('d/m/Y') : null,
                    'is_existing' => true,
                ];
            }
        }

        // Calculate new pending installments
        $pendingCount = 0;
        for ($i = 1; $i <= $this->no_of_installments; $i++) {
            // Skip if this installment number already exists and is paid
            $existingPaid = collect($this->existing_installments)->where('installment_no', $i)->where('status', 'paid')->first();

            if ($existingPaid) {
                continue; // Skip, already added above
            }

            // Calculate amount for this pending installment
            if ($pendingCount == $pendingInstallmentsNeeded - 1) {
                // Last pending installment gets the remaining amount to avoid rounding errors
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
                'status' => 'pending',
                'is_existing' => false,
            ];

            $pendingCount++;
        }

        // Sort by installment number
        usort($this->installment_breakdown, function ($a, $b) {
            return $a['installment_no'] <=> $b['installment_no'];
        });
    }

    // Calculate new installments (when no existing ones)
    private function calculateNewInstallments(): void
    {
        $installmentAmount = round($this->remaining_amount / ($this->no_of_installments ?? 1), 2);
        $remainingForLastInstallment = $this->remaining_amount;

        // Generate installment breakdown
        $this->installment_breakdown = [];

        for ($i = 1; $i <= ($this->no_of_installments ?? 0); $i++) {
            if ($i == ($this->no_of_installments ?? 0)) {
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
                'status' => 'pending',
                'is_existing' => false,
            ];
        }
    }

    // Validation rules
    protected function rules(): array
    {
        return [
            'tiitvt_reg_no' => 'required|string|max:50|unique:students,tiitvt_reg_no,' . $this->student->id,
            'first_name' => 'required|string|max:100',
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
            'tiitvt_reg_no.required' => 'TIITVT Registration Number is required.',
            'tiitvt_reg_no.unique' => 'This TIITVT Registration Number already exists.',
            'first_name.required' => 'First name is required.',
            'fathers_name.required' => 'Father\'s name is required.',
            'email.required' => 'Email is required.',
            'email.unique' => 'This email already exists.',
            'email.email' => 'Please enter a valid email address.',
            'course_fees.required' => 'Course fees is required.',
            'down_payment.lte' => 'Down payment cannot exceed course fees.',
            'center_id.required' => 'Please select a center.',
            'course_id.required' => 'Please select a course.',
        ];
    }

    // Update student
    public function save(): void
    {
        // Additional validation for fees
        if (($this->course_fees ?? 0) > 0) {
            if (($this->down_payment ?? 0) > ($this->course_fees ?? 0)) {
                $this->error('Down payment cannot exceed course fees.', position: 'toast-bottom');
                return;
            }

            if (($this->no_of_installments ?? 0) > 0 && $this->remaining_amount <= 0) {
                $this->error('Cannot create installments when remaining amount is zero or negative.', position: 'toast-bottom');
                return;
            }

            // Check if we have existing installments and validate the new configuration
            if (!empty($this->existing_installments)) {
                $newRemainingAmount = ($this->course_fees ?? 0) - ($this->down_payment ?? 0);

                // Ensure new remaining amount is not less than paid installments
                if ($newRemainingAmount < $this->total_paid_amount) {
                    $this->error('New remaining amount cannot be less than the total amount of paid installments (' . $this->formatCurrency($this->total_paid_amount) . ').', position: 'toast-bottom');
                    return;
                }

                // Ensure new installment count is not less than paid installments
                if (($this->no_of_installments ?? 0) < $this->paid_installments_count) {
                    $this->error('Cannot decrease installments below the number of paid installments (' . $this->paid_installments_count . ').', position: 'toast-bottom');
                    return;
                }
            }
        }

        $this->validate();

        try {
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
                'course_taken' => $this->course_taken,
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
                // Delete old image if exists
                if ($this->student->student_signature_image) {
                    Storage::disk('public')->delete($this->student->student_signature_image);
                }
                $data['student_signature_image'] = $this->student_signature_image->store('students/signatures', 'public');
            }

            if ($this->student_image) {
                // Delete old image if exists
                if ($this->student->student_image) {
                    Storage::disk('public')->delete($this->student->student_image);
                }
                $data['student_image'] = $this->student_image->store('students/images', 'public');
            }

            $this->student->update($data);

            // Handle installment updates if needed
            if (($this->no_of_installments ?? 0) > 0 && $this->remaining_amount > 0) {
                // Check if we have custom installment amounts applied
                if (!empty($this->editable_installment_amounts)) {
                    $this->updateInstallmentsInDatabase();
                } else {
                    $this->updateInstallments();
                }
            }

            $this->success('Student updated successfully!', position: 'toast-bottom');
            $this->redirect(route('admin.student.show', $this->student->id));
        } catch (\Exception $e) {
            $this->error('Failed to update student. Please try again.', position: 'toast-bottom');
        }
    }

    // Update installments while preserving paid ones
    private function updateInstallments(): void
    {
        try {
            // Get current installments from database
            $currentInstallments = $this->student->installments()->orderBy('installment_no')->get();

            // Separate paid and pending installments
            $paidInstallments = $currentInstallments->where('status', 'paid');
            $pendingInstallments = $currentInstallments->whereIn('status', ['pending', 'overdue']);

            // Calculate remaining amount after paid installments
            $paidAmount = $paidInstallments->sum('amount');
            $remainingAfterPaid = $this->remaining_amount - $paidAmount;

            // If all installments are paid, no need to update
            if ($remainingAfterPaid <= 0) {
                return;
            }

            // Calculate how many pending installments we need
            $pendingInstallmentsNeeded = $this->no_of_installments - $paidInstallments->count();

            if ($pendingInstallmentsNeeded <= 0) {
                // All installments are paid, no pending ones needed
                return;
            }

            // Calculate amount per pending installment
            $installmentAmount = round($remainingAfterPaid / $pendingInstallmentsNeeded, 2);
            $remainingForLastInstallment = $remainingAfterPaid;

            // Delete existing pending installments
            $pendingInstallments->each(function ($installment) {
                $installment->delete();
            });

            // Create new pending installments
            $pendingCount = 0;
            for ($i = 1; $i <= $this->no_of_installments; $i++) {
                // Skip if this installment number already exists and is paid
                $existingPaid = $paidInstallments->where('installment_no', $i)->first();

                if ($existingPaid) {
                    continue; // Skip, already exists and paid
                }

                // Calculate amount for this pending installment
                if ($pendingCount == $pendingInstallmentsNeeded - 1) {
                    // Last pending installment gets the remaining amount to avoid rounding errors
                    $amount = round($remainingForLastInstallment, 2);
                } else {
                    $amount = $installmentAmount;
                    $remainingForLastInstallment -= $amount;
                }

                // Create new installment
                \App\Models\Installment::create([
                    'student_id' => $this->student->id,
                    'installment_no' => $i,
                    'amount' => $amount,
                    'due_date' => $this->installment_date ? \Carbon\Carbon::parse($this->installment_date)->addMonths($i - 1) : now()->addMonths($i - 1),
                    'status' => 'pending',
                ]);

                $pendingCount++;
            }

            // Reload installments data
            $this->loadExistingInstallments();
        } catch (\Exception $e) {
            // Log error but don't fail the entire save operation
            \Log::error('Failed to update installments for student ' . $this->student->id . ': ' . $e->getMessage());
        }
    }

    // Refresh installment data
    public function refreshInstallments(): void
    {
        $this->loadExistingInstallments();
        $this->checkOverdueInstallments();
        $this->calculateInstallments();
    }

    // Get warning message about existing installments
    public function getExistingInstallmentsWarning(): ?string
    {
        if (empty($this->existing_installments)) {
            return null;
        }

        $message = 'This student has existing installments. ';

        if ($this->paid_installments_count > 0) {
            $message .= "{$this->paid_installments_count} installment(s) are already paid (₹{$this->total_paid_amount}). ";
        }

        if ($this->pending_installments_count > 0) {
            $message .= "{$this->pending_installments_count} installment(s) are pending (₹{$this->total_pending_amount}). ";
        }

        $message .= 'Only pending installments will be updated when you change fees or installment settings.';

        return $message;
    }

    // Get summary of what will happen when saving
    public function getSaveSummary(): ?string
    {
        if (empty($this->existing_installments)) {
            return null;
        }

        $summary = "When you save, the following will happen:\n";

        if ($this->paid_installments_count > 0) {
            $summary .= "• {$this->paid_installments_count} paid installment(s) will be preserved\n";
        }

        if ($this->pending_installments_count > 0) {
            $summary .= "• {$this->pending_installments_count} pending installment(s) will be updated with new amounts\n";
        }

        $summary .= '• New installment amounts will be calculated based on remaining amount after paid installments';

        return $summary;
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

    // Calculate installments when fees change
    public function updatedCourseFees(): void
    {
        // Check if we're decreasing course fees and have paid installments
        if (($this->course_fees ?? 0) < $this->student->course_fees && $this->total_paid_amount > 0) {
            $newRemainingAmount = ($this->course_fees ?? 0) - ($this->down_payment ?? 0);
            if ($newRemainingAmount < $this->total_paid_amount) {
                $this->error('Cannot decrease course fees below the total amount of paid installments (' . $this->formatCurrency($this->total_paid_amount) . ').', position: 'toast-bottom');
                $this->course_fees = $this->student->course_fees;
                return;
            }
        }

        // Clear related fields if course fees is 0 or empty
        if (($this->course_fees ?? 0) <= 0) {
            $this->down_payment = 0;
            $this->no_of_installments = 0;
            $this->installment_breakdown = [];
            $this->remaining_amount = 0;
            $this->total_payable = 0;
        } else {
            // Recalculate installments if course fees is valid
            // This will preserve paid installments and recalculate only pending ones
            $this->calculateInstallments();
        }
    }

    public function updatedDownPayment(): void
    {
        // Ensure down payment doesn't exceed course fees
        if (($this->down_payment ?? 0) > ($this->course_fees ?? 0)) {
            $this->down_payment = $this->course_fees;
        }

        // Check if we're increasing down payment and have paid installments
        if (($this->down_payment ?? 0) > ($this->student->down_payment ?? 0) && $this->total_paid_amount > 0) {
            $newRemainingAmount = ($this->course_fees ?? 0) - ($this->down_payment ?? 0);
            if ($newRemainingAmount < $this->total_paid_amount) {
                $this->error('Cannot increase down payment above the amount that would make remaining amount less than paid installments (' . $this->formatCurrency($this->total_paid_amount) . ').', position: 'toast-bottom');
                $this->down_payment = $this->student->down_payment ?? 0;
                return;
            }
        }

        // Clear installments if down payment equals course fees
        if (($this->down_payment ?? 0) == ($this->course_fees ?? 0)) {
            $this->no_of_installments = 0;
            $this->installment_breakdown = [];
            $this->remaining_amount = 0;
        } else {
            // Recalculate installments if down payment is valid
            // This will preserve paid installments and recalculate only pending ones
            $this->calculateInstallments();
        }
    }

    public function updatedNoOfInstallments(): void
    {
        // Ensure number of installments is reasonable
        if (($this->no_of_installments ?? 0) > 24) {
            $this->no_of_installments = 24;
        }

        // Check if we're decreasing the number of installments
        $currentInstallmentCount = count($this->existing_installments);
        if (($this->no_of_installments ?? 0) < $currentInstallmentCount) {
            // Check if we have more paid installments than the new count
            if ($this->paid_installments_count > ($this->no_of_installments ?? 0)) {
                $this->error('Cannot decrease installments below the number of paid installments (' . $this->paid_installments_count . ').', position: 'toast-bottom');
                $this->no_of_installments = $currentInstallmentCount;
                return;
            }
        }

        // Clear breakdown if no installments
        if (($this->no_of_installments ?? 0) <= 0) {
            $this->installment_breakdown = [];
            $this->remaining_amount = ($this->course_fees ?? 0) - ($this->down_payment ?? 0);
        } else {
            // Calculate installments if number is valid
            // This will preserve paid installments and recalculate only pending ones
            $this->calculateInstallments();
        }
    }

    public function updatedInstallmentDate(): void
    {
        // Recalculate installments if installment date is valid
        // This will preserve paid installments and recalculate only pending ones
        $this->calculateInstallments();
    }

    // Toggle installment amount editing mode
    public function toggleInstallmentEditing(): void
    {
        $this->edit_installment_amounts = !$this->edit_installment_amounts;

        if ($this->edit_installment_amounts) {
            // Initialize editable amounts with current breakdown (only for pending installments)
            $this->editable_installment_amounts = [];
            foreach ($this->installment_breakdown as $installment) {
                // Only allow editing of pending installments
                if (!isset($installment['status']) || $installment['status'] === 'pending' || $installment['status'] === 'overdue') {
                    $this->editable_installment_amounts[$installment['installment_no']] = $installment['amount'];
                }
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

        // Calculate total of editable amounts + paid amounts
        $editableTotal = array_sum($this->editable_installment_amounts);
        $paidTotal = $this->total_paid_amount;
        $totalAmount = $editableTotal + $paidTotal;

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

    // Update installments in database with custom amounts
    public function updateInstallmentsInDatabase(): void
    {
        if (empty($this->installment_breakdown)) {
            return;
        }

        // Get current installments from database
        $currentInstallments = $this->student->installments()->orderBy('installment_no')->get();

        // Separate paid and pending installments
        $paidInstallments = $currentInstallments->where('status', 'paid');
        $pendingInstallments = $currentInstallments->whereIn('status', ['pending', 'overdue']);

        // Delete existing pending installments
        $pendingInstallments->each(function ($installment) {
            $installment->delete();
        });

        // Create new pending installments with custom amounts
        foreach ($this->installment_breakdown as $installment) {
            // Skip if this installment number already exists and is paid
            $existingPaid = $paidInstallments->where('installment_no', $installment['installment_no'])->first();
            if ($existingPaid) {
                continue; // Skip, already exists and paid
            }

            $dueDate = $this->installment_date ? \Carbon\Carbon::parse($this->installment_date)->addMonths($installment['installment_no'] - 1) : now()->addMonths($installment['installment_no'] - 1);

            \App\Models\Installment::create([
                'student_id' => $this->student->id,
                'installment_no' => $installment['installment_no'],
                'amount' => $installment['amount'],
                'due_date' => $dueDate,
                'status' => 'pending',
            ]);
        }

        // Reload installments data
        $this->loadExistingInstallments();
    }

    public function rendering(\Illuminate\View\View $view)
    {
        $view->centers = Center::active()
            ->latest()
            ->get(['id', 'name']);

        $view->courses = Course::active()
            ->latest()
            ->get(['id', 'name']);
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
                            {{ $student->tiitvt_reg_no }}
                        </a>
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
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div class="md:col-span-2">
                    <h3 class="text-lg font-semibold text-primary">Basic Information</h3>
                </div>

                <x-input label="TIITVT Registration No" wire:model="tiitvt_reg_no"
                    placeholder="Enter registration number" icon="o-identification" readonly />

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

                <x-choices-offline label="Course" wire:model="course_id" placeholder="Select course"
                    icon="o-academic-cap" :options="$courses" single searchable clearable />

                <x-input label="Course Taken" wire:model="course_taken" placeholder="Enter course taken (optional)"
                    icon="o-book-open" />

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

                    @if ($this->getExistingInstallmentsWarning())
                        <x-alert title="Existing Installments:" icon="o-exclamation-triangle"
                            class="alert-warning mt-3" description="{{ $this->getExistingInstallmentsWarning() }}"
                            dismissible />
                    @endif
                </div>

                <x-input label="Course Fees" wire:model.live="course_fees" step="0.01"
                    placeholder="Enter course fees" icon="o-currency-rupee" />

                <x-input label="Down Payment" wire:model.live="down_payment" step="0.01"
                    placeholder="Enter down payment (optional)" icon="o-currency-rupee"
                    hint="Cannot exceed course fees" />

                <x-input label="Number of Installments" wire:model.live="no_of_installments" type="number"
                    placeholder="Enter number of installments (optional)" icon="o-calculator" min="0"
                    hint="Leave empty if no installments" min="{{ $paid_installments_count + 1 }}" />

                <x-datepicker label="Installment Date (optional)" wire:model.live="installment_date"
                    icon="o-calendar" :config="$dateConfig" />

                <x-datepicker label="Enrollment Date" wire:model="enrollment_date" icon="o-calendar"
                    :config="$dateConfig" />

                <x-input label="Incharge Name" wire:model="incharge_name"
                    placeholder="Enter incharge name (optional)" icon="o-user" />
            </div>

            <!-- Fees Summary -->
            @if (($course_fees ?? 0) > 0)
                <div class="grid grid-cols-1 gap-6">
                    <div>
                        <h3 class="text-lg font-semibold text-primary mb-4">Fees Summary</h3>

                        <div class="bg-base-200 rounded-lg p-4 space-y-3">
                            <div class="flex justify-between items-center">
                                <span class="font-medium">Total Course Fees:</span>
                                <span class="font-bold text-lg">{{ $this->formatCurrency($total_payable ?? 0) }}</span>
                            </div>

                            @if (($down_payment ?? 0) > 0)
                                <div class="flex justify-between items-center">
                                    <span class="font-medium">Down Payment:</span>
                                    <span
                                        class="font-bold text-success">-{{ $this->formatCurrency($down_payment ?? 0) }}</span>
                                </div>
                            @endif

                            <div class="flex justify-between items-center border-t pt-3">
                                <span class="font-medium">Remaining Amount:</span>
                                <span
                                    class="font-bold text-lg text-primary">{{ $this->formatCurrency($remaining_amount ?? 0) }}</span>
                            </div>

                            @if (($no_of_installments ?? 0) > 0 && count($installment_breakdown ?? []) > 0)
                                <div class="mt-4">
                                    <div class="flex justify-between items-center mb-3">
                                        <h4 class="font-semibold text-base">Installment Breakdown
                                            ({{ $no_of_installments ?? 0 }} installments)</h4>
                                        <div class="flex gap-2">
                                            @if (!$edit_installment_amounts)
                                                @php
                                                    $hasPendingInstallments =
                                                        collect($installment_breakdown)
                                                            ->whereIn('status', ['pending', 'overdue'])
                                                            ->count() > 0;
                                                @endphp
                                                @if ($hasPendingInstallments)
                                                    <x-button label="Edit Amounts" icon="o-pencil"
                                                        class="btn-sm btn-outline"
                                                        wire:click="toggleInstallmentEditing" />
                                                @endif
                                            @else
                                                <x-button label="Apply" icon="o-check" class="btn-sm btn-primary"
                                                    wire:click="applyCustomInstallmentAmounts" />
                                                <x-button label="Reset" icon="o-arrow-path"
                                                    class="btn-sm btn-outline"
                                                    wire:click="resetToEqualDistribution" />
                                                <x-button label="Cancel" icon="o-x-mark" class="btn-sm btn-ghost"
                                                    wire:click="toggleInstallmentEditing" />
                                            @endif
                                            @if (!empty($existing_installments))
                                                <x-button label="Refresh Installments" icon="o-arrow-path"
                                                    class="btn-sm btn-outline" wire:click="refreshInstallments" />
                                            @endif
                                        </div>
                                    </div>

                                    <!-- Show existing installments summary if any -->
                                    @if (!empty($existing_installments))
                                        <div class="bg-base-100 rounded-lg p-3 border mb-3">
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                                                <div class="flex justify-between items-center">
                                                    <span class="font-medium">Paid Installments:</span>
                                                    <span
                                                        class="font-bold text-success">{{ $paid_installments_count }}
                                                        ({{ $this->formatCurrency($total_paid_amount) }})</span>
                                                </div>
                                                <div class="flex justify-between items-center">
                                                    <span class="font-medium">Pending Installments:</span>
                                                    <span
                                                        class="font-bold text-warning">{{ $pending_installments_count }}
                                                        ({{ $this->formatCurrency($total_pending_amount) }})</span>
                                                </div>
                                            </div>
                                        </div>
                                    @endif

                                    <!-- Display calculated installment amount for pending installments -->
                                    @php
                                        $pendingInstallments = collect($installment_breakdown)->where(
                                            'status',
                                            'pending',
                                        );
                                        $pendingCount = $pendingInstallments->count();
                                    @endphp

                                    @if ($pendingCount > 0)
                                        <div class="bg-base-100 rounded-lg p-3 border mb-3">
                                            <div class="flex justify-between items-center">
                                                <span class="font-medium">Monthly Installment Amount (Pending):</span>
                                                <span
                                                    class="font-bold text-lg text-primary">{{ $this->formatCurrency($pendingInstallments->first()['amount'] ?? 0) }}</span>
                                            </div>
                                        </div>
                                    @endif

                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                                        @foreach ($installment_breakdown as $installment)
                                            <div
                                                class="bg-base-100 rounded-lg p-3 border {{ $installment['status'] === 'paid' ? 'border-success' : ($installment['status'] === 'overdue' ? 'border-error' : 'border-warning') }}">
                                                <div class="flex justify-between items-start mb-2">
                                                    <div class="flex gap-2 items-center">
                                                        <div class="text-sm text-gray-600">
                                                            Installment {{ $installment['installment_no'] }}
                                                        </div>
                                                        @if (isset($installment['is_existing']) && $installment['is_existing'])
                                                            <div class="text-xs text-info">Existing</div>
                                                        @endif
                                                    </div>
                                                    @if (isset($installment['status']))
                                                        <span
                                                            class="badge badge-sm {{ $installment['status'] === 'paid' ? 'badge-success' : ($installment['status'] === 'overdue' ? 'badge-error' : 'badge-warning') }}">
                                                            {{ ucfirst($installment['status']) }}
                                                        </span>
                                                    @endif
                                                </div>

                                                @if (
                                                    $edit_installment_amounts &&
                                                        (!isset($installment['status']) ||
                                                            $installment['status'] === 'pending' ||
                                                            $installment['status'] === 'overdue'))
                                                    <x-input
                                                        wire:model="editable_installment_amounts.{{ $installment['installment_no'] }}"
                                                        type="number" step="0.01" min="0"
                                                        placeholder="Enter amount" />
                                                @else
                                                    <div class="font-bold text-lg">
                                                        {{ $this->formatCurrency($installment['amount']) }}
                                                    </div>
                                                @endif

                                                <div class="flex gap-2 items-center mt-1">
                                                    <div class="text-xs text-gray-500">
                                                        Due: {{ $installment['due_date'] }}
                                                    </div>
                                                    @if (isset($installment['paid_date']) && $installment['paid_date'])
                                                        <div class="text-xs text-success">Paid:
                                                            {{ $installment['paid_date'] }}</div>
                                                    @endif
                                                </div>

                                                @if ($edit_installment_amounts && isset($installment['status']) && $installment['status'] === 'paid')
                                                    <div class="text-xs text-success mt-1">
                                                        <x-icon name="o-lock-closed" class="w-3 h-3 inline" />
                                                        Cannot edit paid installment
                                                    </div>
                                                @endif
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
                                                Only pending/overdue installments can be edited. Total must equal
                                                remaining amount: {{ $this->formatCurrency($remaining_amount) }}
                                            </p>
                                        </div>
                                    @else
                                        <div class="mt-3 text-xs bg-base-100 p-2 rounded-md">
                                            <x-alert title="Note:" icon="o-exclamation-triangle"
                                                description="Paid installments are preserved when recalculating. Only pending installments are updated based on the new course fees and installment count. The last pending installment may vary slightly to account for rounding." />
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
                            <img src="{{ $student->student_image ? asset('storage/' . $student->student_image) : 'https://placehold.co/300x300?text=Image' }}"
                                alt="Student Image" class="w-32 h-32 object-cover rounded-lg">
                        </x-file>
                    </div>
                </div>

                <div>
                    <h3 class="text-lg font-semibold text-primary">Student Signature</h3>
                    <div class="space-y-2 mt-3">
                        <x-file wire:model="student_signature_image" accept="image/*"
                            placeholder="Upload student signature" icon="o-photo" hint="Max 2MB" crop-after-change
                            :crop-config="$config">
                            <img src="{{ $student->student_signature_image ? asset('storage/' . $student->student_signature_image) : 'https://placehold.co/300x300?text=Signature' }}"
                                alt="Signature" class="w-32 h-32 object-cover rounded-lg">
                        </x-file>
                    </div>
                </div>
            </div>

            <!-- Save Summary -->
            @if ($this->getSaveSummary())
                <div class="bg-base-200 rounded-lg p-4">
                    <h4 class="font-semibold text-base mb-3 text-primary">What will happen when you save:</h4>
                    <div class="text-sm space-y-1">
                        @foreach (explode("\n", $this->getSaveSummary()) as $line)
                            @if (trim($line))
                                <div class="flex items-start gap-2">
                                    @if (str_starts_with(trim($line), '•'))
                                        <span class="text-primary">•</span>
                                        <span>{{ trim(substr($line, 1)) }}</span>
                                    @else
                                        <span class="font-medium">{{ $line }}</span>
                                    @endif
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            @endif

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
