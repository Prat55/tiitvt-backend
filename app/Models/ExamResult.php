<?php

namespace App\Models;

use App\Enums\ExamResultStatusEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;

class ExamResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_id',
        'student_id',
        'score',
        'declared_by',
        'category_id',
        'category_slug',
        'answers_data',
        'total_questions',
        'answered_questions',
        'skipped_questions',
        'total_points',
        'points_earned',
        'percentage',
        'result',
        'exam_duration',
        'time_taken_minutes',
        'submitted_at',
    ];

    protected $casts = [
        'answers_data' => 'array',
        'percentage' => 'float',
        'time_taken_minutes' => 'float',
        'submitted_at' => 'datetime',
    ];

    protected $appends = [
        'grade',
        'is_passed',
        'performance_level',
    ];

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    /**
     * Get the student that owns the exam result.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Get the exam that owns the exam result.
     */
    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    /**
     * Get the category that owns the exam result.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Check if a student has completed an exam category.
     */
    public static function isCategoryCompleted(int $examId, int $studentId, int $categoryId): bool
    {
        return static::where('exam_id', $examId)
            ->where('student_id', $studentId)
            ->where('category_id', $categoryId)
            ->exists();
    }

    /**
     * Get the user who declared the result.
     */
    public function declaredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'declared_by');
    }

    /**
     * Get the exam student enrollment for this result.
     */
    public function examStudent(): BelongsTo
    {
        return $this->belongsTo(ExamStudent::class, 'student_id', 'student_id')
            ->where('exam_id', $this->exam_id);
    }

    // =========================================================================
    // ACCESSORS
    // =========================================================================

    /**
     * Get the grade based on percentage.
     */
    public function getGradeAttribute(): string
    {
        if ($this->percentage >= 90) return 'A+';
        if ($this->percentage >= 80) return 'A';
        if ($this->percentage >= 70) return 'B+';
        if ($this->percentage >= 60) return 'B';
        if ($this->percentage >= 50) return 'C+';
        if ($this->percentage >= 40) return 'C';
        if ($this->percentage >= 30) return 'D';
        return 'F';
    }

    /**
     * Check if the student passed the exam.
     */
    public function getIsPassedAttribute(): bool
    {
        return $this->result === 'passed';
    }

    /**
     * Get the performance level description.
     */
    public function getPerformanceLevelAttribute(): string
    {
        if ($this->percentage >= 90) return 'Excellent';
        if ($this->percentage >= 80) return 'Very Good';
        if ($this->percentage >= 70) return 'Good';
        if ($this->percentage >= 60) return 'Above Average';
        if ($this->percentage >= 50) return 'Average';
        if ($this->percentage >= 40) return 'Below Average';
        return 'Poor';
    }

    // =========================================================================
    // METHODS
    // =========================================================================

    /**
     * Declare the exam result.
     */
    public function declareResult(int $declaredBy, ?string $declaredAt = null): bool
    {
        return $this->update([
            'result_status' => ExamResultStatusEnum::Passed,
            'declared_by' => $declaredBy,
            'declared_at' => $declaredAt ?? now(),
        ]);
    }

    /**
     * Undeclare the exam result.
     */
    public function undeclareResult(): bool
    {
        return $this->update([
            'result_status' => ExamResultStatusEnum::NotDeclared,
            'declared_at' => null,
        ]);
    }

    /**
     * Check if the result is declared.
     */
    public function isDeclared(): bool
    {
        return $this->result_status === ExamResultStatusEnum::Passed || $this->result_status === ExamResultStatusEnum::Failed;
    }

    /**
     * Check if the result is pending.
     */
    public function isPending(): bool
    {
        return $this->result_status === ExamResultStatusEnum::NotDeclared;
    }

    /**
     * Get the score percentage.
     */
    public function getScorePercentage(): float
    {
        return round(($this->score / 100) * 100, 2);
    }

    /**
     * Get the score out of total marks.
     */
    public function getScoreOutOf(int $totalMarks = 100): string
    {
        return "{$this->score}/{$totalMarks}";
    }

    /**
     * Check if the score is in a specific range.
     */
    public function isScoreInRange(float $min, float $max): bool
    {
        return $this->score >= $min && $this->score <= $max;
    }

    /**
     * Get the time taken for the exam (if available in data).
     */
    public function getTimeTaken(): ?int
    {
        return $this->data['time_taken'] ?? null;
    }

    /**
     * Get the number of correct answers (if available in data).
     */
    public function getCorrectAnswersCount(): ?int
    {
        return $this->data['correct_answers'] ?? null;
    }

    /**
     * Get the number of total questions (if available in data).
     */
    public function getTotalQuestionsCount(): ?int
    {
        return $this->data['total_questions'] ?? null;
    }

    /**
     * Calculate accuracy percentage.
     */
    public function getAccuracyPercentage(): ?float
    {
        $correct = $this->getCorrectAnswersCount();
        $total = $this->getTotalQuestionsCount();

        if ($correct === null || $total === null || $total === 0) {
            return null;
        }

        return round(($correct / $total) * 100, 2);
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

    /**
     * Scope to get declared results.
     */
    public function scopeDeclared(Builder $query): Builder
    {
        return $query->whereIn('result_status', [ExamResultStatusEnum::Passed, ExamResultStatusEnum::Failed]);
    }

    /**
     * Scope to get pending results.
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('result_status', ExamResultStatusEnum::NotDeclared);
    }

    /**
     * Scope to get passed results.
     */
    public function scopePassed(Builder $query): Builder
    {
        return $query->where('score', '>=', 40);
    }

    /**
     * Scope to get failed results.
     */
    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('score', '<', 40);
    }

    /**
     * Scope to get results by score range.
     */
    public function scopeByScoreRange(Builder $query, float $min, float $max): Builder
    {
        return $query->whereBetween('score', [$min, $max]);
    }

    /**
     * Scope to get results by exam.
     */
    public function scopeByExam(Builder $query, int $examId): Builder
    {
        return $query->where('exam_id', $examId);
    }

    /**
     * Scope to get results by student.
     */
    public function scopeByStudent(Builder $query, int $studentId): Builder
    {
        return $query->where('student_id', $studentId);
    }

    /**
     * Scope to get results by grade.
     */
    public function scopeByGrade(Builder $query, string $grade): Builder
    {
        $scoreRanges = [
            'A+' => [90, 100],
            'A' => [80, 89.99],
            'B+' => [70, 79.99],
            'B' => [60, 69.99],
            'C+' => [50, 59.99],
            'C' => [40, 49.99],
            'D' => [30, 39.99],
            'F' => [0, 29.99],
        ];

        if (isset($scoreRanges[$grade])) {
            [$min, $max] = $scoreRanges[$grade];
            return $query->byScoreRange($min, $max);
        }

        return $query;
    }

    /**
     * Scope to get top performing results.
     */
    public function scopeTopPerformers(Builder $query, int $limit = 10): Builder
    {
        return $query->orderBy('score', 'desc')->limit($limit);
    }

    /**
     * Scope to get results declared by specific user.
     */
    public function scopeDeclaredBy(Builder $query, int $userId): Builder
    {
        return $query->where('declared_by', $userId);
    }

    /**
     * Scope to get results declared within date range.
     */
    public function scopeDeclaredBetween(Builder $query, string $startDate, string $endDate): Builder
    {
        return $query->whereBetween('declared_at', [$startDate, $endDate]);
    }
}
