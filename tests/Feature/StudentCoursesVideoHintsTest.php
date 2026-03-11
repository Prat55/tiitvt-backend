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
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StudentCoursesVideoHintsTest extends TestCase
{
    use RefreshDatabase;

    public function test_courses_endpoint_returns_video_range_hints_from_mp4_atoms(): void
    {
        Storage::fake('public');

        $videoPath = 'lectures/c/intro.mp4';
        $ftyp = $this->mp4Box('ftyp', 'isom0000');
        $moov = $this->mp4Box('moov', str_repeat('m', 32));
        $free = $this->mp4Box('free', str_repeat('f', 64));
        $mdat = $this->mp4Box('mdat', str_repeat('d', 128));
        $binary = $ftyp . $moov . $free . $mdat;

        Storage::disk('public')->put($videoPath, $binary);

        $user = User::factory()->create();
        $center = Center::create([
            'user_id' => $user->id,
            'name' => 'Test Center',
            'status' => 'active',
        ]);

        $course = Course::create([
            'name' => 'C Programming',
            'slug' => 'c-programming',
        ]);

        $category = Category::create([
            'name' => 'Introduction',
            'slug' => 'introduction',
            'lectures' => [[
                'title' => 'Intro',
                'path' => $videoPath,
                'description' => 'Lesson',
            ]],
        ]);

        $course->categories()->attach($category);

        $student = new Student([
            'first_name' => 'Test',
            'fathers_name' => 'Student',
            'email' => 'student@example.com',
            'course_fees' => 1000,
            'down_payment' => 0,
            'date_of_birth' => '2000-01-01',
        ]);
        $student->center_id = $center->id;
        $student->save();

        $student->courses()->attach($course, [
            'enrollment_date' => '2026-03-11',
        ]);

        $request = Request::create(route('api.student.courses.index'), 'GET');
        $request->setUserResolver(static fn () => $student);

        $response = app(StudentApiController::class)->courses($request);
        $payload = $response->getData(true);
        $lecture = $payload['data'][0]['categories'][0]['lectures'][0];
        $expectedFileSize = strlen($binary);
        $expectedMoovOffset = strlen($ftyp);
        $expectedMdatAtomOffset = strlen($ftyp . $moov . $free);
        $expectedMediaStartByte = $expectedMdatAtomOffset + 8;
        $expectedEndByte = $expectedFileSize - 1;
        $expectedLectureKey = sprintf('%d-%d-%s', $course->id, $category->id, sha1("path:{$videoPath}"));

        $this->assertSame(0, $lecture['start_byte']);
        $this->assertSame($expectedLectureKey, $lecture['lecture_key']);
        $this->assertSame($expectedEndByte, $lecture['end_byte']);
        $this->assertSame($expectedFileSize, $lecture['file_size']);
        $this->assertSame($expectedMoovOffset, $lecture['moov_atom_offset']);
        $this->assertSame($expectedMdatAtomOffset, $lecture['media_data_atom_offset']);
        $this->assertSame($expectedMediaStartByte, $lecture['media_start_byte']);
        $this->assertSame($expectedEndByte, $lecture['suggested_initial_end_byte']);
        $this->assertSame("bytes=0-{$expectedEndByte}", $lecture['suggested_initial_range']);
    }

    private function mp4Box(string $type, string $payload): string
    {
        return pack('N', 8 + strlen($payload)) . $type . $payload;
    }
}
