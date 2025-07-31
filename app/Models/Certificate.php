<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Certificate extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'course_id',
        'issued_on',
        'pdf_path',
        'qr_token',
        'qr_code_path',
        'certificate_number',
        'status',
    ];

    protected $casts = [
        'issued_on' => 'date',
        'status' => 'string',
    ];

    /**
     * Get the student that owns the certificate.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Get the course that owns the certificate.
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Scope to get only active certificates.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to get only revoked certificates.
     */
    public function scopeRevoked($query)
    {
        return $query->where('status', 'revoked');
    }
}
