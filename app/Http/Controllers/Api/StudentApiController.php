<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\ExamResult;
use App\Models\Installment;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

        $installments = $student->installments()
            ->orderBy('installment_no')
            ->get()
            ->map(function (Installment $installment) {
                return [
                    'type' => 'installment',
                    'id' => $installment->id,
                    'installment_no' => $installment->installment_no,
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

        $certificates = $student->certificates()
            ->with('course:id,name')
            ->latest('issued_on')
            ->latest('id')
            ->get()
            ->map(function (Certificate $certificate) {
                return [
                    'id' => $certificate->id,
                    'course_id' => $certificate->course_id,
                    'course_name' => $certificate->course?->name,
                    'certificate_number' => $certificate->certificate_number,
                    'status' => $certificate->status,
                    'issued_on' => optional($certificate->issued_on)?->toDateString(),
                    'download_url' => route('api.documents.certificates.download', $certificate),
                ];
            })
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
