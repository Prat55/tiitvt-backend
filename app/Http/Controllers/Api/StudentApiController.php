<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ExamResult;
use App\Models\Installment;
use App\Models\Student;
use App\Models\StudentLectureProgress;
use App\Services\ExamResultSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class StudentApiController extends Controller
{
    private const DEFAULT_INITIAL_RANGE_BYTES = 20971520;
    private const DEFAULT_MEDIA_BUFFER_BYTES = 5242880;

    private array $videoRangeHintsCache = [];

    public function __construct(private readonly ExamResultSyncService $examResultSyncService) {}

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
            ->select('courses.id', 'courses.name', 'courses.slug', 'courses.duration', 'courses.price', 'courses.image', 'courses.meta_description', 'courses.description')
            ->with(['categories' => function ($query) {
                $query->select('categories.id', 'categories.name', 'categories.slug', 'categories.lectures', 'categories.materials');
            }])
            ->withPivot(['course_taken', 'batch_time', 'enrollment_date'])
            ->get()
            ->map(function ($course) {
                // Map categories with their lectures and materials
                $categories = $course->categories->map(function ($category) use ($course) {
                    $catLectures = collect($category->lectures ?? [])
                        ->map(function ($lecture, int $index) use ($course, $category) {
                            if (!is_array($lecture)) {
                                return null;
                            }

                            $title = trim((string) ($lecture['title'] ?? ''));
                            $url = trim((string) ($lecture['url'] ?? ''));
                            $path = trim((string) ($lecture['path'] ?? ''));

                            if ($title === '' || ($url === '' && $path === '')) {
                                return null;
                            }

                            $videoUrl = $url;
                            if ($path !== '') {
                                $videoUrl = route('api.videos.stream', ['path' => base64_encode($path)]);
                            }

                            $rangeHints = $this->buildVideoRangeHints($path);
                            $lectureKey = $this->makeLectureKey($course->id, $category->id, $title, $path, $url, $index);

                            return [
                                'order' => $index + 1,
                                'lecture_key' => $lectureKey,
                                'title' => $title,
                                'video_url' => $videoUrl,
                                'description' => $lecture['description'] ?? '',
                                'start_byte' => $rangeHints['start_byte'],
                                'end_byte' => $rangeHints['end_byte'],
                                'file_size' => $rangeHints['file_size'],
                                'moov_atom_offset' => $rangeHints['moov_atom_offset'],
                                'media_data_atom_offset' => $rangeHints['media_data_atom_offset'],
                                'media_start_byte' => $rangeHints['media_start_byte'],
                                'suggested_initial_end_byte' => $rangeHints['suggested_initial_end_byte'],
                                'suggested_initial_range' => $rangeHints['suggested_initial_range'],
                            ];
                        })
                        ->filter()
                        ->values();

                    $materials = collect($category->materials ?? [])
                        ->map(function ($material) {
                            if (!is_array($material)) {
                                return null;
                            }

                            $path = trim((string) ($material['path'] ?? ''));

                            return [
                                'name' => trim((string) ($material['name'] ?? '')),
                                'description' => trim((string) ($material['description'] ?? '')),
                                'path' => $path ? asset('storage/' . $path) : '',
                                'file_name' => trim((string) ($material['file_name'] ?? '')),
                                'file_size' => (int) ($material['file_size'] ?? 0),
                                'mime_type' => trim((string) ($material['mime_type'] ?? '')),
                            ];
                        })
                        ->filter(fn($material) => $material && !empty($material['path']))
                        ->values();

                    return [
                        'id' => $category->id,
                        'name' => $category->name,
                        'slug' => $category->slug,
                        'lectures' => $catLectures,
                        'materials' => $materials,
                    ];
                })->values();

                return [
                    'id' => $course->id,
                    'name' => $course->name,
                    'slug' => $course->slug,
                    'image_url' => $course->image ? asset('storage/' . $course->image) : null,
                    'meta_description' => $course->meta_description,
                    'description' => $course->description,
                    'batch_time' => $course->pivot->batch_time,
                    'categories' => $categories,
                ];
            })
            ->values();

        return response()->json([
            'data' => $courses,
        ]);
    }

    public function lectureProgress(Request $request): JsonResponse
    {
        $student = $this->resolveStudent($request);

        $progress = StudentLectureProgress::query()
            ->where('student_id', $student->id)
            ->orderByDesc('last_watched_at')
            ->orderByDesc('updated_at')
            ->get()
            ->map(fn(StudentLectureProgress $entry) => $this->formatLectureProgress($entry))
            ->values();

        return response()->json([
            'data' => $progress,
        ]);
    }

    public function upsertLectureProgress(Request $request, string $lectureKey): JsonResponse
    {
        $student = $this->resolveStudent($request);

        $validated = Validator::make($request->all(), [
            'course_id' => ['nullable', 'integer', 'exists:courses,id'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'lecture_title' => ['nullable', 'string', 'max:255'],
            'duration_seconds' => ['nullable', 'numeric', 'min:0'],
            'position_seconds' => ['nullable', 'numeric', 'min:0'],
            'watched_seconds' => ['nullable', 'numeric', 'min:0'],
            'is_completed' => ['nullable', 'boolean'],
        ])->validate();

        $progress = StudentLectureProgress::firstOrNew([
            'student_id' => $student->id,
            'lecture_key' => $lectureKey,
        ]);

        if (array_key_exists('course_id', $validated)) {
            $progress->course_id = $validated['course_id'];
        }

        if (array_key_exists('category_id', $validated)) {
            $progress->category_id = $validated['category_id'];
        }

        if (array_key_exists('lecture_title', $validated)) {
            $progress->lecture_title = $validated['lecture_title'];
        }

        if (array_key_exists('duration_seconds', $validated)) {
            $progress->duration_seconds = round((float) $validated['duration_seconds'], 3);
        }

        if (array_key_exists('position_seconds', $validated)) {
            $progress->position_seconds = round((float) $validated['position_seconds'], 3);
        }

        if (array_key_exists('watched_seconds', $validated)) {
            $incomingWatchedSeconds = round((float) $validated['watched_seconds'], 3);
            $progress->watched_seconds = max((float) ($progress->watched_seconds ?? 0), $incomingWatchedSeconds);
        }

        $completionFromPayload = (bool) ($validated['is_completed'] ?? false);
        $completionFromPlayback = $this->shouldMarkLectureCompleted(
            array_key_exists('position_seconds', $validated) ? (float) $validated['position_seconds'] : (float) ($progress->position_seconds ?? 0),
            array_key_exists('duration_seconds', $validated) ? (float) $validated['duration_seconds'] : (float) ($progress->duration_seconds ?? 0)
        );

        $progress->is_completed = (bool) ($progress->is_completed || $completionFromPayload || $completionFromPlayback);

        if ($progress->is_completed && $progress->completed_at === null) {
            $progress->completed_at = now();
        }

        $progress->last_watched_at = now();
        $progress->save();

        return response()->json([
            'data' => $this->formatLectureProgress($progress->fresh()),
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
                    $summary = $this->examResultSyncService->summarizeCourseResults($allCourseResults);
                    $percentage = $summary['overall_percentage'];
                    $issuedOn = optional($summary['issued_on'])?->toDateString();
                    $isPassed = $summary['is_passed'];
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

    private function formatLectureProgress(StudentLectureProgress $entry): array
    {
        return [
            'lecture_key' => $entry->lecture_key,
            'course_id' => $entry->course_id,
            'category_id' => $entry->category_id,
            'lecture_title' => $entry->lecture_title,
            'duration_seconds' => $entry->duration_seconds !== null ? (float) $entry->duration_seconds : null,
            'position_seconds' => $entry->position_seconds !== null ? (float) $entry->position_seconds : null,
            'watched_seconds' => $entry->watched_seconds !== null ? (float) $entry->watched_seconds : null,
            'is_completed' => (bool) $entry->is_completed,
            'completed_at' => optional($entry->completed_at)?->toIso8601String(),
            'last_watched_at' => optional($entry->last_watched_at)?->toIso8601String(),
            'updated_at' => optional($entry->updated_at)?->toIso8601String(),
        ];
    }

    private function shouldMarkLectureCompleted(float $positionSeconds, float $durationSeconds): bool
    {
        if ($durationSeconds <= 0) {
            return false;
        }

        return ($positionSeconds / $durationSeconds) >= 0.9;
    }

    private function makeLectureKey(
        int|string|null $courseId,
        int|string|null $categoryId,
        string $title,
        string $path,
        string $url,
        int $index
    ): string {
        $source = $path !== ''
            ? "path:{$path}"
            : ($url !== '' ? "url:{$url}" : "title:{$title}|order:{$index}");

        return sprintf(
            '%s-%s-%s',
            $courseId ?? 'x',
            $categoryId ?? 'x',
            sha1($source)
        );
    }

    private function buildVideoRangeHints(string $path): array
    {
        if ($path === '') {
            return $this->emptyVideoRangeHints();
        }

        if (isset($this->videoRangeHintsCache[$path])) {
            return $this->videoRangeHintsCache[$path];
        }

        $disk = Storage::disk('public');
        if (!$disk->exists($path)) {
            return $this->videoRangeHintsCache[$path] = $this->emptyVideoRangeHints();
        }

        $fullPath = $disk->path($path);
        $fileSize = (int) $disk->size($path);
        $atoms = $this->parseTopLevelAtoms($fullPath);

        $moovAtomOffset = $atoms['moov']['offset'] ?? null;
        $mediaDataAtomOffset = $atoms['mdat']['offset'] ?? null;
        $mediaStartByte = isset($atoms['mdat'])
            ? $atoms['mdat']['offset'] + $atoms['mdat']['header_size']
            : null;

        $suggestedInitialEndByte = $this->determineSuggestedInitialEndByte($fileSize, $mediaStartByte);

        return $this->videoRangeHintsCache[$path] = [
            'start_byte' => $fileSize > 0 ? 0 : null,
            'end_byte' => $suggestedInitialEndByte,
            'file_size' => $fileSize,
            'moov_atom_offset' => $moovAtomOffset,
            'media_data_atom_offset' => $mediaDataAtomOffset,
            'media_start_byte' => $mediaStartByte,
            'suggested_initial_end_byte' => $suggestedInitialEndByte,
            'suggested_initial_range' => $suggestedInitialEndByte === null
                ? null
                : "bytes=0-{$suggestedInitialEndByte}",
        ];
    }

    private function emptyVideoRangeHints(): array
    {
        return [
            'start_byte' => null,
            'end_byte' => null,
            'file_size' => 0,
            'moov_atom_offset' => null,
            'media_data_atom_offset' => null,
            'media_start_byte' => null,
            'suggested_initial_end_byte' => null,
            'suggested_initial_range' => null,
        ];
    }

    private function determineSuggestedInitialEndByte(int $fileSize, ?int $mediaStartByte): ?int
    {
        if ($fileSize <= 0) {
            return null;
        }

        $defaultEndByte = min($fileSize - 1, self::DEFAULT_INITIAL_RANGE_BYTES - 1);
        if ($mediaStartByte === null) {
            return $defaultEndByte;
        }

        $mediaBufferedEndByte = min(
            $fileSize - 1,
            $mediaStartByte + self::DEFAULT_MEDIA_BUFFER_BYTES - 1
        );

        return max($defaultEndByte, $mediaBufferedEndByte);
    }

    private function parseTopLevelAtoms(string $fullPath): array
    {
        $stream = @fopen($fullPath, 'rb');
        if ($stream === false) {
            return [];
        }

        $fileSize = filesize($fullPath);
        if ($fileSize === false || $fileSize < 8) {
            fclose($stream);
            return [];
        }

        $atoms = [];
        $offset = 0;
        $maxAtomsToScan = 128;

        try {
            while ($offset + 8 <= $fileSize && count($atoms) < 2 && $maxAtomsToScan-- > 0) {
                if (fseek($stream, $offset) !== 0) {
                    break;
                }

                $header = fread($stream, 8);
                if ($header === false || strlen($header) !== 8) {
                    break;
                }

                $size = unpack('N', substr($header, 0, 4))[1];
                $type = substr($header, 4, 4);
                $headerSize = 8;

                if ($size === 1) {
                    $extendedSizeBytes = fread($stream, 8);
                    if ($extendedSizeBytes === false || strlen($extendedSizeBytes) !== 8) {
                        break;
                    }

                    $size = $this->unpackUInt64($extendedSizeBytes);
                    $headerSize = 16;
                } elseif ($size === 0) {
                    $size = $fileSize - $offset;
                }

                if ($size < $headerSize || $offset + $size > $fileSize) {
                    break;
                }

                if ($type === 'moov' || $type === 'mdat') {
                    $atoms[$type] = [
                        'offset' => $offset,
                        'header_size' => $headerSize,
                        'size' => $size,
                    ];
                }

                $offset += $size;
            }
        } finally {
            fclose($stream);
        }

        return $atoms;
    }

    private function unpackUInt64(string $bytes): int
    {
        $parts = unpack('Nhigh/Nlow', $bytes);

        return ((int) $parts['high'] << 32) | (int) $parts['low'];
    }
}
