<?php

namespace App\Services;

use App\Models\Certificate;
use App\Models\Student;
use App\Models\Course;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class CertificateService
{
    /**
     * Issue a certificate for a student.
     */
    public function issueCertificate(Student $student, Course $course): Certificate
    {
        $qrToken = $this->generateQrToken();
        $certificateNumber = $this->generateCertificateNumber();

        $certificate = Certificate::create([
            'student_id' => $student->id,
            'course_id' => $course->id,
            'issued_on' => now(),
            'qr_token' => $qrToken,
            'certificate_number' => $certificateNumber,
            'status' => 'active',
        ]);

        // Generate QR code
        $qrCodePath = $this->generateQrCode($qrToken, $certificate->id);

        // Generate PDF certificate
        $pdfPath = $this->generatePdfCertificate($certificate);

        $certificate->update([
            'qr_code_path' => $qrCodePath,
            'pdf_path' => $pdfPath,
        ]);

        return $certificate->load(['student', 'course']);
    }

    /**
     * Generate a unique QR token.
     */
    private function generateQrToken(): string
    {
        do {
            $token = Str::random(32);
        } while (Certificate::where('qr_token', $token)->exists());

        return $token;
    }

    /**
     * Generate a unique certificate number.
     */
    private function generateCertificateNumber(): string
    {
        $prefix = 'CERT';
        $year = date('Y');
        $sequence = Certificate::whereYear('created_at', $year)->count() + 1;

        return sprintf('%s-%s-%06d', $prefix, $year, $sequence);
    }

    /**
     * Generate QR code for certificate verification.
     */
    private function generateQrCode(string $token, int $certificateId): string
    {
        $verificationUrl = route('certificate.verify', $token);

        $qrCode = QrCode::format('png')
            ->size(300)
            ->margin(10)
            ->generate($verificationUrl);

        $filename = "certificates/qr_codes/certificate_{$certificateId}.png";
        Storage::disk('public')->put($filename, $qrCode);

        return $filename;
    }

    /**
     * Generate PDF certificate.
     */
    private function generatePdfCertificate(Certificate $certificate): string
    {
        // This would typically use a PDF library like DomPDF or Snappy
        // For now, we'll create a placeholder
        $filename = "certificates/pdfs/certificate_{$certificate->id}.pdf";

        // TODO: Implement actual PDF generation
        // $pdf = PDF::loadView('certificates.template', compact('certificate'));
        // Storage::disk('public')->put($filename, $pdf->output());

        return $filename;
    }

    /**
     * Verify certificate by QR token.
     */
    public function verifyCertificate(string $token): ?Certificate
    {
        return Certificate::where('qr_token', $token)
            ->where('status', 'active')
            ->with(['student', 'course'])
            ->first();
    }

    /**
     * Revoke a certificate.
     */
    public function revokeCertificate(Certificate $certificate): bool
    {
        return $certificate->update(['status' => 'revoked']);
    }

    /**
     * Get certificate statistics.
     */
    public function getCertificateStatistics(): array
    {
        return [
            'total_certificates' => Certificate::count(),
            'active_certificates' => Certificate::where('status', 'active')->count(),
            'revoked_certificates' => Certificate::where('status', 'revoked')->count(),
            'certificates_this_month' => Certificate::whereMonth('created_at', now()->month)->count(),
            'certificates_this_year' => Certificate::whereYear('created_at', now()->year)->count(),
        ];
    }

    /**
     * Get certificates for a student.
     */
    public function getStudentCertificates(Student $student): \Illuminate\Database\Eloquent\Collection
    {
        return $student->certificates()->with('course')->get();
    }

    /**
     * Get certificates for a course.
     */
    public function getCourseCertificates(Course $course): \Illuminate\Database\Eloquent\Collection
    {
        return $course->certificates()->with('student')->get();
    }
}
