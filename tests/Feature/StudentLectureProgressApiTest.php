<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\StudentApiController;
use App\Models\Category;
use App\Models\Center;
use App\Models\Course;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class StudentLectureProgressApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_upsert_lecture_progress_creates_and_returns_progress_entry(): void
    {
        [$student, $course, $category] = $this->makeStudentContext();

        $request = Request::create(route('api.student.lecture-progress.upsert', ['lectureKey' => '1-1-demo']), 'PUT', [
            'course_id' => $course->id,
            'category_id' => $category->id,
            'lecture_title' => 'Introduction',
            'duration_seconds' => 1200.5,
            'position_seconds' => 300.25,
            'watched_seconds' => 320.75,
            'is_completed' => false,
        ]);
        $request->setUserResolver(static fn () => $student);

        $response = app(StudentApiController::class)->upsertLectureProgress($request, '1-1-demo');
        $payload = $response->getData(true)['data'];

        $this->assertSame('1-1-demo', $payload['lecture_key']);
        $this->assertSame($course->id, $payload['course_id']);
        $this->assertSame($category->id, $payload['category_id']);
        $this->assertSame('Introduction', $payload['lecture_title']);
        $this->assertSame(1200.5, $payload['duration_seconds']);
        $this->assertSame(300.25, $payload['position_seconds']);
        $this->assertSame(320.75, $payload['watched_seconds']);
        $this->assertFalse($payload['is_completed']);
        $this->assertNull($payload['completed_at']);
        $this->assertNotNull($payload['last_watched_at']);

        $this->assertDatabaseHas('student_lecture_progress', [
            'student_id' => $student->id,
            'lecture_key' => '1-1-demo',
            'course_id' => $course->id,
            'category_id' => $category->id,
            'lecture_title' => 'Introduction',
            'is_completed' => 0,
        ]);
    }

    public function test_upsert_marks_progress_complete_at_ninety_percent_and_keeps_completion_sticky(): void
    {
        [$student] = $this->makeStudentContext();
        $controller = app(StudentApiController::class);

        $firstRequest = Request::create('/api/student/lecture-progress/1-1-demo', 'PUT', [
            'duration_seconds' => 100,
            'position_seconds' => 95,
            'watched_seconds' => 95,
        ]);
        $firstRequest->setUserResolver(static fn () => $student);

        $firstPayload = $controller->upsertLectureProgress($firstRequest, '1-1-demo')->getData(true)['data'];
        $this->assertTrue($firstPayload['is_completed']);
        $this->assertNotNull($firstPayload['completed_at']);

        $secondRequest = Request::create('/api/student/lecture-progress/1-1-demo', 'PUT', [
            'duration_seconds' => 100,
            'position_seconds' => 40,
            'watched_seconds' => 96,
            'is_completed' => false,
        ]);
        $secondRequest->setUserResolver(static fn () => $student);

        $secondPayload = $controller->upsertLectureProgress($secondRequest, '1-1-demo')->getData(true)['data'];
        $this->assertTrue($secondPayload['is_completed']);
        $this->assertNotNull($secondPayload['completed_at']);
        $this->assertEquals(96.0, $secondPayload['watched_seconds']);
    }

    public function test_lecture_progress_endpoint_returns_student_entries_in_response(): void
    {
        [$student] = $this->makeStudentContext();
        $controller = app(StudentApiController::class);

        $updateRequest = Request::create('/api/student/lecture-progress/1-1-demo', 'PUT', [
            'lecture_title' => 'Introduction',
            'duration_seconds' => 600,
            'position_seconds' => 120,
            'watched_seconds' => 180,
        ]);
        $updateRequest->setUserResolver(static fn () => $student);
        $controller->upsertLectureProgress($updateRequest, '1-1-demo');

        $listRequest = Request::create(route('api.student.lecture-progress.index'), 'GET');
        $listRequest->setUserResolver(static fn () => $student);

        $payload = $controller->lectureProgress($listRequest)->getData(true);

        $this->assertCount(1, $payload['data']);
        $this->assertSame('1-1-demo', $payload['data'][0]['lecture_key']);
        $this->assertSame('Introduction', $payload['data'][0]['lecture_title']);
        $this->assertEquals(120.0, $payload['data'][0]['position_seconds']);
    }

    private function makeStudentContext(): array
    {
        $user = User::factory()->create();
        $center = Center::create([
            'user_id' => $user->id,
            'name' => 'Progress Center',
            'status' => 'active',
        ]);

        $course = Course::create([
            'name' => 'C Programming',
            'slug' => 'c-programming',
        ]);

        $category = Category::create([
            'name' => 'Introduction',
            'slug' => 'introduction',
        ]);

        $student = new Student([
            'first_name' => 'Progress',
            'fathers_name' => 'Student',
            'email' => fake()->unique()->safeEmail(),
            'course_fees' => 1000,
            'down_payment' => 0,
            'date_of_birth' => '2000-01-01',
        ]);
        $student->center_id = $center->id;
        $student->save();
        $student->courses()->attach($course, [
            'enrollment_date' => '2026-03-11',
        ]);

        return [$student, $course, $category];
    }
}
