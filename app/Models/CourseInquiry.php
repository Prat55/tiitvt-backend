<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseInquiry extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'name',
        'email',
        'phone',
        'status',
        'notes',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function scopeNew($query)
    {
        return $query->where('status', 'new');
    }
}
