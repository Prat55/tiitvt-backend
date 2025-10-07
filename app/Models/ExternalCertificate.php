<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExternalCertificate extends Model
{
    use HasFactory;

    protected $fillable = [
        'center_id',
        'reg_no',
        'course_name',
        'student_name',
        'grade',
        'percentage',
        'issued_on',
        'qr_token',
        'qr_code_path',
        'pdf_path',
        'data',
    ];

    protected $casts = [
        'issued_on' => 'date',
        'percentage' => 'decimal:2',
        'data' => 'array',
    ];

    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }
}
