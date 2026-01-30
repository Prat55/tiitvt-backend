<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\TwoFactorAuthService;

class Verify2FA
{
    public function __construct(
        protected TwoFactorAuthService $twoFactorService
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        // Skip 2FA check for auth routes and specific routes
        if ($this->shouldSkip($request)) {
            return $next($request);
        }

        // Skip Livewire requests on 2FA verification pages
        if ($request->header('X-Livewire') === 'true') {
            return $next($request);
        }

        $user = auth()->user();

        // Check if user has 2FA enabled
        if ($user && $this->twoFactorService->is2FAEnabled($user)) {
            // Check if user has already verified 2FA in this session
            if (!session()->has('2fa_verified_at') || session()->get('2fa_verified_at') + 86400 < now()->timestamp) {
                // Redirect to 2FA verification page based on method
                $method = $user->two_factor_method ?? 'email';
                if ($method === 'authenticator') {
                    return redirect()->route('auth.verify-2fa-authenticator');
                }
                return redirect()->route('auth.verify-2fa-email');
            }
        }

        return $next($request);
    }

    protected function shouldSkip(Request $request): bool
    {
        // Routes that should skip 2FA verification
        $skipRoutes = [
            'login',
            'register',
            'password.request',
            'password.reset',
            'password.email',
            'auth.verify-2fa-email',
            'auth.verify-2fa-authenticator',
            'logout',
        ];

        foreach ($skipRoutes as $route) {
            if ($request->routeIs($route)) {
                return true;
            }
        }

        return !auth()->check();
    }
}
