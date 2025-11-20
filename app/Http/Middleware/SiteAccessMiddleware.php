<?php

namespace App\Http\Middleware;

use App\Models\SiteAccessControl;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SiteAccessMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Allow API trigger endpoint to always be accessible
        if ($request->is('api/access-control/trigger')) {
            return $next($request);
        }

        // Check if site is accessible
        if (!SiteAccessControl::isAccessible()) {
            $state = SiteAccessControl::getCurrentState();
            $message = $state->block_message ?? 'The website is currently unavailable. Please try again later.';

            return response()->view('errors.site-blocked', [
                'message' => $message
            ], 503);
        }

        return $next($request);
    }
}
