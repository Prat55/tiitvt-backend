<?php

namespace App\Http\Controllers;

use App\Models\ExternalCertificate;
use App\Models\Installment;
use Illuminate\Http\Request;

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
     * Display payment receipt for an installment
     */
    public function receipt($installmentId)
    {
        $installment = Installment::with(['student.center', 'student.courses'])
            ->findOrFail($installmentId);

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
        $course = $student->course; // Use the accessor which returns the first course (may be null)

        // Calculate payment details
        $currentPaymentAmount = $installment->paid_amount ?? 0;
        $totalFees = $student->installments->sum('amount');

        // Calculate previous paid (from other installments only, excluding current installment)
        $totalPreviousPaid = $student->installments
            ->where('id', '!=', $installment->id)
            ->sum('paid_amount');

        // Total paid after including this installment
        $totalPaidAfter = $totalPreviousPaid + $currentPaymentAmount;
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
            'websiteName'
        ));
    }
}
