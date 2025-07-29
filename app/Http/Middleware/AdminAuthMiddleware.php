<?php

namespace App\Http\Middleware;

use App\Enums\RolesEnum;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class AdminAuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            // Store the intended URL in the session
            session()->put('url.intended', url()->current());
            // Redirect to custom login page if not authenticated
            return redirect()->route('login');
        }
        $user = User::find(Auth::id());
        if (!$user->hasRole(RolesEnum::CENTER->value)) {
            return $next($request);
        }

        abort(403);
    }
}
