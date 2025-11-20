<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SiteAccessControl extends Model
{
    use HasFactory;

    protected $table = 'site_access_control';

    protected $fillable = [
        'is_accessible',
        'block_message',
    ];

    protected $casts = [
        'is_accessible' => 'boolean',
    ];

    /**
     * Get the current access control state.
     */
    public static function getCurrentState(): self
    {
        return self::firstOrCreate(
            [],
            [
                'is_accessible' => true,
                'block_message' => null,
            ]
        );
    }

    /**
     * Check if site is accessible.
     */
    public static function isAccessible(): bool
    {
        $state = self::getCurrentState();
        return $state->is_accessible;
    }

    /**
     * Set access state.
     */
    public static function setAccess(bool $accessible, ?string $message = null): void
    {
        $state = self::getCurrentState();
        $state->update([
            'is_accessible' => $accessible,
            'block_message' => $message,
        ]);
    }
}
