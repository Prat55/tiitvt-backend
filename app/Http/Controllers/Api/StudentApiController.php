<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ExamResult;
use App\Models\Installment;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class StudentApiController extends Controller
{
    public function profile(Request $request): JsonResponse
    {
        $student = $this->resolveStudent($request);

        return response()->json([
            'data' => [
                'id' => $student->id,
                'registration_no' => $student->tiitvt_reg_no,
                'name' => $student->full_name,
                'email' => $student->email,
                'mobile' => $student->mobile,
                'enrollment_date' => optional($student->enrollment_date)?->toDateString(),
                'date_of_birth' => $student->date_of_birth->format('Y-m-d'),
            ],
        ]);
    }

    public function courses(Request $request): JsonResponse
    {
        $student = $this->resolveStudent($request);

        $courses = $student->courses()
            ->select('courses.id', 'courses.name', 'courses.slug', 'courses.duration', 'courses.price')
            ->withPivot(['course_taken', 'batch_time', 'enrollment_date'])
            ->get()
            ->map(function ($course) {
                $lectureUrl = $course->pivot->course_taken;

                return [
                    'id' => $course->id,
                    'name' => $course->name,
                    'slug' => $course->slug,
                    'duration' => $course->duration,
                    'price' => (float) $course->price,
                    'enrollment_date' => optional($course->pivot->enrollment_date)?->toDateString(),
                    'batch_time' => $course->pivot->batch_time,
                    'lecture_url' => $lectureUrl,
                    'open_lecture_url' => $lectureUrl,
                ];
            })
            ->values();

        return response()->json([
            'data' => $courses,
        ]);
    }

    public function results(Request $request): JsonResponse
    {
        $student = $this->resolveStudent($request);

        $results = ExamResult::query()
            ->with(['exam.course:id,name'])
            ->where('student_id', $student->id)
            ->latest('submitted_at')
            ->latest('id')
            ->get()
            ->map(function (ExamResult $result) {
                return [
                    'id' => $result->id,
                    'course_name' => $result->exam?->course?->name,
                    'exam_title' => $result->exam?->title,
                    'percentage' => (float) $result->percentage,
                    'grade' => $result->grade,
                    'result' => $result->result,
                    'submitted_at' => optional($result->submitted_at)?->toIso8601String(),
                ];
            })
            ->values();

        return response()->json([
            'data' => $results,
        ]);
    }

    public function paymentLogs(Request $request): JsonResponse
    {
        $student = $this->resolveStudent($request);
        $hasInstallmentNo = Schema::hasColumn('installments', 'installment_no');

        $logs = collect();

        if ((float) $student->down_payment > 0) {
            $logs->push([
                'type' => 'down_payment',
                'id' => $student->id,
                'installment_no' => null,
                'amount' => (float) $student->down_payment,
                'paid_amount' => (float) $student->down_payment,
                'status' => 'paid',
                'paid_date' => optional($student->enrollment_date)?->toDateString(),
                'receipt_download_url' => route('api.documents.receipts.down-payment', $student),
            ]);
        }

        $installmentsQuery = $student->installments();

        if ($hasInstallmentNo) {
            $installmentsQuery->orderBy('installment_no');
        } else {
            $installmentsQuery->oldest('id');
        }

        $installments = $installmentsQuery
            ->get()
            ->values()
            ->map(function (Installment $installment, int $index) use ($hasInstallmentNo) {
                return [
                    'type' => 'installment',
                    'id' => $installment->id,
                    'installment_no' => $hasInstallmentNo ? $installment->installment_no : ($index + 1),
                    'amount' => (float) $installment->amount,
                    'paid_amount' => (float) ($installment->paid_amount ?? 0),
                    'status' => $installment->status?->value ?? (string) $installment->status,
                    'paid_date' => optional($installment->paid_date)?->toDateString(),
                    'receipt_download_url' => route('api.documents.receipts.installment', $installment),
                ];
            });

        $logs = $logs->concat($installments)->values();

        return response()->json([
            'data' => $logs,
        ]);
    }

    public function certificates(Request $request): JsonResponse
    {
        $student = $this->resolveStudent($request);

        $courses = $student->courses()
            ->select('courses.id', 'courses.name', 'courses.auto_certificate', 'courses.passing_percentage')
            ->get();

        $courseIds = $courses->pluck('id')->values();

        $resultsByCourse = ExamResult::query()
            ->with('exam:id,course_id')
            ->where('student_id', $student->id)
            ->whereHas('exam', function ($query) use ($courseIds) {
                $query->whereIn('course_id', $courseIds->all());
            })
            ->orderByDesc('submitted_at')
            ->orderByDesc('id')
            ->get()
            ->groupBy(function (ExamResult $result) {
                return $result->exam?->course_id;
            });

        $certificates = $courses
            ->map(function ($course) use ($student, $resultsByCourse) {
                if (!$course->auto_certificate) {
                    return null;
                }

                $allCourseResults = $resultsByCourse->get($course->id, collect());

                if ($allCourseResults->isEmpty()) {
                    $percentage = (float) ($course->passing_percentage ?: 80);
                    $issuedOn = optional($student->enrollment_date)?->toDateString();
                } else {
                    $categoryResults = $allCourseResults
                        ->groupBy('category_id')
                        ->map(function ($results) {
                            return $results->first();
                        })
                        ->values();

                    $totalPoints = 0;
                    $pointsEarned = 0;

                    foreach ($categoryResults as $result) {
                        $totalPoints += $result->total_points ?: 100;
                        $pointsEarned += $result->points_earned ?: $result->score ?: 0;
                    }

                    $overallPercentage = $totalPoints > 0 ? ($pointsEarned / $totalPoints) * 100 : 0;
                    $percentage = round($overallPercentage, 2);
                    $issuedOn = optional($allCourseResults->first()->submitted_at)?->toDateString();
                }

                return [
                    'id' => null,
                    'course_id' => $course->id,
                    'course_name' => $course->name,
                    'certificate_number' => null,
                    'status' => 'auto_available',
                    'issued_on' => $issuedOn,
                    'percentage' => $percentage,
                    'download_url' => route('api.documents.certificates.auto-download', $course),
                    'type' => 'auto',
                ];
            })
            ->filter()
            ->values();

        return response()->json([
            'data' => $certificates,
        ]);
    }

    private function resolveStudent(Request $request): Student
    {
        $actor = $request->user();

        if (!$actor instanceof Student) {
            abort(403, 'Only student token can access this endpoint.');
        }

        $actor->loadMissing(['center:id,name,address,state,country', 'courses:id,name']);

        return $actor;
    }
}
