<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\CertificateController;
use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\Installment;
use App\Models\Student;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class DocumentApiController extends Controller
{
    public function installmentReceipt(Request $request, Installment $installment)
    {
        $student = $this->resolveAccessibleStudent($request, $installment->student);

        $pdf = Pdf::loadView('receipt.payment', $this->buildInstallmentReceiptData($student, $installment));

        return $pdf->download('receipt_installment_' . $installment->id . '.pdf');
    }

    public function downPaymentReceipt(Request $request, Student $student)
    {
        $student = $this->resolveAccessibleStudent($request, $student);

        if ((float) $student->down_payment <= 0) {
            abort(404, 'Down payment receipt not found.');
        }

        $pdf = Pdf::loadView('receipt.payment', $this->buildDownPaymentReceiptData($student));

        return $pdf->download('receipt_down_payment_' . $student->id . '.pdf');
    }

    public function certificateDownload(Request $request, Certificate $certificate)
    {
        $student = $this->resolveAccessibleStudent($request, $certificate->student);

        $regNo = str_replace('/', '_', $student->tiitvt_reg_no);

        return app(CertificateController::class)->download($regNo, $certificate->course_id);
    }

    private function resolveAccessibleStudent(Request $request, ?Student $targetStudent = null): Student
    {
        $actor = $request->user();

        if ($actor instanceof Student) {
            if ($targetStudent && $actor->id !== $targetStudent->id) {
                abort(404, 'Resource not found.');
            }

            $actor->loadMissing(['center:id,name,address,state,country', 'courses:id,name']);

            return $actor;
        }

        if ($actor instanceof User && $actor->hasRole('center')) {
            $centerId = $actor->center?->id;

            if (!$centerId) {
                abort(403, 'Center profile not configured.');
            }

            if (!$targetStudent || $targetStudent->center_id !== $centerId) {
                abort(404, 'Resource not found.');
            }

            $targetStudent->loadMissing(['center:id,name,address,state,country', 'courses:id,name']);

            return $targetStudent;
        }

        abort(403, 'Unauthorized.');
    }

    private function buildInstallmentReceiptData(Student $student, Installment $installment): array
    {
        $center = $student->center;
        $courses = $student->courses;

        $currentPaymentAmount = (float) ($installment->paid_amount ?? 0);
        $totalFees = (float) $student->course_fees;
        $downPayment = (float) ($student->down_payment ?? 0);

        $previousInstallmentsPaid = (float) $student->installments()
            ->where('id', '!=', $installment->id)
            ->sum('paid_amount');

        $totalPreviousPaid = $previousInstallmentsPaid + $downPayment;
        $totalPaidAfter = $totalPreviousPaid + $currentPaymentAmount;
        $balanceAmount = max(0, $totalFees - $totalPaidAfter);

        $centerAddress = trim(implode(', ', array_filter([
            $center?->address,
            $center?->state,
            $center?->country,
        ])));

        return [
            'student' => $student,
            'center' => $center,
            'courses' => $courses,
            'currentPaymentAmount' => $currentPaymentAmount,
            'totalFees' => $totalFees,
            'totalPreviousPaid' => $totalPreviousPaid,
            'totalPaidAfter' => $totalPaidAfter,
            'balanceAmount' => $balanceAmount,
            'receiptNumber' => 'RCP-' . date('Y') . '-' . str_pad((string) $installment->id, 6, '0', STR_PAD_LEFT),
            'centerAddress' => $centerAddress,
            'paymentMethod' => 'cash',
            'paymentType' => 'full',
            'amountInWords' => numberToWords($currentPaymentAmount),
            'studentName' => trim($student->first_name . ' ' . ($student->surname ?? '')),
            'studentTitle' => 'Mr./Ms./Mrs.',
            'websiteName' => getWebsiteName(),
            'paymentDate' => $installment->paid_date ?? now(),
            'chequeNumber' => null,
            'withdrawnDate' => null,
        ];
    }

    private function buildDownPaymentReceiptData(Student $student): array
    {
        $center = $student->center;
        $courses = $student->courses;

        $currentPaymentAmount = (float) $student->down_payment;
        $totalFees = (float) $student->course_fees;

        $paidInstallments = (float) $student->installments()->sum('paid_amount');
        $totalPreviousPaid = 0;
        $totalPaidAfter = $currentPaymentAmount + $paidInstallments;
        $balanceAmount = max(0, $totalFees - $totalPaidAfter);

        $centerAddress = trim(implode(', ', array_filter([
            $center?->address,
            $center?->state,
            $center?->country,
        ])));

        return [
            'student' => $student,
            'center' => $center,
            'courses' => $courses,
            'currentPaymentAmount' => $currentPaymentAmount,
            'totalFees' => $totalFees,
            'totalPreviousPaid' => $totalPreviousPaid,
            'totalPaidAfter' => $totalPaidAfter,
            'balanceAmount' => $balanceAmount,
            'receiptNumber' => 'RCP-DP-' . date('Y') . '-' . str_pad((string) $student->id, 6, '0', STR_PAD_LEFT),
            'centerAddress' => $centerAddress,
            'paymentMethod' => 'cash',
            'paymentType' => 'down payment',
            'amountInWords' => numberToWords($currentPaymentAmount),
            'studentName' => trim($student->first_name . ' ' . ($student->surname ?? '')),
            'studentTitle' => 'Mr./Ms./Mrs.',
            'websiteName' => getWebsiteName(),
            'paymentDate' => $student->enrollment_date ?? now(),
            'chequeNumber' => null,
            'withdrawnDate' => null,
        ];
    }
}
