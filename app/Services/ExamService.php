<?php

namespace App\Services;

use App\Enums\ExamStatusEnum;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\{Exam, Student, Course, Category, ExamCategory, ExamStudent};

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
                'duration' => $data['duration'],
                'date' => $data['date'],
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
                'status' => ExamStatusEnum::SCHEDULED,
            ]);

            Log::info('Exam scheduled successfully', [
                'exam_id' => $exam->id,
                'course_id' => $exam->course_id,
                'scheduled_date' => $exam->date,
                'scheduled_time' => $exam->start_time . ' - ' . $exam->end_time
            ]);

            return $exam;
        });
    }

    /**
     * Schedule a new exam for multiple students with specific categories
     */
    public function scheduleExamWithCategories(array $data): Exam
    {
        return DB::transaction(function () use ($data) {
            // Create one exam for the course and categories
            $exam = Exam::create([
                'course_id' => $data['course_id'],
                'duration' => $data['duration'],
                'date' => $data['date'],
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
                'status' => ExamStatusEnum::SCHEDULED,
            ]);

            // Attach categories to the exam with points
            if (!empty($data['category_data'])) {
                foreach ($data['category_data'] as $categoryData) {
                    ExamCategory::create([
                        'exam_id' => $exam->id,
                        'category_id' => $categoryData['category_id'],
                        'total_points' => $categoryData['total_points'],
                        'passing_points' => $categoryData['passing_points'],
                    ]);
                }
            } elseif (!empty($data['category_ids'])) {
                // Fallback for backward compatibility
                foreach ($data['category_ids'] as $categoryId) {
                    ExamCategory::create([
                        'exam_id' => $exam->id,
                        'category_id' => $categoryId,
                        'total_points' => 0,
                        'passing_points' => 0,
                    ]);
                }
            }

            // Create ExamStudent records for each student with individual credentials
            if (!empty($data['student_ids'])) {
                foreach ($data['student_ids'] as $studentId) {
                    ExamStudent::create([
                        'exam_id' => $exam->id,
                        'student_id' => $studentId,
                        'exam_user_id' => ExamStudent::generateUniqueExamUserId(),
                        'exam_password' => ExamStudent::generatePassword(),
                    ]);
                }
            }

            Log::info('Exam scheduled successfully with categories and students', [
                'exam_id' => $exam->id,
                'course_id' => $exam->course_id,
                'category_data' => $data['category_data'] ?? $data['category_ids'] ?? [],
                'student_ids' => $data['student_ids'] ?? [],
                'scheduled_date' => $exam->date,
                'scheduled_time' => $exam->start_time . ' - ' . $exam->end_time
            ]);

            return $exam->load('examStudents.student', 'examCategories.category');
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
                $hasCompletedExam = Exam::where('course_id', $courseId)
                    ->whereHas('examCategories', function ($query) use ($category) {
                        $query->where('category_id', $category->id);
                    })
                    ->whereHas('examStudents', function ($query) use ($student) {
                        $query->where('student_id', $student->id);
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
        $query = Exam::where('date', $date)
            ->where('status', ExamStatusEnum::SCHEDULED)
            ->whereHas('examStudents', function ($q) use ($studentId) {
                $q->where('student_id', $studentId);
            })
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
        return Exam::where('status', ExamStatusEnum::SCHEDULED)
            ->where('date', '>=', now()->toDateString())
            ->whereHas('examStudents', function ($query) use ($studentId) {
                $query->where('student_id', $studentId);
            })
            ->orderBy('date')
            ->orderBy('start_time')
            ->limit($limit)
            ->get();
    }
}
