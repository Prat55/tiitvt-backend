<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'center_id',
        'course_id',
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
        'course_taken',
        'batch_time',
        'scheme_given',
        'course_fees',
        'down_payment',
        'no_of_installments',
        'installment_date',
        'enrollment_date',
        'student_image',
        'student_signature_image',
        'incharge_name',
    ];

    protected $casts = [
        'address' => 'array',
        'date_of_birth' => 'date',
        'enrollment_date' => 'date',
        'installment_date' => 'date',
        'course_fees' => 'decimal:2',
        'down_payment' => 'decimal:2',
        'age' => 'integer',
        'no_of_installments' => 'integer',
        'center_id' => 'integer',
        'course_id' => 'integer',
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
     * Get the course that owns the student.
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Get the exam results for the student.
     */
    public function examResults(): HasMany
    {
        return $this->hasMany(ExamResult::class);
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
}
