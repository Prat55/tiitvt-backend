<?php

namespace App\Services;

use App\Enums\ExamResultStatusEnum;
use App\Models\Course;
use App\Models\ExamResult;
use App\Models\Student;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class ExamResultSyncService
{
    public function evaluateExamResult(ExamResult $examResult): array
    {
        $examResult->loadMissing(['exam.examCategories', 'category']);

        $examCategory = $examResult->exam?->examCategories?->firstWhere('category_id', $examResult->category_id);

        $maximumMarks = (int) ($examResult->total_points ?: $examCategory?->total_points ?: 100);
        $maximumMarks = $maximumMarks > 0 ? $maximumMarks : 100;

        $obtainedMarks = (float) ($examResult->points_earned ?? $examResult->score ?? 0);
        $passingPoints = $examCategory?->passing_points;

        if ($passingPoints === null) {
            $passingPoints = $examResult->category_id ? 0 : (int) ceil($maximumMarks * 0.4);
        }

        $percentage = $maximumMarks > 0
            ? round(($obtainedMarks / $maximumMarks) * 100, 2)
            : 0.0;

        $isPassed = $obtainedMarks >= (float) $passingPoints;

        return [
            'maximum_marks' => $maximumMarks,
            'obtained_marks' => $obtainedMarks,
            'passing_points' => (float) $passingPoints,
            'percentage' => $percentage,
            'result' => $isPassed ? 'passed' : 'failed',
            'result_label' => $isPassed ? 'PASS' : 'FAIL',
            'result_status' => $isPassed ? ExamResultStatusEnum::Passed : ExamResultStatusEnum::Failed,
        ];
    }

    public function summarizeCourseResults(EloquentCollection $results): array
    {
        if ($results->isEmpty()) {
            return [
                'subjects' => [],
                'total_marks' => 0,
                'total_marks_obtained' => 0,
                'overall_percentage' => 0.0,
                'overall_result' => 'FAIL',
                'is_passed' => false,
                'issued_on' => null,
            ];
        }

        $sortedResults = $results->sortByDesc(function (ExamResult $result) {
            return sprintf(
                '%s-%020d-%020d',
                optional($result->submitted_at)->timestamp ?? 0,
                optional($result->created_at)->timestamp ?? 0,
                $result->id ?? 0,
            );
        })->values();

        $categoryResults = $sortedResults
            ->groupBy(function (ExamResult $result) {
                if ($result->category_id !== null) {
                    return 'category-' . $result->category_id;
                }

                return 'uncategorized-' . ($result->exam_id ?: $result->id ?: spl_object_id($result));
            })
            ->map(function (EloquentCollection $groupedResults) {
                return $groupedResults->first();
            })
            ->values();

        $subjects = $categoryResults->map(function (ExamResult $result) {
            $evaluation = $this->evaluateExamResult($result);

            return [
                'name' => $result->category->name ?? 'Subject',
                'maximum' => (int) $evaluation['maximum_marks'],
                'obtained' => (int) round($evaluation['obtained_marks']),
                'result' => $evaluation['result_label'],
            ];
        })->values();

        $totalMarks = (int) $subjects->sum('maximum');
        $totalMarksObtained = (int) $subjects->sum('obtained');
        $overallPercentage = $totalMarks > 0
            ? round(($totalMarksObtained / $totalMarks) * 100, 2)
            : 0.0;
        $isPassed = $subjects->isNotEmpty() && $subjects->every(fn(array $subject) => $subject['result'] === 'PASS');

        return [
            'subjects' => $subjects->all(),
            'total_marks' => $totalMarks,
            'total_marks_obtained' => $totalMarksObtained,
            'overall_percentage' => $overallPercentage,
            'overall_result' => $isPassed ? 'PASS' : 'FAIL',
            'is_passed' => $isPassed,
            'issued_on' => $sortedResults->first()?->submitted_at ?? $sortedResults->first()?->created_at,
        ];
    }

    public function getCourseSummary(Student $student, Course $course): ?array
    {
        $results = ExamResult::query()
            ->with(['exam.examCategories', 'exam.course', 'category'])
            ->where('student_id', $student->id)
            ->whereHas('exam', function ($query) use ($course) {
                $query->where('course_id', $course->id);
            })
            ->orderByDesc('submitted_at')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        if ($results->isEmpty()) {
            return null;
        }

        return $this->summarizeCourseResults($results);
    }

    public function syncExamResult(ExamResult $examResult, bool $dryRun = false): array
    {
        $evaluation = $this->evaluateExamResult($examResult);

        $attributes = [
            'percentage' => $evaluation['percentage'],
            'result' => $evaluation['result'],
        ];

        if ($this->normalizeResultStatus($examResult->result_status) !== ExamResultStatusEnum::NotDeclared->value) {
            $attributes['result_status'] = $evaluation['result_status']->value;
        }

        $changes = [];

        foreach ($attributes as $field => $newValue) {
            $currentValue = $field === 'result_status'
                ? $this->normalizeResultStatus($examResult->{$field})
                : $examResult->{$field};

            if ($field === 'percentage' && $currentValue !== null) {
                $currentValue = round((float) $currentValue, 2);
            }

            if ($currentValue === $newValue) {
                continue;
            }

            $changes[$field] = [
                'from' => $currentValue,
                'to' => $newValue,
            ];
        }

        if (!$dryRun && !empty($changes)) {
            $examResult->forceFill($attributes);
            $examResult->save();
        }

        return [
            'updated' => !empty($changes),
            'changes' => $changes,
            'evaluation' => $evaluation,
        ];
    }

    public function syncResults(?int $studentId = null, ?int $courseId = null, ?int $resultId = null, bool $dryRun = false): array
    {
        $query = ExamResult::query()->with(['exam.examCategories', 'category']);

        if ($resultId !== null) {
            $query->whereKey($resultId);
        }

        if ($studentId !== null) {
            $query->where('student_id', $studentId);
        }

        if ($courseId !== null) {
            $query->whereHas('exam', function ($builder) use ($courseId) {
                $builder->where('course_id', $courseId);
            });
        }

        $summary = [
            'processed' => 0,
            'updated' => 0,
            'failed' => 0,
            'changes' => [],
        ];

        $query->orderBy('id')->chunkById(100, function (EloquentCollection $results) use (&$summary, $dryRun) {
            foreach ($results as $result) {
                $summary['processed']++;

                try {
                    $sync = $this->syncExamResult($result, $dryRun);

                    if (!$sync['updated']) {
                        continue;
                    }

                    $summary['updated']++;
                    $summary['changes'][] = [
                        'result_id' => $result->id,
                        'student_id' => $result->student_id,
                        'exam_id' => $result->exam_id,
                        'category_id' => $result->category_id,
                        'changes' => $sync['changes'],
                    ];
                } catch (\Throwable $exception) {
                    $summary['failed']++;
                    $summary['changes'][] = [
                        'result_id' => $result->id,
                        'student_id' => $result->student_id,
                        'exam_id' => $result->exam_id,
                        'category_id' => $result->category_id,
                        'error' => $exception->getMessage(),
                    ];
                }
            }
        });

        return $summary;
    }

    private function normalizeResultStatus(mixed $resultStatus): ?string
    {
        if ($resultStatus instanceof ExamResultStatusEnum) {
            return $resultStatus->value;
        }

        return $resultStatus;
    }
}
