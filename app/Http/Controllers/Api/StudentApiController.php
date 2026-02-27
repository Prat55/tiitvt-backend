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
            ->select('courses.id', 'courses.name', 'courses.slug', 'courses.duration', 'courses.price', 'courses.lectures')
            ->withPivot(['course_taken', 'batch_time', 'enrollment_date'])
            ->get()
            ->map(function ($course) {
                $lectures = collect($course->lectures ?? [])
                    ->map(function ($lecture, int $index) {
                        if (!is_array($lecture)) {
                            return null;
                        }

                        $title = trim((string) ($lecture['title'] ?? ''));
                        $url = trim((string) ($lecture['url'] ?? ''));

                        if ($title === '' || $url === '') {
                            return null;
                        }

                        return [
                            'order' => $index + 1,
                            'title' => $title,
                            'url' => $url,
                            'open_url' => $url,
                        ];
                    })
                    ->filter()
                    ->values();

                return [
                    'id' => $course->id,
                    'name' => $course->name,
                    'slug' => $course->slug,
                    'batch_time' => $course->pivot->batch_time,
                    'lectures' => $lectures,
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
        $courseFee = (float) $student->course_fees;

        $logs = collect();

        if ((float) $student->down_payment > 0) {
            $logs->push([
                'type' => 'down_payment',
                'id' => $student->id,
                'installment_no' => null,
                'amount' => $courseFee,
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
            ->map(function (Installment $installment, int $index) use ($courseFee, $hasInstallmentNo) {
                $paidAmount = (float) ($installment->paid_amount ?? 0);
                $status = $installment->status?->value ?? (string) $installment->status;

                return [
                    'type' => 'installment',
                    'id' => $installment->id,
                    'installment_no' => $hasInstallmentNo ? $installment->installment_no : ($index + 1),
                    'amount' => $courseFee,
                    'paid_amount' => $paidAmount,
                    'status' => $status,
                    'paid_date' => optional($installment->paid_date)?->toDateString(),
                    'receipt_download_url' => $status === 'paid'
                        ? route('api.documents.receipts.installment', $installment)
                        : null,
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

        $certificates = $courses
            ->map(function ($course) use ($student) {
                $allCourseResults = ExamResult::query()
                    ->where('student_id', $student->id)
                    ->whereHas('exam', function ($query) use ($course) {
                        $query->where('course_id', $course->id);
                    })
                    ->orderByDesc('submitted_at')
                    ->orderByDesc('id')
                    ->get();

                $hasResults = $allCourseResults->isNotEmpty();
                $isAuto = (bool) $course->auto_certificate;

                if (!$hasResults && !$isAuto) {
                    return null;
                }

                if (!$hasResults) {
                    $percentage = (float) ($course->passing_percentage ?: 80);
                    $issuedOn = optional($student->enrollment_date)?->toDateString();
                    $isPassed = true;
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
                    $isPassed = $isAuto
                        ? $overallPercentage >= (float) ($course->passing_percentage ?: 80)
                        : $overallPercentage >= 50;
                }

                return [
                    'course_id' => $course->id,
                    'course_name' => $course->name,
                    'status' => $isPassed ? 'eligible' : 'not_eligible',
                    'has_results' => $hasResults,
                    'auto_certificate' => $isAuto,
                    'issued_on' => $issuedOn,
                    'percentage' => $percentage,
                    'download_url' => $isPassed
                        ? route('api.documents.certificates.download', $course->id)
                        : null,
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
