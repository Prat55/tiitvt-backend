<?php

namespace App\Http\Controllers;

use App\Models\ExternalCertificate;
use App\Models\Student;
use App\Models\ExamResult;
use App\Services\StudentQRService;
use App\Services\WebsiteSettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CertificateController extends Controller
{
    protected StudentQRService $studentQRService;
    protected WebsiteSettingsService $websiteSettings;

    public function __construct(StudentQRService $studentQRService, WebsiteSettingsService $websiteSettings)
    {
        $this->studentQRService = $studentQRService;
        $this->websiteSettings = $websiteSettings;
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
    public function preview($regNo)
    {
        // Convert _ back to / for database lookup
        $originalRegNo = str_replace('_', '/', $regNo);

        // Find the student by TIITVT registration number
        $student = Student::where('tiitvt_reg_no', $originalRegNo)->first();

        if (!$student) {
            abort(404, 'Student not found');
        }

        // Get all exam results for this student grouped by exam
        $examResults = ExamResult::with(['exam.course', 'student', 'category'])
            ->where('student_id', $student->id)
            ->get()
            ->groupBy('exam_id');

        if ($examResults->isEmpty()) {
            abort(404, 'No exam result found for this student');
        }

        // Get the latest exam (most recent)
        $latestExamId = $examResults->keys()->max();
        $latestExamResults = $examResults[$latestExamId];
        $latestExamResult = $latestExamResults->first();

        // Get the course ID from the latest exam
        $courseId = $latestExamResult->exam->course_id;

        // Get all exam results from the same course across all exams
        // This includes categories from previous exams and current exam
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
            // Return the most recent result for this category (first in the sorted collection)
            return $results->first();
        })->values();

        // Generate QR code for the student using StudentQRService
        $qrDataUri = null;

        // Get or create student QR code
        $studentQR = $student->qrCode;
        if (!$studentQR) {
            $studentQR = $this->studentQRService->generateStudentQR($student);
        }

        // Generate QR code data URI
        $qrDataUri = $this->studentQRService->generateQRCodeDataUri($studentQR->qr_data);

        // Calculate overall statistics from all categories (using the most recent result for each category)
        $totalMarks = 0;
        $totalMarksObtained = 0;

        foreach ($categoryResults as $result) {
            $totalMarks += $result->total_points ?: 100;
            $totalMarksObtained += $result->points_earned ?: $result->score ?: 0;
        }

        $overallPercentage = $totalMarks > 0 ? ($totalMarksObtained / $totalMarks) * 100 : 0;

        // Create subjects array from categories (using the most recent result for each category)
        $subjects = $categoryResults->map(function ($result) {
            // Get max marks from total_points, default to 100 if not set
            $maxMarks = $result->total_points && $result->total_points > 0 ? $result->total_points : 100;

            // Get obtained marks from points_earned, fallback to score
            $obtainedMarks = $result->points_earned ?? $result->score ?? 0;

            // Get passing points from exam_categories table
            $examCategory = $result->exam->examCategories()->where('category_id', $result->category_id)->first();
            $passingPoints = $examCategory ? (int) $examCategory->passing_points : 0;

            // Calculate result based on passing points
            $resultStatus = $obtainedMarks >= $passingPoints ? 'PASS' : 'FAIL';

            return [
                'name' => $result->category->name ?? 'Subject',
                'maximum' => (int) $maxMarks,
                'obtained' => (int) round($obtainedMarks),
                'result' => $resultStatus
            ];
        })->values()->toArray();

        // Create certificate data from exam results
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

        return view('certificates.exam-result-preview', compact('certificate', 'qrDataUri'));
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
