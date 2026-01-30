<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use App\Mail\TwoFactorCodeMail;
use Illuminate\Support\Facades\Log;

class TwoFactorAuthService
{
    const CACHE_PREFIX = 'twofa_code_';
    const CACHE_DURATION = 600; // 10 minutes
    const MAX_ATTEMPTS = 5;
    const ATTEMPT_CACHE_PREFIX = 'twofa_attempts_';

    /**
     * Generate a 6-digit code
     */
    public function generateCode(): string
    {
        return str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Send 2FA code via email
     */
    public function sendCodeViaEmail(User $user): bool
    {
        try {
            $code = $this->generateCode();
            $cacheKey = self::CACHE_PREFIX . $user->id;

            // Store code in cache for 10 minutes
            Cache::put($cacheKey, $code, self::CACHE_DURATION);

            // Send email with code
            Mail::to($user->email)->send(new TwoFactorCodeMail($user, $code));

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send 2FA code email: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'email' => $user->email,
                'exception' => $e
            ]);
            return false;
        }
    }

    /**
     * Verify the 2FA code
     */
    public function verifyCode(User $user, string $code): bool
    {
        $cacheKey = self::CACHE_PREFIX . $user->id;
        $storedCode = Cache::get($cacheKey);

        if (!$storedCode) {
            return false;
        }

        if ($storedCode === $code) {
            // Code is correct, clear it from cache
            Cache::forget($cacheKey);
            Cache::forget(self::ATTEMPT_CACHE_PREFIX . $user->id);
            return true;
        }

        // Increment failed attempts
        $this->recordFailedAttempt($user->id);

        return false;
    }

    /**
     * Record failed verification attempt
     */
    public function recordFailedAttempt(int $userId): int
    {
        $attemptKey = self::ATTEMPT_CACHE_PREFIX . $userId;
        $attempts = Cache::get($attemptKey, 0);
        $attempts++;

        Cache::put($attemptKey, $attempts, self::CACHE_DURATION);

        return $attempts;
    }

    /**
     * Check if user has exceeded max attempts
     */
    public function hasExceededAttempts(int $userId): bool
    {
        $attemptKey = self::ATTEMPT_CACHE_PREFIX . $userId;
        $attempts = Cache::get($attemptKey, 0);

        return $attempts >= self::MAX_ATTEMPTS;
    }

    /**
     * Get remaining attempts
     */
    public function getRemainingAttempts(int $userId): int
    {
        $attemptKey = self::ATTEMPT_CACHE_PREFIX . $userId;
        $attempts = Cache::get($attemptKey, 0);

        return max(0, self::MAX_ATTEMPTS - $attempts);
    }

    /**
     * Enable 2FA for user
     */
    public function enable2FA(User $user, string $method = 'email'): bool
    {
        try {
            $user->update([
                'two_factor_enabled' => true,
                'two_factor_method' => $method,
                'two_factor_verified_at' => now(),
            ]);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Disable 2FA for user
     */
    public function disable2FA(User $user): bool
    {
        try {
            $user->update([
                'two_factor_enabled' => false,
                'two_factor_method' => null,
            ]);

            // Clear any pending codes
            Cache::forget(self::CACHE_PREFIX . $user->id);
            Cache::forget(self::ATTEMPT_CACHE_PREFIX . $user->id);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check if 2FA is enabled for user
     */
    public function is2FAEnabled(User $user): bool
    {
        return $user->two_factor_enabled ?? false;
    }

    /**
     * Get 2FA method for user
     */
    public function get2FAMethod(User $user): ?string
    {
        return $user->two_factor_method;
    }

    /**
     * Set pending 2FA session
     */
    public function setPending2FASession(User $user): void
    {
        session()->put('pending_2fa_user_id', $user->id);
        session()->put('pending_2fa_timestamp', now());
    }

    /**
     * Get pending 2FA user
     */
    public function getPending2FAUser(): ?User
    {
        $userId = session()->get('pending_2fa_user_id');

        if (!$userId) {
            return null;
        }

        // Check if session is not older than 15 minutes
        $timestamp = session()->get('pending_2fa_timestamp');
        if ($timestamp && now()->diffInMinutes($timestamp) > 15) {
            $this->clearPending2FASession();
            return null;
        }

        return User::find($userId);
    }

    /**
     * Clear pending 2FA session
     */
    public function clearPending2FASession(): void
    {
        session()->forget('pending_2fa_user_id');
        session()->forget('pending_2fa_timestamp');
    }
}
