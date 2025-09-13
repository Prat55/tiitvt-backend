<?php

namespace App\Services;

use App\Models\Certificate;
use App\Models\Student;
use App\Models\Course;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Color\Color;

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
        $logoPath = public_path('default/qr_logo.png');

        $builder = Builder::create()
            ->writer(new PngWriter())
            ->writerOptions([])
            ->data($verificationUrl)
            ->encoding(new Encoding('UTF-8'))
            ->errorCorrectionLevel(ErrorCorrectionLevel::High)
            ->size(300)
            ->margin(10)
            ->roundBlockSizeMode(RoundBlockSizeMode::Margin)
            ->foregroundColor(new Color(0, 0, 0))
            ->backgroundColor(new Color(255, 255, 255));

        // Add logo if it exists
        if (file_exists($logoPath)) {
            $builder->logoPath($logoPath)
                ->logoResizeToWidth(50)
                ->logoPunchoutBackground(true);
        }

        $result = $builder->build();

        $filename = "certificates/qr_codes/certificate_{$certificateId}.png";
        $result->saveToFile(Storage::disk('public')->path($filename));

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

    /**
     * Generate enhanced QR code for certificate with logo and label.
     */
    public function generateEnhancedCertificateQRCode(Certificate $certificate, ?string $logoPath = null, ?string $labelText = null): string
    {
        $verificationUrl = route('certificate.verify', $certificate->qr_token);

        $builder = Builder::create()
            ->writer(new PngWriter())
            ->writerOptions([])
            ->data($verificationUrl)
            ->encoding(new Encoding('UTF-8'))
            ->errorCorrectionLevel(ErrorCorrectionLevel::High)
            ->size(400)
            ->margin(15)
            ->roundBlockSizeMode(RoundBlockSizeMode::Margin)
            ->foregroundColor(new Color(0, 0, 0))
            ->backgroundColor(new Color(255, 255, 255));

        // Add logo if provided
        if ($logoPath && file_exists($logoPath)) {
            $builder->logoPath($logoPath)
                ->logoResizeToWidth(60)
                ->logoPunchoutBackground(true);
        }

        // Add label if provided
        if ($labelText) {
            $builder->labelText($labelText)
                ->labelFont(new \Endroid\QrCode\Label\Font\OpenSans(14))
                ->labelAlignment(\Endroid\QrCode\Label\LabelAlignment::Center);
        }

        $result = $builder->build();

        // Save the enhanced QR code
        $filename = "certificates/qr_codes/enhanced_certificate_{$certificate->id}.png";
        $result->saveToFile(Storage::disk('public')->path($filename));

        return $filename;
    }

    /**
     * Generate QR code data URI for inline display.
     */
    public function generateCertificateQRCodeDataUri(string $token): string
    {
        $verificationUrl = route('certificate.verify', $token);

        $result = Builder::create()
            ->writer(new PngWriter())
            ->writerOptions([])
            ->data($verificationUrl)
            ->encoding(new Encoding('UTF-8'))
            ->errorCorrectionLevel(ErrorCorrectionLevel::High)
            ->size(250)
            ->margin(8)
            ->roundBlockSizeMode(RoundBlockSizeMode::Margin)
            ->foregroundColor(new Color(0, 0, 0))
            ->backgroundColor(new Color(255, 255, 255))
            ->build();

        return $result->getDataUri();
    }
}
