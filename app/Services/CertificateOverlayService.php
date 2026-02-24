<?php

namespace App\Services;

use setasign\Fpdi\Fpdi;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class CertificateOverlayService
{
    /**
     * Generate a PDF certificate by overlaying data on an existing template.
     *
     * @param object $certificate Data object containing student and exam info
     * @param string|null $qrDataUri Base64 encoded QR code data URI
     * @return string Serialized PDF content
     */
    public function generate($certificate, $qrDataUri = null)
    {
        $pdf = new Fpdi('L', 'mm', 'A4'); // Initialized with a default, will be overridden by template size

        // Template path
        $templatePath = public_path('default/certificate/merit_certificate_with_sign.pdf');

        if (!file_exists($templatePath)) {
            Log::error("Certificate template not found: " . $templatePath);
            throw new \Exception("Certificate template file not found.");
        }

        // Set the source file
        $pdf->setSourceFile($templatePath);

        // Import page 1
        $tplId = $pdf->importPage(1);
        $size = $pdf->getTemplateSize($tplId);
        $pageWidth = $size['width'];
        $pageHeight = $size['height'];
        $orientation = $size['orientation'];

        Log::info("Certificate Template Size: Orientation: {$orientation}, Width: {$pageWidth}, Height: {$pageHeight}");

        // Add a page matching the template
        $pdf->AddPage($orientation, array($pageWidth, $pageHeight));

        // Use the imported page as a template (1:1 scale)
        $pdf->useTemplate($tplId, 0, 0, $pageWidth, $pageHeight);

        // Set font
        $pdf->SetFont('Arial', 'B', 10); // Reduced from 14 to 12
        $pdf->SetTextColor(0, 0, 0);

        // --- Placement Logic ---
        // Coordinates are in mm (A4 Landscape: 297mm x 210mm)

        // 1. Registration Number (Top Left area)
        $pdf->SetY(10);
        $pdf->SetX(35);
        $pdf->Cell(0, 10, $certificate->reg_no, 0, 0, 'L');

        // 2. Date 
        $pdf->SetY(17);
        $pdf->SetX(35);
        $date = is_string($certificate->issued_on) ? $certificate->issued_on : $certificate->issued_on->format('d/m/Y');
        $pdf->Cell(0, 10, $date, 0, 0, 'L');

        // 3. Student Name (Center area)
        $pdf->SetY(102);
        $pdf->SetX(0);
        $pdf->SetFont('Arial', 'B', 12); // Reduced from 16 to 12
        $pdf->Cell($pageWidth, 10, $certificate->student_name, 0, 0, 'C');

        // 4. Course Name (Below Name)
        $pdf->SetY(120);
        $pdf->SetX(0);
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell($pageWidth, 10, $certificate->course_name, 0, 0, 'C');

        // 5. Percentage (Bottom area)
        $pdf->SetY(128);
        $pdf->SetX(60);
        $pdf->SetFont('Arial', 'B', 12); // Reduced from 14
        $pdf->Cell(0, 10, number_format($certificate->percentage, 2), 0, 0, 'L');

        // 6. Grade 
        $pdf->SetX(170);
        $pdf->Cell(0, 10, $certificate->grade, 0, 0, 'L');

        // 7. Center Name 
        $pdf->SetY(145);
        $pdf->SetX(0);
        $pdf->Cell($pageWidth, 10, $certificate->center_name, 0, 0, 'C');

        // 8. QR Code
        if ($qrDataUri) {
            try {
                // Extract base64 content
                if (preg_match('/^data:image\/(\w+);base64,/', $qrDataUri, $type)) {
                    $data = substr($qrDataUri, strpos($qrDataUri, ',') + 1);
                    $data = base64_decode($data);

                    // FPDF/FPDI doesn't directly support base64, so we need a temp file or use a memory stream
                    $tempQrPath = tempnam(sys_get_temp_dir(), 'qr_');
                    rename($tempQrPath, $tempQrPath . '.png');
                    $tempQrPath .= '.png';
                    file_put_contents($tempQrPath, $data);

                    $pdf->Image($tempQrPath, 170, 7, 28, 28); // Adjusted for Portrait width (210mm)

                    // Cleanup
                    unlink($tempQrPath);
                }
            } catch (\Exception $e) {
                Log::warning("Failed to add QR code to PDF: " . $e->getMessage());
            }
        }

        // 9. Subjects Table
        $startY = 163;
        $rowHeight = 7;

        // Define column widths for A4 Portrait (210mm total width) - Reduced widths as requested
        $wSR = 10;
        $wSub = 80;
        $wMax = 25;
        $wObs = 25;
        $wRes = 20;
        $totalTableWidth = $wSR + $wSub + $wMax + $wObs + $wRes; // 160mm
        $startX = ($pageWidth - $totalTableWidth) / 2; // Center the table

        $pdf->SetFont('Arial', 'B', 11); // Increased from 9 to 12
        $headerHeight = 9; // Increased from 7 to 9
        $lineHeight = 4.5; // Increased from 3.5 to 4.5

        // Header: SR. NO.
        $pdf->SetXY($startX, $startY);
        $pdf->Cell($wSR, $lineHeight, 'SR.', 'TLR', 0, 'C');
        $pdf->SetXY($startX, $startY + $lineHeight);
        $pdf->Cell($wSR, $lineHeight, 'NO.', 'BLR', 0, 'C');

        // Header: SUBJECTS
        $pdf->SetXY($startX + $wSR, $startY);
        $pdf->Cell($wSub, $headerHeight, 'SUBJECTS', 1, 0, 'C');

        // Header: MAXIMUM MARKS
        $pdf->SetXY($startX + $wSR + $wSub, $startY);
        $pdf->Cell($wMax, $lineHeight, 'MAXIMUM', 'TLR', 0, 'C');
        $pdf->SetXY($startX + $wSR + $wSub, $startY + $lineHeight);
        $pdf->Cell($wMax, $lineHeight, 'MARKS', 'BLR', 0, 'C');

        // Header: MARKS OBTAINED
        $pdf->SetXY($startX + $wSR + $wSub + $wMax, $startY);
        $pdf->Cell($wObs, $lineHeight, 'MARKS', 'TLR', 0, 'C');
        $pdf->SetXY($startX + $wSR + $wSub + $wMax, $startY + $lineHeight);
        $pdf->Cell($wObs, $lineHeight, 'OBTAINED', 'BLR', 0, 'C');

        // Header: RESULTS
        $pdf->SetXY($startX + $wSR + $wSub + $wMax + $wObs, $startY);
        $pdf->Cell($wRes, $headerHeight, 'RESULTS', 1, 0, 'C');

        $currentY = $startY + $headerHeight;

        if (isset($certificate->data['subjects'])) {
            $pdf->SetFont('Arial', '', 9);
            $i = 0;
            foreach ($certificate->data['subjects'] as $subject) {
                // Remove bottom borders for inner rows (using 'LR' border)
                $pdf->SetXY($startX, $currentY);
                $pdf->Cell($wSR, $rowHeight, ($i + 1) . ".", 'LR', 0, 'C');
                $pdf->Cell($wSub, $rowHeight, $subject['name'], 'LR', 0, 'L');
                $pdf->Cell($wMax, $rowHeight, $subject['maximum'], 'LR', 0, 'C');
                $pdf->Cell($wObs, $rowHeight, $subject['obtained'], 'LR', 0, 'C');
                $pdf->Cell($wRes, $rowHeight, $subject['result'], 'LR', 0, 'C');

                $currentY += $rowHeight;
                $i++;
            }

            // TOTAL MARKS ROW - Keep all borders (especially top border 1)
            $pdf->SetFont('Arial', 'B', 11); // Increased from 9 to 12

            $pdf->SetXY($startX, $currentY);
            $pdf->Cell($wSR, $rowHeight, '', 1, 0, 'C'); // Empty cell for SR. NO. column
            $pdf->Cell($wSub, $rowHeight, 'TOTAL MARKS', 1, 0, 'C'); // Center aligned
            $pdf->Cell($wMax, $rowHeight, $certificate->data['total_marks'], 1, 0, 'C');
            $pdf->Cell($wObs, $rowHeight, $certificate->data['total_marks_obtained'], 1, 0, 'C');
            $pdf->Cell($wRes, $rowHeight, $certificate->data['total_result'], 1, 0, 'C');
        }

        return $pdf->Output('S'); // Return as string
    }
}
