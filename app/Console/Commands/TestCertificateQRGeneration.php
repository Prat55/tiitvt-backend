<?php

namespace App\Console\Commands;

use App\Services\CertificateService;
use App\Services\WebsiteSettingsService;
use App\Models\ExternalCertificate;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class TestCertificateQRGeneration extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'certificate:test-qr {--certificate= : Test QR generation for specific certificate ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test certificate QR code generation and logo detection';

    protected CertificateService $certificateService;
    protected WebsiteSettingsService $settingsService;

    public function __construct(CertificateService $certificateService, WebsiteSettingsService $settingsService)
    {
        parent::__construct();
        $this->certificateService = $certificateService;
        $this->settingsService = $settingsService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing Certificate QR Code Generation and Logo Detection...');
        $this->newLine();

        // Test 1: Check website settings
        $this->info('1. Checking Website Settings:');
        $settings = $this->settingsService->getSettings();

        if ($settings) {
            $this->line("   ✓ Website settings found");
            $this->line("   QR Code Image: " . ($settings->qr_code_image ?: 'Not set'));

            if ($settings->qr_code_image) {
                $fullPath = Storage::disk('public')->path($settings->qr_code_image);
                $this->line("   Full Path: {$fullPath}");
                $this->line("   File Exists: " . (file_exists($fullPath) ? 'Yes' : 'No'));
                $this->line("   File Size: " . (file_exists($fullPath) ? filesize($fullPath) . ' bytes' : 'N/A'));
            }
        } else {
            $this->error("   ✗ No website settings found");
        }
        $this->newLine();

        // Test 2: Check logo path method
        $this->info('2. Testing CertificateService getQrLogoPath() method:');
        $reflection = new \ReflectionClass($this->certificateService);
        $method = $reflection->getMethod('getQrLogoPath');
        $method->setAccessible(true);
        $logoPath = $method->invoke($this->certificateService);

        $this->line("   Logo Path: {$logoPath}");
        $this->line("   File Exists: " . (file_exists($logoPath) ? 'Yes' : 'No'));
        $this->line("   Is Readable: " . (is_readable($logoPath) ? 'Yes' : 'No'));
        $this->newLine();

        // Test 3: Generate test QR code
        $this->info('3. Testing Certificate QR Code Generation:');

        if ($certificateId = $this->option('certificate')) {
            $certificate = ExternalCertificate::find($certificateId);
            if (!$certificate) {
                $this->error("   ✗ Certificate with ID {$certificateId} not found");
                return 1;
            }

            $this->line("   Testing with Certificate: {$certificate->student_name} (ID: {$certificate->id})");

            // Generate QR code with logo
            $this->line("   Generating QR code with logo...");
            $qrPath = $this->certificateService->generateCertificateQRCodeWithLogo($certificate->qr_token, $certificate->id);

            $this->line("   ✓ QR Code generated successfully");
            $this->line("   QR Code Path: {$qrPath}");
            $this->line("   QR Code URL: " . Storage::url($qrPath));

            // Update the certificate with the new QR path
            $certificate->update(['qr_code_path' => $qrPath]);
            $this->line("   ✓ Certificate updated with new QR code path");
        } else {
            $this->line("   No certificate ID provided. Use --certificate=ID to test with specific certificate.");
            $this->line("   Available certificates:");

            $certificates = ExternalCertificate::latest()->take(5)->get(['id', 'student_name', 'course_name']);
            foreach ($certificates as $cert) {
                $this->line("     - ID: {$cert->id}, Student: {$cert->student_name}, Course: {$cert->course_name}");
            }
        }
        $this->newLine();

        // Test 4: Check logs
        $this->info('4. Recent Certificate QR Logo Debug Logs:');
        $logFile = storage_path('logs/laravel.log');
        if (file_exists($logFile)) {
            $logs = shell_exec("tail -20 {$logFile} | grep 'Certificate QR Logo Debug'");
            if ($logs) {
                $this->line($logs);
            } else {
                $this->line("   No recent certificate QR logo debug logs found.");
            }
        } else {
            $this->line("   Log file not found.");
        }

        $this->newLine();
        $this->info('Test completed! Check the logs above for any issues.');

        return 0;
    }
}
