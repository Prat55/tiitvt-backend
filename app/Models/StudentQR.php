<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentQR extends Model
{
    protected $fillable = [
        'student_id',
        'qr_token',
        'qr_code_path',
        'qr_data',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the student that owns the QR code.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
