<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VideoStreamingMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. CLEAR ALL OUTPUT BUFFERS - Definitive binary safety
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        // 2. DISABLE COMPRESSION & ERRORS - Definitive binary safety
        @ini_set('zlib.output_compression', 'Off');
        @ini_set('display_errors', '0');
        @error_reporting(0);

        // 3. PREVENT SESSION LOCKING
        if (session_id()) {
            session_write_close();
        }

        return $next($request);
    }
}
