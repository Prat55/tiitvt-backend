<?php

namespace App\Http\Controllers;

use App\Models\ExternalCertificate;
use App\Models\Installment;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class StudentController extends Controller
{
    /**
     * Display certificate result view by registration number
     */
    public function resultView($regNo)
    {
        // Convert _ back to / for database lookup
        $originalRegNo = str_replace('_', '/', $regNo);

        // Find the certificate by registration number
        $certificate = ExternalCertificate::with('center')
            ->where('reg_no', $originalRegNo)
            ->first();

        if (!$certificate) {
            abort(404, 'Certificate not found');
        }

        // Track page visit
        trackPageVisit('student_result', [
            'registration_number' => $originalRegNo,
            'certificate_id' => $certificate->id,
        ]);

        // Create a student-like object for compatibility with the view
        $student = (object) [
            'tiitvt_reg_no' => $certificate->reg_no,
            'full_name' => $certificate->student_name,
            'center' => $certificate->center,
        ];

        // Create exam results-like structure for compatibility
        $examResults = collect([
            (object) [
                'id' => $certificate->id,
                'percentage' => $certificate->percentage,
                'grade' => $certificate->grade,
                'course_name' => $certificate->course_name,
                'issued_on' => $certificate->issued_on,
                'created_at' => $certificate->created_at,
            ]
        ]);

        $latestResult = $examResults->first();
        $totalExams = $examResults->count();
        $averagePercentage = $examResults->avg('percentage');
        $passedExams = $examResults->where('percentage', '>=', 50)->count();

        return view('student.result-view', compact(
            'student',
            'examResults',
            'latestResult',
            'totalExams',
            'averagePercentage',
            'passedExams',
            'certificate'
        ));
    }

    /**
     * Display payment receipt for an installment or down payment
     *
     * @param string $type - 'installment' or 'down-payment'
     * @param int $id - Installment ID or Student ID
     */
    public function receipt($type, $id)
    {
        if ($type === 'down-payment') {
            // Handle down payment receipt
            $student = Student::with(['center', 'courses', 'installments'])
                ->findOrFail($id);

            // Check if center user can access this student's receipt
            if (!canAccessStudent($student)) {
                abort(404, 'Receipt not found');
            }

            // Explicit check: If authenticated user is a center, verify center_id matches
            if (hasAuthRole('center')) {
                $userCenterId = getUserCenterId();

                if ($userCenterId !== null && $student->center_id !== $userCenterId) {
                    abort(404, 'Receipt not found');
                }
            }

            // Check if student has down payment
            if (!$student->down_payment || $student->down_payment <= 0) {
                abort(404, 'No down payment found for this student');
            }

            $center = $student->center;
            $course = $student->course;

            // Calculate payment details
            $currentPaymentAmount = $student->down_payment;
            $totalFees = $student->course_fees;

            // Calculate total paid from installments
            $totalPaidFromInstallments = $student->installments->sum('paid_amount');

            // Previous paid is 0 for down payment (it's the first payment)
            $totalPreviousPaid = 0;

            // Total paid after including down payment
            $totalPaidAfter = $currentPaymentAmount + $totalPaidFromInstallments;
            $balanceAmount = max(0, $totalFees - $totalPaidAfter);

            // Generate receipt number
            $receiptNumber = 'RCP-DP-' . date('Y') . '-' . str_pad($student->id, 6, '0', STR_PAD_LEFT);

            // Format address
            $centerAddress = trim(implode(', ', array_filter([
                $center->address,
                $center->state,
                $center->country
            ])));

            // Payment method and details (down payment doesn't store payment method, default to cash)
            $paymentMethod = 'cash';
            $paymentType = 'down payment';
            $chequeNumber = null;
            $withdrawnDate = null;

            // Amount in words
            $amountInWords = numberToWords($currentPaymentAmount);

            // Student name with title
            $studentName = $student->first_name . ' ' . $student->surname;
            $studentTitle = 'Mr./Ms./Mrs.';

            // Get website name
            $websiteName = getWebsiteName();

            // Payment date (use enrollment date if available, otherwise use current date)
            $paymentDate = $student->enrollment_date ?? now();

            return view('receipt.payment', compact(
                'student',
                'center',
                'course',
                'currentPaymentAmount',
                'totalFees',
                'totalPreviousPaid',
                'totalPaidAfter',
                'balanceAmount',
                'receiptNumber',
                'centerAddress',
                'paymentMethod',
                'paymentType',
                'amountInWords',
                'studentName',
                'studentTitle',
                'websiteName',
                'paymentDate',
                'chequeNumber',
                'withdrawnDate'
            ));
        } else {
            // Handle installment receipt
            $installment = Installment::with(['student.center', 'student.courses'])
                ->findOrFail($id);

            $student = $installment->student;

            // Check if center user can access this student's receipt
            if (!canAccessStudent($student)) {
                abort(404, 'Receipt not found');
            }

            // Explicit check: If authenticated user is a center, verify center_id matches
            if (hasAuthRole('center')) {
                $userCenterId = getUserCenterId();

                if ($userCenterId !== null && $student->center_id !== $userCenterId) {
                    abort(404, 'Receipt not found');
                }
            }

            $center = $student->center;
            $course = $student->course;

            // Calculate payment details
            $currentPaymentAmount = $installment->paid_amount ?? 0;
            // Use course_fees as total fees (show full course fees, not just installments sum)
            $totalFees = $student->course_fees;

            // Down payment
            $downPayment = $student->down_payment ?? 0;

            // Calculate previous paid (from other installments only, excluding current installment)
            $totalPreviousPaid = $student->installments
                ->where('id', '!=', $installment->id)
                ->sum('paid_amount');

            // Add down payment to previous paid
            $totalPreviousPaidWithDown = $totalPreviousPaid + $downPayment;

            // Total paid after including this installment
            $totalPaidAfter = $totalPreviousPaidWithDown + $currentPaymentAmount;
            $balanceAmount = max(0, $totalFees - $totalPaidAfter);

            // Generate receipt number
            $receiptNumber = 'RCP-' . date('Y') . '-' . str_pad($installment->id, 6, '0', STR_PAD_LEFT);

            // Format address
            $centerAddress = trim(implode(', ', array_filter([
                $center->address,
                $center->state,
                $center->country
            ])));

            // Payment method and details
            $paymentMethod = $installment->payment_method?->value ?? 'cash';
            $paymentType = $installment->status->isPaid() ? 'full' : ($installment->status->isPartial() ? 'part' : 'full');

            // Amount in words
            $amountInWords = numberToWords($currentPaymentAmount);

            // Student name with title
            $studentName = $student->first_name . ' ' . $student->surname;
            $studentTitle = 'Mr./Ms./Mrs.';

            // Get website name
            $websiteName = getWebsiteName();

            // Payment date
            $paymentDate = $installment->paid_date ?? now();
            $chequeNumber = $installment->cheque_number;
            $withdrawnDate = $installment->withdrawn_date;

            return view('receipt.payment', compact(
                'installment',
                'student',
                'center',
                'course',
                'currentPaymentAmount',
                'totalFees',
                'totalPreviousPaid',
                'totalPaidAfter',
                'balanceAmount',
                'receiptNumber',
                'centerAddress',
                'paymentMethod',
                'paymentType',
                'amountInWords',
                'studentName',
                'studentTitle',
                'websiteName',
                'paymentDate',
                'chequeNumber',
                'withdrawnDate'
            ));
        }
    }
}
