<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ForceJsonResponse
{
    public function handle(Request $request, Closure $next)
    {
        // Skip forcing JSON for video streaming routes to prevent binary corruption
        if ($request->is('api/videos/stream/*')) {
            return $next($request);
        }

        $request->headers->set('Accept', 'application/json');
        return $next($request);
    }
}
