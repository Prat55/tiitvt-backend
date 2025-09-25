<?php

namespace App\Models;

use App\Enums\ExamStatusEnum;
use App\Enums\ExamResultStatusEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;

class Exam extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_id',
        'center_id',
        'course_id',
        'duration',
        'date',
        'start_time',
        'end_time',
        'status',
    ];

    protected $casts = [
        'duration' => 'integer',
        'status' => ExamStatusEnum::class,
        'date' => 'date',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    protected $appends = [
        'category_names',
        'exam_statistics',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Exam $exam): void {
            if (empty($exam->exam_id)) {
                $exam->exam_id = self::generateUniqueExamId();
            }
        });
    }

    /**
     * Get the course that owns the exam.
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Get the center that owns the exam.
     */
    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    /**
     * Get the exam student enrollments.
     */
    public function examStudents(): HasMany
    {
        return $this->hasMany(ExamStudent::class);
    }

    /**
     * Get the students who are enrolled in this exam.
     */
    public function enrolledStudents(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'exam_students')
            ->withPivot(['exam_user_id', 'exam_password', 'answers'])
            ->withTimestamps();
    }

    /**
     * Get the exam categories for the exam.
     */
    public function examCategories(): HasMany
    {
        return $this->hasMany(ExamCategory::class);
    }

    /**
     * Get the categories for the exam.
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'exam_categories');
    }

    /**
     * Get the questions for the exam.
     */
    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }

    /**
     * Get the exam results for the exam.
     */
    public function examResults(): HasMany
    {
        return $this->hasMany(ExamResult::class);
    }

    /**
     * Get the students with their exam results for this exam.
     */
    public function studentsWithResults(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'exam_results')
            ->withPivot(['score', 'result_status', 'declared_at', 'data'])
            ->withTimestamps();
    }

    /**
     * Get the students who have completed this exam.
     */
    public function completedStudents(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'exam_results')
            ->wherePivot('result_status', '!=', 'NotDeclared')
            ->withPivot(['score', 'result_status', 'declared_at', 'data'])
            ->withTimestamps();
    }

    // =========================================================================
    // ACCESSORS
    // =========================================================================

    /**
     * Get the category names for the exam.
     */
    public function getCategoryNamesAttribute(): array
    {
        return $this->examCategories()
            ->with('category')
            ->get()
            ->pluck('category.name')
            ->filter()
            ->toArray();
    }


    /**
     * Get exam statistics.
     */
    public function getExamStatisticsAttribute(): array
    {
        $results = $this->examResults()
            ->whereNotIn('result_status', [ExamResultStatusEnum::NotDeclared])
            ->get();

        if ($results->isEmpty()) {
            return $this->getEmptyStatistics();
        }

        $scores = $results->pluck('score')->filter();

        return [
            'total_students' => $this->enrolled_students_count ?? 0,
            'completed_students' => $results->count(),
            'average_score' => round($scores->avg(), 2),
            'pass_rate' => $this->calculatePassRate($scores),
            'highest_score' => $scores->max(),
            'lowest_score' => $scores->min(),
        ];
    }

    // =========================================================================
    // METHODS
    // =========================================================================


    /**
     * Check if a student is enrolled in this exam.
     */
    public function isStudentEnrolled(int $studentId): bool
    {
        return $this->examStudents()
            ->where('student_id', $studentId)
            ->exists();
    }

    /**
     * Check if a student has completed this exam.
     */
    public function hasStudentCompleted(int $studentId): bool
    {
        return $this->examResults()
            ->where('student_id', $studentId)
            ->whereNotIn('result_status', [ExamResultStatusEnum::NotDeclared])
            ->exists();
    }

    /**
     * Get a specific student's exam result.
     */
    public function getStudentResult(int $studentId): ?ExamResult
    {
        return $this->examResults()
            ->where('student_id', $studentId)
            ->first();
    }

    /**
     * Enroll a student in this exam.
     */
    public function enrollStudent(int $studentId): ExamStudent
    {
        if ($this->isStudentEnrolled($studentId)) {
            throw new \Exception('Student is already enrolled in this exam');
        }

        return $this->examStudents()->create([
            'student_id' => $studentId,
        ]);
    }

    /**
     * Remove a student from this exam.
     */
    public function removeStudent(int $studentId): bool
    {
        return $this->examStudents()
            ->where('student_id', $studentId)
            ->delete();
    }

    /**
     * Get results with student information.
     */
    public function resultsWithStudents(): Collection
    {
        return $this->examResults()->with('student')->get();
    }

    // =========================================================================
    // PRIVATE METHODS
    // =========================================================================

    /**
     * Get empty statistics array.
     */
    private function getEmptyStatistics(): array
    {
        return [
            'total_students' => 0,
            'completed_students' => 0,
            'average_score' => 0,
            'pass_rate' => 0,
            'highest_score' => 0,
            'lowest_score' => 0,
        ];
    }

    /**
     * Calculate pass rate percentage.
     */
    private function calculatePassRate(Collection $scores): float
    {
        if ($scores->isEmpty()) {
            return 0.0;
        }

        $passingScores = $scores->filter(fn($score) => $score >= 40)->count();
        return round(($passingScores / $scores->count()) * 100, 2);
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

    /**
     * Scope to get only active exams.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get scheduled exams that are past their end time.
     */
    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where('status', ExamStatusEnum::SCHEDULED)
            ->where('date', '<=', now()->toDateString())
            ->where('end_time', '<', now()->format('H:i:s'));
    }

    /**
     * Scope to search exams by course name.
     */
    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->whereHas('course', function (Builder $q) use ($search) {
            $q->search($search);
        });
    }

    /**
     * Scope to get exams with student counts and results.
     */
    public function scopeWithStudentCounts(Builder $query): Builder
    {
        return $query->withCount([
            'examStudents as enrolled_students_count',
            'examResults as completed_students_count' => function (Builder $query) {
                $query->whereNotIn('result_status', [ExamResultStatusEnum::NotDeclared]);
            }
        ]);
    }

    /**
     * Scope to get exams by status.
     */
    public function scopeByStatus(Builder $query, ExamStatusEnum $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get exams by date range.
     */
    public function scopeByDateRange(Builder $query, string $startDate, string $endDate): Builder
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    /**
     * Generate a unique exam ID.
     */
    public static function generateUniqueExamId(): string
    {
        do {
            $examId = 'EXAM-' . strtoupper(Str::random(8));
        } while (self::where('exam_id', $examId)->exists());

        return $examId;
    }
}
