<?php

namespace App\Services;

use App\Models\Student;
use App\Models\StudentQR;
use App\Services\WebsiteSettingsService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Label\Font\OpenSans;
use Endroid\QrCode\Label\LabelAlignment;
use Illuminate\Support\Facades\Log;

class StudentQRService
{
    protected WebsiteSettingsService $websiteSettings;

    public function __construct(?WebsiteSettingsService $websiteSettings = null)
    {
        $this->websiteSettings = $websiteSettings ?: app(WebsiteSettingsService::class);
    }

    /**
     * Get QR logo path from website settings or fallback to default.
     */
    public function getQrLogoPath(): string
    {
        // Get the settings directly to get the raw path, not the URL
        $settings = $this->websiteSettings->getSettings();

        if ($settings && $settings->qr_code_image) {
            $logoPath = Storage::disk('public')->path($settings->qr_code_image);

            // Debug logging
            Log::info('QR Logo Debug', [
                'settings_qr_code_image' => $settings->qr_code_image,
                'full_logo_path' => $logoPath,
                'file_exists' => file_exists($logoPath),
                'is_readable' => is_readable($logoPath),
                'file_size' => file_exists($logoPath) ? filesize($logoPath) : 'N/A'
            ]);

            return $logoPath;
        }

        // Fallback to default logo
        $defaultPath = public_path('default/qr_logo.png');
        Log::info('QR Logo Debug - Using default', [
            'default_path' => $defaultPath,
            'file_exists' => file_exists($defaultPath)
        ]);

        return $defaultPath;
    }

    /**
     * Generate QR code for a student.
     */
    public function generateStudentQR(Student $student): StudentQR
    {
        // Check if student already has a QR code
        $existingQR = $student->qrCode;
        if ($existingQR) {
            return $existingQR;
        }

        $qrToken = $this->generateQrToken();

        // Generate QR data with the actual token
        $qrData = $this->generateQrDataWithToken($student, $qrToken);

        $studentQR = StudentQR::create([
            'student_id' => $student->id,
            'qr_token' => $qrToken,
            'qr_data' => $qrData,
            'is_active' => true,
        ]);

        // Generate QR code with logo
        $qrCodePath = $this->generateQrCodeWithLogo($qrData, $studentQR->id);

        $studentQR->update([
            'qr_code_path' => $qrCodePath,
        ]);

        return $studentQR;
    }

    /**
     * Generate a unique QR token.
     */
    private function generateQrToken(): string
    {
        do {
            $token = Str::random(32);
        } while (StudentQR::where('qr_token', $token)->exists());

        return $token;
    }

    /**
     * Generate QR data for the student.
     */
    private function generateQrData(Student $student): string
    {
        // Generate just the verification URL placeholder
        return route('student.qr.verify', 'TOKEN_PLACEHOLDER');
    }

    /**
     * Generate QR data for the student with actual token.
     */
    public function generateQrDataWithToken(Student $student, string $token): string
    {
        // Generate just the verification URL instead of JSON data
        return route('student.qr.verify', $token);
    }

    /**
     * Generate QR code with logo in the center.
     */
    public function generateQrCodeWithLogo(string $data, int $studentQRId): string
    {
        $logoPath = $this->getQrLogoPath();

        // Create QR code using the Builder pattern with logo
        $builder = Builder::create()
            ->writer(new PngWriter())
            ->writerOptions([])
            ->data($data)
            ->encoding(new Encoding('UTF-8'))
            ->errorCorrectionLevel(ErrorCorrectionLevel::High)
            ->size(400)
            ->margin(10)
            ->roundBlockSizeMode(RoundBlockSizeMode::Margin)
            ->foregroundColor(new Color(0, 0, 0))
            ->backgroundColor(new Color(255, 255, 255));

        // Add logo if it exists
        if (file_exists($logoPath)) {
            $builder->logoPath($logoPath)
                ->logoResizeToWidth(90)
                ->logoPunchoutBackground(true);
        }

        $result = $builder->build();

        // Ensure directory exists and save file using Storage
        $filename = "students/qr_codes/student_{$studentQRId}.png";

        // Ensure the directory exists
        Storage::disk('public')->makeDirectory(dirname($filename));

        // Save the QR code data to storage
        Storage::disk('public')->put($filename, $result->getString());

        return $filename;
    }

    /**
     * Generate QR code with logo and label for enhanced display.
     */
    public function generateEnhancedQRCode(Student $student, ?string $logoPath = null, ?string $labelText = null): string
    {
        $qrData = $this->generateQrDataWithToken($student, $student->qrCode->qr_token ?? $this->generateQrToken());

        $builder = Builder::create()
            ->writer(new PngWriter())
            ->writerOptions([])
            ->data($qrData)
            ->encoding(new Encoding('UTF-8'))
            ->errorCorrectionLevel(ErrorCorrectionLevel::High)
            ->size(500)
            ->margin(15)
            ->roundBlockSizeMode(RoundBlockSizeMode::Margin)
            ->foregroundColor(new Color(0, 0, 0))
            ->backgroundColor(new Color(255, 255, 255));

        // Use QR logo from website settings if no logo path provided
        if (!$logoPath) {
            $logoPath = $this->getQrLogoPath();
        }

        // Add logo if it exists
        if (file_exists($logoPath)) {
            $builder->logoPath($logoPath)
                ->logoResizeToWidth(90)
                ->logoPunchoutBackground(true);
        }

        // Add label if provided
        if ($labelText) {
            $builder->labelText($labelText)
                ->labelFont(new OpenSans(16))
                ->labelAlignment(LabelAlignment::Center);
        }

        $result = $builder->build();

        // Ensure directory exists and save file using Storage
        $filename = "students/qr_codes/enhanced_student_{$student->id}.png";

        // Ensure the directory exists
        Storage::disk('public')->makeDirectory(dirname($filename));

        // Save the QR code data to storage
        Storage::disk('public')->put($filename, $result->getString());

        return $filename;
    }

    /**
     * Generate QR code data URI for inline display.
     */
    public function generateQRCodeDataUri(string $data): string
    {
        $logoPath = $this->getQrLogoPath();

        $builder = Builder::create()
            ->writer(new PngWriter())
            ->writerOptions([])
            ->data($data)
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
                ->logoResizeToWidth(90)
                ->logoPunchoutBackground(true);
        }

        $result = $builder->build();

        return $result->getDataUri();
    }

    /**
     * Verify student QR code.
     */
    public function verifyStudentQR(string $token): ?StudentQR
    {
        return StudentQR::where('qr_token', $token)
            ->where('is_active', true)
            ->with('student.center', 'student.courses')
            ->first();
    }

    /**
     * Get student QR code by student ID.
     */
    public function getStudentQR(Student $student): ?StudentQR
    {
        return $student->qrCode;
    }

    /**
     * Regenerate QR code for a student.
     */
    public function regenerateStudentQR(Student $student): StudentQR
    {
        // Delete existing QR code
        $existingQR = $student->qrCode;
        if ($existingQR) {
            if ($existingQR->qr_code_path) {
                Storage::disk('public')->delete($existingQR->qr_code_path);
            }
            $existingQR->delete();
        }

        // Generate new QR code
        return $this->generateStudentQR($student);
    }

    /**
     * Regenerate QR code with fresh logo from website settings.
     */
    public function regenerateStudentQRWithFreshLogo(Student $student): StudentQR
    {
        // Get existing QR code or create new one
        $existingQR = $student->qrCode;
        if (!$existingQR) {
            return $this->generateStudentQR($student);
        }

        // Delete existing QR code file
        if ($existingQR->qr_code_path) {
            Storage::disk('public')->delete($existingQR->qr_code_path);
        }

        // Generate new QR code with current website logo
        $qrData = $this->generateQrDataWithToken($student, $existingQR->qr_token);
        $qrCodePath = $this->generateQrCodeWithLogo($qrData, $existingQR->id);

        $existingQR->update([
            'qr_code_path' => $qrCodePath,
        ]);

        return $existingQR;
    }

    /**
     * Bulk regenerate QR codes for all students with fresh logo.
     */
    public function regenerateAllStudentQRsWithFreshLogo(): array
    {
        $results = [];
        $students = Student::with('qrCode')->whereHas('qrCode')->get();

        foreach ($students as $student) {
            try {
                $results[$student->id] = $this->regenerateStudentQRWithFreshLogo($student);
            } catch (\Exception $e) {
                $results[$student->id] = ['error' => $e->getMessage()];
            }
        }

        return $results;
    }

    /**
     * Deactivate student QR code.
     */
    public function deactivateStudentQR(Student $student): bool
    {
        $qrCode = $student->qrCode;
        if ($qrCode) {
            return $qrCode->update(['is_active' => false]);
        }
        return false;
    }

    /**
     * Get QR code statistics.
     */
    public function getQRStatistics(): array
    {
        return [
            'total_qr_codes' => StudentQR::count(),
            'active_qr_codes' => StudentQR::where('is_active', true)->count(),
            'inactive_qr_codes' => StudentQR::where('is_active', false)->count(),
            'qr_codes_this_month' => StudentQR::whereMonth('created_at', now()->month)->count(),
            'qr_codes_this_year' => StudentQR::whereYear('created_at', now()->year)->count(),
        ];
    }
}
