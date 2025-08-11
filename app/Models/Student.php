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
        'status',
    ];

    protected $casts = [
        'address' => 'array',
        'date_of_birth' => 'date',
        'enrollment_date' => 'date',
        'installment_date' => 'date',
        'course_fees' => 'decimal:2',
        'down_payment' => 'decimal:2',
        'status' => 'string',
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
        if ($this->middle_name) {
            $name .= ' ' . $this->middle_name;
        }
        if ($this->last_name) {
            $name .= ' ' . $this->last_name;
        }
        return $name;
    }

    /**
     * Scope to get only active students.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public static function generateUniqueTiitvtRegNo($centerId): string
    {
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

        return "TIITVT/ATC/{$centerId}/{$studentNumber}";
    }
}
