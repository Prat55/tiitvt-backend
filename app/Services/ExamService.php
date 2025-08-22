<?php

namespace App\Services;

use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\Question;
use App\Models\Student;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ExamService
{
    /**
     * Create a new exam with questions.
     */
    public function createExam(array $data): Exam
    {
        return DB::transaction(function () use ($data) {
            $exam = Exam::create([
                'course_id' => $data['course_id'],
                'title' => $data['title'],
                'duration' => $data['duration'],
                'is_active' => $data['is_active'] ?? true,
            ]);

            // Create questions for the exam
            foreach ($data['questions'] as $questionData) {
                $question = Question::create([
                    'exam_id' => $exam->id,
                    'question_text' => $questionData['question_text'],
                    'points' => $questionData['points'] ?? 1,
                ]);

                // Create options for the question
                foreach ($questionData['options'] as $index => $optionText) {
                    $option = $question->options()->create([
                        'option_text' => $optionText,
                        'order_by' => $index + 1, // Set order_by based on position
                    ]);

                    // If this is the correct option, update the question with the option ID
                    if ($index == $questionData['correct_option_index']) {
                        $question->update(['correct_option_id' => $option->id]);
                    }
                }
            }

            return $exam->load('questions');
        });
    }

    /**
     * Evaluate exam results for a student.
     */
    public function evaluateExam(Student $student, Exam $exam, array $answers): ExamResult
    {
        $questions = $exam->questions->load('options');
        $totalPoints = $questions->sum('points');
        $earnedPoints = 0;

        foreach ($answers as $questionId => $selectedOptionText) {
            $question = $questions->find($questionId);
            if ($question && $question->correctOption && $question->correctOption->option_text === $selectedOptionText) {
                $earnedPoints += $question->points;
            }
        }

        $score = ($earnedPoints / $totalPoints) * 100;
        $resultStatus = $score >= 70 ? 'pass' : 'fail'; // Assuming 70% is passing

        return ExamResult::create([
            'student_id' => $student->id,
            'exam_id' => $exam->id,
            'score' => $score,
            'result_status' => $resultStatus,
            'declared_by' => Auth::id(),
            'declared_at' => now(),
            'answers' => $answers,
        ]);
    }

    /**
     * Get exam results for a student.
     */
    public function getStudentExamResults(Student $student): \Illuminate\Database\Eloquent\Collection
    {
        return $student->examResults()->with(['exam', 'declaredBy'])->get();
    }

    /**
     * Get exam results for a specific exam.
     */
    public function getExamResults(Exam $exam): \Illuminate\Database\Eloquent\Collection
    {
        return $exam->examResults()->with(['student', 'declaredBy'])->get();
    }

    /**
     * Get exam statistics.
     */
    public function getExamStatistics(Exam $exam): array
    {
        $results = $exam->examResults;

        return [
            'total_students' => $results->count(),
            'passed_students' => $results->where('result_status', 'pass')->count(),
            'failed_students' => $results->where('result_status', 'fail')->count(),
            'average_score' => $results->avg('score'),
            'highest_score' => $results->max('score'),
            'lowest_score' => $results->min('score'),
        ];
    }

    /**
     * Activate or deactivate an exam.
     */
    public function toggleExamStatus(Exam $exam): bool
    {
        $exam->update(['is_active' => !$exam->is_active]);
        return $exam->is_active;
    }
}
