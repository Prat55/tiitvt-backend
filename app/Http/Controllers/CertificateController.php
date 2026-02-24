<?php

namespace App\Http\Controllers;

use App\Models\ExternalCertificate;
use App\Models\Student;
use App\Models\ExamResult;
use App\Services\StudentQRService;
use App\Services\WebsiteSettingsService;
use App\Services\CertificateOverlayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CertificateController extends Controller
{
    protected StudentQRService $studentQRService;
    protected WebsiteSettingsService $websiteSettings;
    protected CertificateOverlayService $overlayService;

    public function __construct(StudentQRService $studentQRService, WebsiteSettingsService $websiteSettings, CertificateOverlayService $overlayService)
    {
        $this->studentQRService = $studentQRService;
        $this->websiteSettings = $websiteSettings;
        $this->overlayService = $overlayService;
    }

    /**
     * Display external certificate in modern format
     */
    public function display($id)
    {
        $certificate = ExternalCertificate::with('center')->findOrFail($id);

        // Authorization check for center users
        $centerId = getUserCenterId();
        if ($centerId && $certificate->center_id !== $centerId) {
            abort(403, 'Unauthorized action.');
        }

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
    public function preview($regNo, $courseId = null)
    {
        $originalRegNo = str_replace('_', '/', $regNo);

        // Find the student by TIITVT registration number
        $student = Student::where('tiitvt_reg_no', $originalRegNo)->first();

        if (!$student) {
            abort(404, 'Student not found');
        }

        $data = $this->getCertificateData($student, $courseId);
        $certificate = $data->certificate;
        $qrDataUri = $data->qrDataUri;

        return view('certificates.exam-result-preview', compact('certificate', 'qrDataUri'));
    }

    /**
     * Download certificate PDF with overlay
     */
    public function download($regNo, $courseId = null)
    {
        // Convert _ back to / for database lookup
        $originalRegNo = str_replace('_', '/', $regNo);

        // Find the student by TIITVT registration number
        $student = Student::where('tiitvt_reg_no', $originalRegNo)->first();

        if (!$student) {
            abort(404, 'Student not found');
        }

        $certificateData = $this->getCertificateData($student, $courseId);

        $pdfContent = $this->overlayService->generate($certificateData->certificate, $certificateData->qrDataUri);

        $filename = 'Certificate_' . str_replace('/', '_', $student->tiitvt_reg_no) . '.pdf';

        // Track certificate download
        trackPageVisit('certificate_download', [
            'student_id' => $student->id,
            'reg_no' => $student->tiitvt_reg_no,
        ]);

        return response($pdfContent)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    /**
     * Download certificate PDF by QR token (Public)
     */
    public function downloadByToken($token)
    {
        $studentQR = \App\Models\StudentQR::where('qr_token', $token)
            ->where('is_active', true)
            ->first();

        if (!$studentQR) {
            abort(404, 'Invalid certificate token.');
        }

        $student = $studentQR->student;

        if (!$student) {
            abort(404, 'Student not found.');
        }

        $certificateData = $this->getCertificateData($student);

        $pdfContent = $this->overlayService->generate($certificateData->certificate, $certificateData->qrDataUri);

        $filename = 'Certificate_' . str_replace('/', '_', $student->tiitvt_reg_no) . '.pdf';

        // Track certificate download
        trackPageVisit('certificate_download', [
            'student_id' => $student->id,
            'reg_no' => $student->tiitvt_reg_no,
            'token' => $token,
        ]);

        return response($pdfContent)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    /**
     * Helper to get consolidated certificate data
     */
    private function getCertificateData(Student $student, $courseId = null)
    {
        // Get all exam results for this student
        $query = ExamResult::with(['exam.course', 'student', 'category'])
            ->where('student_id', $student->id);

        if ($courseId) {
            $query->whereHas('exam', function ($q) use ($courseId) {
                $q->where('course_id', $courseId);
            });
        }

        $examResults = $query->get()->groupBy('exam_id');

        if ($examResults->isEmpty()) {
            // Check if student has any auto-certificate courses
            $courseQuery = $student->courses()->where('auto_certificate', true);

            if ($courseId) {
                $courseQuery->where('courses.id', $courseId);
            }

            $autoCourse = $courseQuery->latest()->first();

            if (!$autoCourse) {
                abort(404, 'No exam result or auto-certificate course found for this student');
            }

            // Generate dummy certificate data based on course categories
            return $this->generateAutoCertificateData($student, $autoCourse);
        }

        // Get the latest exam (most recent)
        $latestExamId = $examResults->keys()->max();
        $latestExamResults = $examResults[$latestExamId];
        $latestExamResult = $latestExamResults->first();

        // Get the course ID from the latest exam
        $courseId = $latestExamResult->exam->course_id;

        // Get all exam results from the same course across all exams
        $allCourseResults = ExamResult::with(['exam.course', 'student', 'category'])
            ->where('student_id', $student->id)
            ->whereHas('exam', function ($query) use ($courseId) {
                $query->where('course_id', $courseId);
            })
            ->orderBy('submitted_at', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        // Group by category_id and get the most recent result for each unique category
        $categoryResults = $allCourseResults->groupBy('category_id')->map(function ($results) {
            return $results->first();
        })->values();

        // Get or create student QR code
        $studentQR = $student->qrCode;
        if (!$studentQR) {
            $studentQR = $this->studentQRService->generateStudentQR($student);
        }

        // Generate QR code data URI
        $qrDataUri = $this->studentQRService->generateQRCodeDataUri($studentQR->qr_data);

        // Calculate overall statistics
        $totalMarks = 0;
        $totalMarksObtained = 0;

        foreach ($categoryResults as $result) {
            $totalMarks += $result->total_points ?: 100;
            $totalMarksObtained += $result->points_earned ?: $result->score ?: 0;
        }

        $overallPercentage = $totalMarks > 0 ? ($totalMarksObtained / $totalMarks) * 100 : 0;

        // Create subjects array
        $subjects = $categoryResults->map(function ($result) {
            $maxMarks = $result->total_points && $result->total_points > 0 ? $result->total_points : 100;
            $obtainedMarks = $result->points_earned ?? $result->score ?? 0;
            $examCategory = $result->exam->examCategories()->where('category_id', $result->category_id)->first();
            $passingPoints = $examCategory ? (int) $examCategory->passing_points : 0;
            $resultStatus = $obtainedMarks >= $passingPoints ? 'PASS' : 'FAIL';

            return [
                'name' => $result->category->name ?? 'Subject',
                'maximum' => (int) $maxMarks,
                'obtained' => (int) round($obtainedMarks),
                'result' => $resultStatus
            ];
        })->values()->toArray();

        // Create certificate data
        $certificate = (object) [
            'reg_no' => $student->tiitvt_reg_no,
            'student_name' => $student->first_name . ($student->fathers_name ? ' ' . $student->fathers_name : '') . ($student->surname ? ' ' . $student->surname : ''),
            'course_name' => $latestExamResult->exam->course->name,
            'percentage' => round($overallPercentage, 2),
            'grade' => $this->calculateGrade($overallPercentage),
            'issued_on' => $latestExamResult->submitted_at ?? now(),
            'center_name' => $student->center->name ?? $this->websiteSettings->getWebsiteName(),
            'data' => [
                'subjects' => $subjects,
                'total_marks' => $totalMarks,
                'total_marks_obtained' => round($totalMarksObtained),
                'total_result' => $overallPercentage >= 50 ? 'PASS' : 'FAIL'
            ]
        ];

        return (object) ['certificate' => $certificate, 'qrDataUri' => $qrDataUri];
    }

    /**
     * Generate automated certificate data for courses with auto_certificate enabled
     */
    private function generateAutoCertificateData(Student $student, \App\Models\Course $course)
    {
        // Get or create student QR code
        $studentQR = $student->qrCode;
        if (!$studentQR) {
            $studentQR = $this->studentQRService->generateStudentQR($student);
        }

        // Generate QR code data URI
        $qrDataUri = $this->studentQRService->generateQRCodeDataUri($studentQR->qr_data);

        // Calculate statistics based on passing percentage
        $passingPercentage = (int) ($course->passing_percentage ?: 80);
        $totalMarks = 0;
        $totalMarksObtained = 0;

        // Use course categories as subjects
        $subjects = $course->categories->map(function ($category) use ($passingPercentage, &$totalMarks, &$totalMarksObtained) {
            $maxMarks = 100;
            $obtainedMarks = $passingPercentage; // Set marks to exact passing percentage

            $totalMarks += $maxMarks;
            $totalMarksObtained += $obtainedMarks;

            return [
                'name' => $category->name,
                'maximum' => (int) $maxMarks,
                'obtained' => (int) round($obtainedMarks),
                'result' => 'PASS'
            ];
        })->values()->toArray();

        // Create certificate data
        $certificate = (object) [
            'reg_no' => $student->tiitvt_reg_no,
            'student_name' => $student->full_name,
            'course_name' => $course->name,
            'percentage' => (float) $passingPercentage,
            'grade' => $this->calculateGrade($passingPercentage),
            'issued_on' => now(), // Auto-certificate is issued when requested
            'center_name' => $student->center->name ?? $this->websiteSettings->getWebsiteName(),
            'data' => [
                'subjects' => $subjects,
                'total_marks' => $totalMarks,
                'total_marks_obtained' => round($totalMarksObtained),
                'total_result' => 'PASS'
            ]
        ];

        return (object) ['certificate' => $certificate, 'qrDataUri' => $qrDataUri];
    }

    /**
     * Calculate grade based on percentage
     */
    private function calculateGrade($percentage)
    {
        if ($percentage >= 90) return 'A+';
        if ($percentage >= 80) return 'A';
        if ($percentage >= 70) return 'B+';
        if ($percentage >= 60) return 'B';
        if ($percentage >= 50) return 'C+';
        if ($percentage >= 40) return 'C';
        return 'F';
    }
}
