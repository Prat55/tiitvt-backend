<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'center_id',
        'tiitvt_reg_no',
        'first_name',
        'fathers_name',
        'surname',
        'address',
        'telephone_no',
        'email',
        'mobile',
        'date_of_birth',
        'age',
        'qualification',
        'additional_qualification',
        'reference',
        'course_fees',
        'down_payment',
        'enrollment_date',
        'student_image',
        'student_signature_image',
    ];

    protected $casts = [
        'address' => 'array',
        'date_of_birth' => 'date',
        'enrollment_date' => 'date',
        'course_fees' => 'decimal:2',
        'down_payment' => 'decimal:2',
        'age' => 'integer',
        'center_id' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($student) {
            if (empty($student->tiitvt_reg_no)) {
                $student->tiitvt_reg_no = self::generateUniqueTiitvtRegNo($student->center_id);
            }
        });
    }

    /**
     * Get the center that owns the student.
     */
    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    /**
     * Get the courses that the student is enrolled in.
     */
    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'student_courses')
            ->withPivot([
                'enrollment_date',
                'course_taken',
                'batch_time',
                'scheme_given',
                'incharge_name'
            ])
            ->withTimestamps();
    }

    /**
     * Get the student course enrollments.
     */
    public function studentCourses(): HasMany
    {
        return $this->hasMany(StudentCourse::class);
    }

    /**
     * Get the exam results for the student.
     */
    public function examResults(): HasMany
    {
        return $this->hasMany(ExamResult::class);
    }

    /**
     * Get the exam students for the student.
     */
    public function examStudents(): HasMany
    {
        return $this->hasMany(ExamStudent::class);
    }

    /**
     * Get the exams for the student.
     */
    public function exams()
    {
        return $this->hasMany(ExamStudent::class);
    }

    /**
     * Get the invoices for the student.
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Get the certificates for the student.
     */
    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }

    /**
     * Get the installments for the student.
     */
    public function installments(): HasMany
    {
        return $this->hasMany(Installment::class);
    }

    /**
     * Get the QR code for the student.
     */
    public function qrCode(): HasOne
    {
        return $this->hasOne(StudentQR::class);
    }

    /**
     * Get the full name of the student.
     */
    public function getFullNameAttribute(): string
    {
        $name = $this->first_name;
        if ($this->fathers_name) {
            $name .= ' ' . $this->fathers_name;
        }
        if ($this->surname) {
            $name .= ' ' . $this->surname;
        }
        return $name;
    }

    public static function generateUniqueTiitvtRegNo($centerId): string
    {
        $maxAttempts = 10;
        $attempt = 0;

        do {
            $attempt++;

            // Get the last student in this center
            $lastStudentInCenter = self::where('center_id', $centerId)
                ->latest()
                ->first();

            if (!$lastStudentInCenter) {
                $studentNumber = 1;
            } else {
                $lastRegNo = $lastStudentInCenter->tiitvt_reg_no;
                if (preg_match('/\/(\d+)$/', $lastRegNo, $matches)) {
                    $studentNumber = (int)$matches[1] + 1;
                } else {
                    $studentNumber = $lastStudentInCenter->id + 1;
                }
            }

            $proposedRegNo = "TIITVT/ATC/{$centerId}/{$studentNumber}";

            // Check if this registration number already exists
            if (!self::where('tiitvt_reg_no', $proposedRegNo)->exists()) {
                return $proposedRegNo;
            }

            // If it exists, increment the student number and try again
            $studentNumber++;
        } while ($attempt < $maxAttempts);

        // If we still can't find a unique number after max attempts,
        // add a timestamp suffix to ensure uniqueness
        $timestamp = now()->format('His');
        return "TIITVT/ATC/{$centerId}/{$studentNumber}_{$timestamp}";
    }

    public function scopeSearch($query, $search)
    {
        return $query->whereAny(['first_name', 'fathers_name', 'surname', 'tiitvt_reg_no', 'mobile', 'email'], 'like', "%{$search}%");
    }

    /**
     * Get the primary course for backward compatibility.
     * Returns the first enrolled course.
     */
    public function getCourseAttribute()
    {
        // Check if courses are already loaded
        if ($this->relationLoaded('courses')) {
            return $this->courses->first();
        }

        // If not loaded, load the first course
        return $this->courses()->first();
    }

    /**
     * Get the primary course ID for backward compatibility.
     */
    public function getCourseIdAttribute()
    {
        $primaryCourse = $this->course; // Use the course accessor
        return $primaryCourse ? $primaryCourse->id : null;
    }

    /**
     * Get the primary course taken for backward compatibility.
     */
    public function getCourseTakenAttribute()
    {
        $primaryCourse = $this->course;
        return $primaryCourse ? $primaryCourse->pivot->course_taken : null;
    }

    /**
     * Get the primary course batch time for backward compatibility.
     */
    public function getBatchTimeAttribute()
    {
        $primaryCourse = $this->course;
        return $primaryCourse ? $primaryCourse->pivot->batch_time : null;
    }

    /**
     * Get the primary course scheme given for backward compatibility.
     */
    public function getSchemeGivenAttribute()
    {
        $primaryCourse = $this->course;
        return $primaryCourse ? $primaryCourse->pivot->scheme_given : null;
    }

    /**
     * Get the primary course incharge name for backward compatibility.
     */
    public function getInchargeNameAttribute()
    {
        $primaryCourse = $this->course;
        return $primaryCourse ? $primaryCourse->pivot->incharge_name : null;
    }

    public function getInitials()
    {
        $first = is_string($this->first_name) && strlen($this->first_name) ? mb_substr($this->first_name, 0, 1) : '';
        $last  = is_string($this->surname) && strlen($this->surname) ? mb_substr($this->surname, 0, 1) : '';
        return strtoupper($first . $last);
    }
}
