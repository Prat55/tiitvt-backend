<?php

namespace App\Http\Controllers;

use App\Models\ExternalCertificate;
use App\Models\Student;
use App\Models\ExamResult;
use App\Services\StudentQRService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CertificateController extends Controller
{
    protected StudentQRService $studentQRService;

    public function __construct(StudentQRService $studentQRService)
    {
        $this->studentQRService = $studentQRService;
    }

    /**
     * Display external certificate in modern format
     */
    public function display($id)
    {
        $certificate = ExternalCertificate::with('center')->findOrFail($id);

        // Generate QR code data URI if not exists
        $qrDataUri = null;
        if ($certificate->qr_code_path && Storage::disk('public')->exists($certificate->qr_code_path)) {
            $contents = Storage::disk('public')->get($certificate->qr_code_path);
            $qrDataUri = 'data:image/png;base64,' . base64_encode($contents);
        }

        return view('certificates.modern-display', compact('certificate', 'qrDataUri'));
    }

    /**
     * Preview certificate by TIITVT registration number (auto certificate)
     */
    public function preview($regNo)
    {
        // Convert _ back to / for database lookup
        $originalRegNo = str_replace('_', '/', $regNo);

        // Find the student by TIITVT registration number
        $student = Student::where('tiitvt_reg_no', $originalRegNo)->first();

        if (!$student) {
            abort(404, 'Student not found');
        }

        // Get the latest exam result for this student
        $examResult = ExamResult::with(['exam.course', 'student'])
            ->where('student_id', $student->id)
            ->latest()
            ->first();

        if (!$examResult) {
            abort(404, 'No exam result found for this student');
        }

        // Generate QR code for the student using StudentQRService
        $qrDataUri = null;

        // Get or create student QR code
        $studentQR = $student->qrCode;
        if (!$studentQR) {
            $studentQR = $this->studentQRService->generateStudentQR($student);
        }

        // Generate QR code data URI
        $qrDataUri = $this->studentQRService->generateQRCodeDataUri($studentQR->qr_data);

        // Create certificate data from exam result
        $certificate = (object) [
            'reg_no' => $student->tiitvt_reg_no,
            'student_name' => $student->first_name . ($student->fathers_name ? ' ' . $student->fathers_name : '') . ($student->surname ? ' ' . $student->surname : ''),
            'course_name' => $examResult->exam->course->name,
            'percentage' => $examResult->percentage,
            'grade' => $this->calculateGrade($examResult->percentage),
            'issued_on' => $examResult->submitted_at ?? now(),
            'center_name' => $student->center->name ?? 'TIITVT',
            'data' => [
                'subjects' => [
                    [
                        'name' => $examResult->exam->course->name,
                        'maximum' => 100,
                        'obtained' => round($examResult->percentage),
                        'result' => $examResult->percentage >= 50 ? 'PASS' : 'FAIL'
                    ]
                ],
                'total_marks' => 100,
                'total_marks_obtained' => round($examResult->percentage),
                'total_result' => $examResult->percentage >= 50 ? 'PASS' : 'FAIL'
            ]
        ];

        return view('certificates.exam-result-preview', compact('certificate', 'qrDataUri'));
    }

    /**
     * Calculate grade based on percentage
     */
    private function calculateGrade($percentage)
    {
        if ($percentage >= 90) return 'A+';
        if ($percentage >= 80) return 'A';
        if ($percentage >= 70) return 'B';
        if ($percentage >= 60) return 'C';
        if ($percentage >= 50) return 'D';
        return 'F';
    }
}
