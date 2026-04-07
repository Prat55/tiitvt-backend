<?php

namespace Tests\Unit;

use App\Enums\ExamResultStatusEnum;
use App\Models\Category;
use App\Models\Course;
use App\Models\Exam;
use App\Models\ExamCategory;
use App\Models\ExamResult;
use App\Services\ExamResultSyncService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Tests\TestCase;

class ExamResultSyncServiceTest extends TestCase
{
    public function test_course_summary_marks_course_passed_when_all_categories_pass(): void
    {
        $service = new ExamResultSyncService();
        $course = new Course(['id' => 11, 'name' => 'Sample Course']);
        $exam = new Exam(['id' => 21, 'course_id' => 11]);
        $exam->setRelation('course', $course);
        $exam->setRelation('examCategories', new EloquentCollection([
            new ExamCategory(['category_id' => 1, 'passing_points' => 49, 'total_points' => 100]),
            new ExamCategory(['category_id' => 2, 'passing_points' => 49, 'total_points' => 100]),
            new ExamCategory(['category_id' => 3, 'passing_points' => 49, 'total_points' => 100]),
            new ExamCategory(['category_id' => 4, 'passing_points' => 49, 'total_points' => 100]),
        ]));

        $results = new EloquentCollection([
            $this->makeExamResult($exam, 1, 'Theory', 49),
            $this->makeExamResult($exam, 2, 'Practical', 49),
            $this->makeExamResult($exam, 3, 'Project', 49),
            $this->makeExamResult($exam, 4, 'Viva', 49),
        ]);

        $summary = $service->summarizeCourseResults($results);

        $this->assertSame(400, $summary['total_marks']);
        $this->assertSame(196, $summary['total_marks_obtained']);
        $this->assertSame(49.0, $summary['overall_percentage']);
        $this->assertTrue($summary['is_passed']);
        $this->assertSame('PASS', $summary['overall_result']);
    }

    public function test_sync_exam_result_dry_run_detects_stale_result_fields(): void
    {
        $service = new ExamResultSyncService();
        $course = new Course(['id' => 11, 'name' => 'Sample Course']);
        $exam = new Exam(['id' => 21, 'course_id' => 11]);
        $exam->setRelation('course', $course);
        $exam->setRelation('examCategories', new EloquentCollection([
            new ExamCategory(['category_id' => 5, 'passing_points' => 50, 'total_points' => 100]),
        ]));

        $result = new ExamResult([
            'id' => 88,
            'exam_id' => 21,
            'student_id' => 4,
            'category_id' => 5,
            'total_points' => 100,
            'points_earned' => 60,
            'percentage' => 45,
            'result' => 'failed',
            'result_status' => ExamResultStatusEnum::Failed->value,
        ]);
        $result->setRelation('exam', $exam);
        $result->setRelation('category', new Category(['id' => 5, 'name' => 'Theory']));

        $sync = $service->syncExamResult($result, true);

        $this->assertTrue($sync['updated']);
        $this->assertSame('passed', $sync['changes']['result']['to']);
        $this->assertSame(60.0, $sync['changes']['percentage']['to']);
        $this->assertSame(ExamResultStatusEnum::Passed->value, $sync['changes']['result_status']['to']);
    }

    public function test_course_summary_can_order_subjects_by_submission_sequence(): void
    {
        $service = new ExamResultSyncService();
        $course = new Course(['id' => 15, 'name' => 'Full Stack Course']);
        $exam = new Exam(['id' => 41, 'course_id' => 15]);
        $exam->setRelation('course', $course);
        $exam->setRelation('examCategories', new EloquentCollection([
            new ExamCategory(['category_id' => 1, 'passing_points' => 40, 'total_points' => 100]),
            new ExamCategory(['category_id' => 2, 'passing_points' => 40, 'total_points' => 100]),
            new ExamCategory(['category_id' => 3, 'passing_points' => 40, 'total_points' => 100]),
        ]));

        $results = new EloquentCollection([
            $this->makeExamResult($exam, 1, 'PHP', 60, '2026-04-01 11:00:00'),
            $this->makeExamResult($exam, 2, 'HTML', 75, '2026-04-01 09:00:00'),
            $this->makeExamResult($exam, 1, 'PHP', 80, '2026-04-02 10:00:00'),
            $this->makeExamResult($exam, 3, 'CSS', 70, '2026-04-03 08:30:00'),
        ]);

        $summary = $service->summarizeCourseResults($results, 'submission_asc');

        $this->assertSame(['HTML', 'PHP', 'CSS'], array_column($summary['subjects'], 'name'));
        $this->assertSame([75, 80, 70], array_column($summary['subjects'], 'obtained'));
    }

    private function makeExamResult(Exam $exam, int $categoryId, string $categoryName, int $pointsEarned, ?string $submittedAt = null): ExamResult
    {
        $result = new ExamResult([
            'exam_id' => $exam->id,
            'student_id' => 1,
            'category_id' => $categoryId,
            'total_points' => 100,
            'points_earned' => $pointsEarned,
            'score' => $pointsEarned,
            'submitted_at' => $submittedAt ? \Carbon\Carbon::parse($submittedAt) : now(),
        ]);

        $result->setRelation('exam', $exam);
        $result->setRelation('category', new Category(['id' => $categoryId, 'name' => $categoryName]));

        return $result;
    }
}
