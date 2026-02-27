<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

class AuthApiController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'role' => ['nullable', 'in:student,center'],
        ]);

        $email = strtolower((string) $credentials['email']);
        $throttleKey = $this->loginThrottleKey($request, $email);

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return response()->json([
                'message' => 'Too many failed login attempts. Try again later.',
                'retry_after_seconds' => $seconds,
            ], 429);
        }

        $role = $credentials['role'] ?? null;
        $centerLoginEnabled = filter_var(env('ENABLE_CENTER_API_LOGIN', false), FILTER_VALIDATE_BOOLEAN);

        if ($role === 'center') {
            if (!$centerLoginEnabled) {
                return response()->json([
                    'message' => 'Center login is currently disabled.',
                ], 403);
            }

            $response = $this->attemptCenterLogin($credentials['email'], $credentials['password']);

            if ($response->getStatusCode() === 401) {
                RateLimiter::hit($throttleKey, 300);
            } else {
                RateLimiter::clear($throttleKey);
            }

            return $response;
        }

        if ($role === 'student') {
            $response = $this->attemptStudentLogin($credentials['email'], $credentials['password']);

            if ($response->getStatusCode() === 401) {
                RateLimiter::hit($throttleKey, 300);
            } else {
                RateLimiter::clear($throttleKey);
            }

            return $response;
        }

        if ($centerLoginEnabled) {
            $centerResponse = $this->attemptCenterLogin($credentials['email'], $credentials['password'], false);
            if ($centerResponse instanceof JsonResponse) {
                RateLimiter::clear($throttleKey);
                return $centerResponse;
            }
        }

        $studentResponse = $this->attemptStudentLogin($credentials['email'], $credentials['password'], false);
        if ($studentResponse instanceof JsonResponse) {
            RateLimiter::clear($throttleKey);
            return $studentResponse;
        }

        RateLimiter::hit($throttleKey, 300);

        return response()->json([
            'message' => 'Invalid credentials.',
        ], 401);
    }

    public function studentLogin(Request $request): JsonResponse
    {
        $request->merge(['role' => 'student']);

        return $this->login($request);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }

    private function attemptCenterLogin(string $email, string $password, bool $failDirectly = true): ?JsonResponse
    {
        $user = User::query()
            ->where('email', $email)
            ->where('is_active', true)
            ->first();

        if (!$user || !$user->hasRole('center') || !Hash::check($password, (string) $user->password)) {
            return $failDirectly ? response()->json(['message' => 'Invalid center credentials.'], 401) : null;
        }

        $token = $user->createToken('center-mobile')->plainTextToken;
        $this->pruneTokens($user, 3);

        return response()->json([
            'token' => $token,
            'token_type' => 'Bearer',
            'role' => 'center',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'center_id' => $user->center?->id,
            ],
        ]);
    }

    private function attemptStudentLogin(string $email, string $password, bool $failDirectly = true): ?JsonResponse
    {
        $student = Student::query()->where('email', $email)->first();

        if (!$student) {
            return $failDirectly ? response()->json(['message' => 'Invalid student credentials.'], 401) : null;
        }

        $defaultPassword = (string) env('STUDENT_DEFAULT_PASSWORD', '12345');

        $validPassword = Hash::check($password, (string) $student->password)
            || hash_equals($defaultPassword, $password);

        if (!$validPassword) {
            return $failDirectly ? response()->json(['message' => 'Invalid student credentials.'], 401) : null;
        }

        $token = $student->createToken('student-mobile')->plainTextToken;
        $this->pruneTokens($student, 3);

        return response()->json([
            'token' => $token,
            'token_type' => 'Bearer',
            'role' => 'student',
            'user' => [
                'id' => $student->id,
                'registration_no' => $student->tiitvt_reg_no,
                'name' => $student->full_name,
                'email' => $student->email,
            ],
        ]);
    }

    private function pruneTokens(object $authenticatable, int $maxAllowedTokens): void
    {
        $allTokenIds = $authenticatable->tokens()
            ->orderByDesc('id')
            ->pluck('id');

        $tokenIdsToDelete = $allTokenIds->slice($maxAllowedTokens)->values();

        if ($tokenIdsToDelete->isEmpty()) {
            return;
        }

        $authenticatable->tokens()
            ->whereIn('id', $tokenIdsToDelete)
            ->delete();
    }

    private function loginThrottleKey(Request $request, string $email): string
    {
        return 'api-login:' . $request->ip() . '|' . $email;
    }
}
