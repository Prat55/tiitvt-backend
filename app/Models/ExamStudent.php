<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class ExamStudent extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_id',
        'student_id',
        'exam_user_id',
        'exam_password',
        'answers',
    ];

    protected $casts = [
        'answers' => 'array',
    ];

    protected $appends = [
        'is_completed',
        'completion_status',
    ];

    // =========================================================================
    // BOOT METHOD
    // =========================================================================

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (ExamStudent $examStudent): void {
            if (empty($examStudent->exam_user_id)) {
                $examStudent->exam_user_id = self::generateUniqueExamUserId();
            }
            if (empty($examStudent->exam_password)) {
                $examStudent->exam_password = self::generatePassword();
            }
        });
    }

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    /**
     * Get the exam that owns the exam student.
     */
    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    /**
     * Get the student that owns the exam student.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Get the exam result for this student.
     */
    public function examResult(): BelongsTo
    {
        return $this->belongsTo(ExamResult::class, 'student_id', 'student_id')
            ->where('exam_id', $this->exam_id);
    }

    /**
     * Get all exam results for this student.
     */
    public function examResults(): HasMany
    {
        return $this->hasMany(ExamResult::class, 'student_id', 'student_id');
    }

    // =========================================================================
    // ACCESSORS
    // =========================================================================

    /**
     * Check if the exam is completed by this student.
     */
    public function getIsCompletedAttribute(): bool
    {
        return $this->examResult && $this->examResult->result_status !== \App\Enums\ExamResultStatusEnum::NotDeclared;
    }

    /**
     * Get the completion status of the exam.
     */
    public function getCompletionStatusAttribute(): string
    {
        if (!$this->examResult) {
            return 'Not Started';
        }

        return $this->examResult->result_status;
    }

    // =========================================================================
    // METHODS
    // =========================================================================

    /**
     * Generate a unique exam user ID.
     */
    public static function generateUniqueExamUserId(): string
    {
        do {
            $examUserId = 'STUDENT' . strtoupper(Str::random(8));
        } while (self::where('exam_user_id', $examUserId)->exists());

        return $examUserId;
    }

    /**
     * Generate a random password for exam access.
     */
    public static function generatePassword(): string
    {
        return '12345';
    }

    /**
     * Submit answers for the exam.
     */
    public function submitAnswers(array $answers): bool
    {
        return $this->update(['answers' => $answers]);
    }

    /**
     * Check if the student has submitted answers.
     */
    public function hasSubmittedAnswers(): bool
    {
        return !empty($this->answers);
    }

    /**
     * Get the submitted answers count.
     */
    public function getSubmittedAnswersCount(): int
    {
        return is_array($this->answers) ? count($this->answers) : 0;
    }

    /**
     * Check if the exam is accessible for this student.
     */
    public function isExamAccessible(): bool
    {
        $exam = $this->exam;

        if (!$exam) {
            return false;
        }

        $now = now();
        $examDate = $exam->date;
        $startTime = $exam->start_time;
        $endTime = $exam->end_time;

        // Check if exam is today and within time range
        if ($examDate->isToday()) {
            $currentTime = $now->format('H:i:s');
            return $currentTime >= $startTime->format('H:i:s') &&
                $currentTime <= $endTime->format('H:i:s');
        }

        return false;
    }

    /**
     * Get the remaining time for the exam.
     */
    public function getRemainingTime(): ?int
    {
        $exam = $this->exam;

        if (!$exam || !$this->isExamAccessible()) {
            return null;
        }

        $now = now();
        $endDateTime = $exam->date->setTimeFrom($exam->end_time);

        return max(0, $endDateTime->diffInSeconds($now));
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

    /**
     * Scope to get completed exam enrollments.
     */
    public function scopeCompleted($query)
    {
        return $query->whereHas('examResult', function ($q) {
            $q->whereNotIn('result_status', [\App\Enums\ExamResultStatusEnum::NotDeclared]);
        });
    }

    /**
     * Scope to get pending exam enrollments.
     */
    public function scopePending($query)
    {
        return $query->whereDoesntHave('examResult')
            ->orWhereHas('examResult', function ($q) {
                $q->where('result_status', \App\Enums\ExamResultStatusEnum::NotDeclared);
            });
    }

    /**
     * Scope to get enrollments by exam.
     */
    public function scopeByExam($query, int $examId)
    {
        return $query->where('exam_id', $examId);
    }

    /**
     * Scope to get enrollments by student.
     */
    public function scopeByStudent($query, int $studentId)
    {
        return $query->where('student_id', $studentId);
    }
}
