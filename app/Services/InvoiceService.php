<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Student;
use Illuminate\Support\Facades\DB;

class InvoiceService
{
    /**
     * Create a new invoice for a student.
     */
    public function createInvoice(Student $student, array $data): Invoice
    {
        return Invoice::create([
            'student_id' => $student->id,
            'amount' => $data['amount'],
            'status' => 'unpaid',
            'invoice_number' => $this->generateInvoiceNumber(),
            'description' => $data['description'] ?? null,
        ]);
    }

    /**
     * Generate a unique invoice number.
     */
    private function generateInvoiceNumber(): string
    {
        $prefix = 'INV';
        $year = date('Y');
        $month = date('m');
        $sequence = Invoice::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->count() + 1;

        return sprintf('%s-%s%s-%06d', $prefix, $year, $month, $sequence);
    }

    /**
     * Mark invoice as paid.
     */
    public function markAsPaid(Invoice $invoice): bool
    {
        return $invoice->update([
            'status' => 'paid',
            'paid_at' => now(),
        ]);
    }

    /**
     * Mark invoice as unpaid.
     */
    public function markAsUnpaid(Invoice $invoice): bool
    {
        return $invoice->update([
            'status' => 'unpaid',
            'paid_at' => null,
        ]);
    }

    /**
     * Get invoices for a student.
     */
    public function getStudentInvoices(Student $student): \Illuminate\Database\Eloquent\Collection
    {
        return $student->invoices()->orderBy('created_at', 'desc')->get();
    }

    /**
     * Get unpaid invoices for a student.
     */
    public function getStudentUnpaidInvoices(Student $student): \Illuminate\Database\Eloquent\Collection
    {
        return $student->invoices()->unpaid()->get();
    }

    /**
     * Get all unpaid invoices.
     */
    public function getAllUnpaidInvoices(): \Illuminate\Database\Eloquent\Collection
    {
        return Invoice::unpaid()->with('student')->get();
    }

    /**
     * Get invoice statistics.
     */
    public function getInvoiceStatistics(): array
    {
        $totalInvoices = Invoice::count();
        $paidInvoices = Invoice::paid()->count();
        $unpaidInvoices = Invoice::unpaid()->count();

        $totalAmount = Invoice::sum('amount');
        $paidAmount = Invoice::paid()->sum('amount');
        $unpaidAmount = Invoice::unpaid()->sum('amount');

        return [
            'total_invoices' => $totalInvoices,
            'paid_invoices' => $paidInvoices,
            'unpaid_invoices' => $unpaidInvoices,
            'total_amount' => $totalAmount,
            'paid_amount' => $paidAmount,
            'unpaid_amount' => $unpaidAmount,
            'payment_rate' => $totalInvoices > 0 ? ($paidInvoices / $totalInvoices) * 100 : 0,
        ];
    }

    /**
     * Get monthly invoice statistics.
     */
    public function getMonthlyInvoiceStatistics(int $year = null): array
    {
        $year = $year ?? date('Y');

        $monthlyData = Invoice::whereYear('created_at', $year)
            ->selectRaw('MONTH(created_at) as month, COUNT(*) as count, SUM(amount) as total_amount')
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $statistics = [];
        for ($month = 1; $month <= 12; $month++) {
            $monthData = $monthlyData->where('month', $month)->first();
            $statistics[$month] = [
                'count' => $monthData ? $monthData->count : 0,
                'total_amount' => $monthData ? $monthData->total_amount : 0,
            ];
        }

        return $statistics;
    }

    /**
     * Bulk create invoices for multiple students.
     */
    public function bulkCreateInvoices(array $students, array $invoiceData): array
    {
        $createdInvoices = [];

        DB::transaction(function () use ($students, $invoiceData, &$createdInvoices) {
            foreach ($students as $studentId) {
                $student = Student::find($studentId);
                if ($student) {
                    $invoice = $this->createInvoice($student, $invoiceData);
                    $createdInvoices[] = $invoice;
                }
            }
        });

        return $createdInvoices;
    }

    /**
     * Send payment reminder for unpaid invoices.
     */
    public function sendPaymentReminders(): int
    {
        $unpaidInvoices = Invoice::unpaid()
            ->with('student')
            ->where('created_at', '<=', now()->subDays(7))
            ->get();

        $sentCount = 0;
        foreach ($unpaidInvoices as $invoice) {
            // TODO: Implement actual email/notification sending
            // Mail::to($invoice->student->user->email)->send(new PaymentReminder($invoice));
            $sentCount++;
        }

        return $sentCount;
    }
}
