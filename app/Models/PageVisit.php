<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageVisit extends Model
{
    protected $fillable = [
        'page_type',
        'page_url',
        'token',
        'student_id',
        'certificate_id',
        'ip_address',
        'user_agent',
        'browser',
        'browser_version',
        'platform',
        'device_type',
        'referer',
        'additional_data',
    ];

    protected $casts = [
        'additional_data' => 'array',
    ];

    /**
     * Get the student that this visit is associated with.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Get the certificate that this visit is associated with.
     */
    public function certificate(): BelongsTo
    {
        return $this->belongsTo(Certificate::class);
    }
}
