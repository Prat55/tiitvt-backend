<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExternalCertificate extends Model
{
    use HasFactory;

    protected $fillable = [
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
}
