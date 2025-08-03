<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Center extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'phone',
        'address',
        'state',
        'country',
        'institute_logo',
        'front_office_photo',
        'back_office_photo',
        'email',
        'owner_name',
        'aadhar',
        'pan',
        'status',
    ];

    protected $casts = [
        'status' => 'string',
    ];

    /**
     * Get the user that owns the center.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the students for the center.
     */
    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    /**
     * Scope to get only active centers.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Generate a unique center ID with incrementing format (CTR0001, CTR0002, etc.)
     *
     * @return string
     */
    public static function generateUniqueCenterId(): string
    {
        // Get the last center ID from the database
        $lastCenter = self::orderBy('id', 'desc')->first();

        if (!$lastCenter) {
            // If no centers exist, start with CTR0001
            return 'CTR0001';
        }

        // Extract the numeric part from the last center ID
        $lastId = $lastCenter->id;
        $nextNumber = $lastId + 1;

        // Format the ID with leading zeros (4 digits)
        return 'CTR' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Boot method to automatically set center_id when creating a new center
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($center) {
            if (empty($center->uid)) {
                $center->uid = self::generateUniqueCenterId();
            }
        });
    }
}
