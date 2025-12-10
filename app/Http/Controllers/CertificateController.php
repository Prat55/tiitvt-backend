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
            ->orderBy('created_at', 'desc')
            ->get();

        // Group by category and get the most recent result for each category
        $categoryResults = $allCourseResults->groupBy('category_id')->map(function ($results) {
            // Return the most recent result for this category (first in the sorted collection)
            return $results->first();
        });

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
        $totalMarks = $categoryResults->sum('total_points') ?: $categoryResults->count() * 100;
        $totalMarksObtained = $categoryResults->sum('points_earned') ?: $categoryResults->sum('score');
        $overallPercentage = $totalMarks > 0 ? ($totalMarksObtained / $totalMarks) * 100 : 0;

        // Create subjects array from categories (using the most recent result for each category)
        $subjects = $categoryResults->map(function ($result) {
            $maxMarks = $result->total_points ?: 100;
            $obtainedMarks = $result->points_earned ?: $result->score;
            $percentage = $maxMarks > 0 ? ($obtainedMarks / $maxMarks) * 100 : 0;

            return [
                'name' => $result->category->name ?? 'Subject',
                'maximum' => $maxMarks,
                'obtained' => round($obtainedMarks),
                'result' => $percentage >= 50 ? 'PASS' : 'FAIL'
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
