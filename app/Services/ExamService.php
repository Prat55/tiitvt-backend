<?php

namespace App\Services;

use App\Enums\ExamStatusEnum;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\{Exam, Student, Course, Category, ExamCategory};

class ExamService
{
    /**
     * Schedule a new exam for a student
     */
    public function scheduleExam(array $data): Exam
    {
        return DB::transaction(function () use ($data) {
            $exam = Exam::create([
                'course_id' => $data['course_id'],
                'student_id' => $data['student_id'],
                'duration' => $data['duration'],
                'date' => $data['date'],
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
                'status' => ExamStatusEnum::SCHEDULED,
            ]);

            Log::info('Exam scheduled successfully', [
                'exam_id' => $exam->exam_id,
                'student_id' => $exam->student_id,
                'course_id' => $exam->course_id,
                'scheduled_date' => $exam->date,
                'scheduled_time' => $exam->start_time . ' - ' . $exam->end_time
            ]);

            return $exam;
        });
    }

    /**
     * Schedule a new exam for a student with specific categories
     */
    public function scheduleExamWithCategories(array $data): Exam
    {
        return DB::transaction(function () use ($data) {
            $exam = Exam::create([
                'course_id' => $data['course_id'],
                'student_id' => $data['student_id'],
                'duration' => $data['duration'],
                'date' => $data['date'],
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
                'status' => ExamStatusEnum::SCHEDULED,
            ]);

            // Attach categories to the exam
            if (!empty($data['category_ids'])) {
                foreach ($data['category_ids'] as $categoryId) {
                    ExamCategory::create([
                        'exam_id' => $exam->id,
                        'category_id' => $categoryId,
                    ]);
                }
            }

            Log::info('Exam scheduled successfully with categories', [
                'exam_id' => $exam->exam_id,
                'student_id' => $exam->student_id,
                'course_id' => $exam->course_id,
                'category_ids' => $data['category_ids'] ?? [],
                'scheduled_date' => $exam->date,
                'scheduled_time' => $exam->start_time . ' - ' . $exam->end_time
            ]);

            return $exam->load('student', 'examCategories.category');
        });
    }

    /**
     * Get completed categories for a course (categories where all students have completed exams)
     */
    public function getCompletedCategories(int $courseId): array
    {
        $course = Course::with(['students', 'categories'])->find($courseId);

        if (!$course || $course->students->isEmpty() || $course->categories->isEmpty()) {
            return [];
        }

        $completedCategories = [];

        foreach ($course->categories as $category) {
            $allStudentsCompleted = true;

            foreach ($course->students as $student) {
                // Check if this student has completed an exam for this category
                $hasCompletedExam = Exam::where('student_id', $student->id)
                    ->where('course_id', $courseId)
                    ->whereHas('examCategories', function ($query) use ($category) {
                        $query->where('category_id', $category->id);
                    })
                    ->where('status', ExamStatusEnum::COMPLETED)
                    ->exists();

                if (!$hasCompletedExam) {
                    $allStudentsCompleted = false;
                    break;
                }
            }

            if ($allStudentsCompleted) {
                $completedCategories[] = [
                    'id' => $category->id,
                    'name' => $category->name,
                ];
            }
        }

        return $completedCategories;
    }

    /**
     * Get available categories for a course (excluding completed ones)
     */
    public function getAvailableCategories(int $courseId): array
    {
        $course = Course::with('categories')->find($courseId);

        if (!$course) {
            return [];
        }

        $completedCategoryIds = collect($this->getCompletedCategories($courseId))->pluck('id')->toArray();

        return $course->categories
            ->whereNotIn('id', $completedCategoryIds)
            ->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                ];
            })
            ->toArray();
    }

    /**
     * Get student's course information
     */
    public function getStudentCourse(int $studentId): ?Course
    {
        $student = Student::with('course')->find($studentId);
        return $student?->course;
    }

    /**
     * Cancel overdue exams
     */
    public function cancelOverdueExams(): array
    {
        $overdueExams = Exam::overdue()->get();

        if ($overdueExams->isEmpty()) {
            return ['cancelled' => 0, 'failed' => 0];
        }

        $cancelledCount = 0;
        $failedCount = 0;

        foreach ($overdueExams as $exam) {
            try {
                $exam->update(['status' => ExamStatusEnum::CANCELLED]);
                $cancelledCount++;

                Log::info('Exam automatically cancelled', [
                    'exam_id' => $exam->exam_id,
                    'student_id' => $exam->student_id,
                    'course_id' => $exam->course_id,
                    'scheduled_date' => $exam->date,
                    'scheduled_end_time' => $exam->end_time,
                    'cancelled_at' => now(),
                    'reason' => 'Automatically cancelled due to being overdue'
                ]);
            } catch (\Exception $e) {
                $failedCount++;
                Log::error('Failed to cancel overdue exam', [
                    'exam_id' => $exam->exam_id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return [
            'cancelled' => $cancelledCount,
            'failed' => $failedCount
        ];
    }

    /**
     * Get exam statistics
     */
    public function getExamStats(): array
    {
        return [
            'total' => Exam::count(),
            'scheduled' => Exam::where('status', ExamStatusEnum::SCHEDULED)->count(),
            'completed' => Exam::where('status', ExamStatusEnum::COMPLETED)->count(),
            'cancelled' => Exam::where('status', ExamStatusEnum::CANCELLED)->count(),
            'overdue' => Exam::overdue()->count(),
        ];
    }

    /**
     * Validate exam schedule time conflicts
     */
    public function hasTimeConflict(int $studentId, string $date, string $startTime, string $endTime, ?int $excludeExamId = null): bool
    {
        $query = Exam::where('student_id', $studentId)
            ->where('date', $date)
            ->where('status', ExamStatusEnum::SCHEDULED)
            ->where(function ($q) use ($startTime, $endTime) {
                $q->whereBetween('start_time', [$startTime, $endTime])
                    ->orWhereBetween('end_time', [$startTime, $endTime])
                    ->orWhere(function ($q2) use ($startTime, $endTime) {
                        $q2->where('start_time', '<=', $startTime)
                            ->where('end_time', '>=', $endTime);
                    });
            });

        if ($excludeExamId) {
            $query->where('id', '!=', $excludeExamId);
        }

        return $query->exists();
    }

    /**
     * Get upcoming exams for a student
     */
    public function getUpcomingExams(int $studentId, int $limit = 5): \Illuminate\Database\Eloquent\Collection
    {
        return Exam::where('student_id', $studentId)
            ->where('status', ExamStatusEnum::SCHEDULED)
            ->where('date', '>=', now()->toDateString())
            ->orderBy('date')
            ->orderBy('start_time')
            ->limit($limit)
            ->get();
    }
}
