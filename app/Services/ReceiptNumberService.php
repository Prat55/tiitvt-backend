<?php

namespace App\Services;

use App\Enums\InstallmentStatusEnum;
use App\Models\Installment;
use App\Models\Student;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ReceiptNumberService
{
    public function forDownPayment(Student $student, ?CarbonInterface $paymentDate = null): string
    {
        $receiptDate = $paymentDate ?? $student->created_at ?? $student->enrollment_date ?? now();
        $year = (int) $receiptDate->year;

        if (empty($student->center_id)) {
            return $this->formatReceiptNumber($year, (int) $student->id);
        }

        $dateString = $receiptDate->toDateString();
        $centerId = (int) $student->center_id;

        $earlierDownPayments = $this->applyReceiptDateFilter(
            $this->receiptedDownPaymentsQuery($centerId),
            'created_at',
            $year,
            '<',
            $dateString,
        )->count();

        $earlierInstallments = $this->applyReceiptDateFilter(
            $this->receiptedInstallmentsQuery($centerId),
            'COALESCE(paid_date, created_at)',
            $year,
            '<',
            $dateString,
        )->count();

        $sameDayDownPayments = $this->applyReceiptDateFilter(
            Student::query()
                ->where('center_id', $centerId)
                ->where(function (Builder $query) use ($student) {
                    $query->where(function (Builder $downPaymentQuery) {
                        $this->applyReceiptedDownPaymentFilter($downPaymentQuery);
                    })->orWhere('id', $student->id);
                })
                ->where('id', '<=', $student->id),
            'created_at',
            $year,
            '=',
            $dateString,
        )->count();

        $sequence = $earlierDownPayments + $earlierInstallments + $sameDayDownPayments;

        return $this->formatReceiptNumber($year, $sequence);
    }

    public function forInstallment(Installment $installment, ?CarbonInterface $paymentDate = null): string
    {
        $receiptDate = $paymentDate ?? $installment->paid_date ?? $installment->created_at ?? now();
        $year = (int) $receiptDate->year;
        $student = $installment->student;

        if (!$student || empty($student->center_id)) {
            return $this->formatReceiptNumber($year, (int) $installment->id);
        }

        $dateString = $receiptDate->toDateString();
        $centerId = (int) $student->center_id;

        $earlierDownPayments = $this->applyReceiptDateFilter(
            $this->receiptedDownPaymentsQuery($centerId),
            'created_at',
            $year,
            '<',
            $dateString,
        )->count();

        $earlierInstallments = $this->applyReceiptDateFilter(
            $this->receiptedInstallmentsQuery($centerId),
            'COALESCE(paid_date, created_at)',
            $year,
            '<',
            $dateString,
        )->count();

        $sameDayDownPayments = $this->applyReceiptDateFilter(
            $this->receiptedDownPaymentsQuery($centerId),
            'created_at',
            $year,
            '=',
            $dateString,
        )->count();

        $sameDayInstallments = $this->applyReceiptDateFilter(
            Installment::query()
                ->whereHas('student', function (Builder $query) use ($centerId) {
                    $query->where('center_id', $centerId);
                })
                ->where(function (Builder $query) use ($installment) {
                    $query->where(function (Builder $installmentQuery) {
                        $this->applyReceiptedInstallmentFilter($installmentQuery);
                    })->orWhere('id', $installment->id);
                })
                ->where('id', '<=', $installment->id),
            'COALESCE(paid_date, created_at)',
            $year,
            '=',
            $dateString,
        )->count();

        $sequence = $earlierDownPayments + $earlierInstallments + $sameDayDownPayments + $sameDayInstallments;

        return $this->formatReceiptNumber($year, $sequence);
    }

    private function receiptedDownPaymentsQuery(int $centerId): Builder
    {
        return $this->applyReceiptedDownPaymentFilter(
            Student::query()->where('center_id', $centerId)
        );
    }

    private function receiptedInstallmentsQuery(int $centerId): Builder
    {
        return $this->applyReceiptedInstallmentFilter(
            Installment::query()->whereHas('student', function (Builder $query) use ($centerId) {
                $query->where('center_id', $centerId);
            })
        );
    }

    private function applyReceiptedDownPaymentFilter(Builder $query): Builder
    {
        return $query
            ->whereNotNull('down_payment')
            ->where('down_payment', '>', 0);
    }

    private function applyReceiptedInstallmentFilter(Builder $query): Builder
    {
        return $query
            ->where('status', InstallmentStatusEnum::Paid->value)
            ->whereNotNull('paid_amount')
            ->where('paid_amount', '>', 0);
    }

    private function applyReceiptDateFilter(Builder $query, string $dateExpression, int $year, string $operator, string $date): Builder
    {
        return $query
            ->whereYear(DB::raw($dateExpression), $year)
            ->whereDate(DB::raw($dateExpression), $operator, $date);
    }

    private function formatReceiptNumber(int $year, int $sequence): string
    {
        return 'RCP-' . $year . '-' . str_pad((string) max(1, $sequence), 6, '0', STR_PAD_LEFT);
    }
}
