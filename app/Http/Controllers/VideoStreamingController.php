<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class VideoStreamingController extends Controller
{
    public function stream(Request $request, string $path)
    {
        $path = ltrim(str_replace('..', '', base64_decode($path)), '/');

        if (!Storage::disk('public')->exists($path)) {
            abort(404, 'Video not found.');
        }

        $fullPath = Storage::disk('public')->path($path);
        $fileSize = filesize($fullPath);
        $mimeType = 'video/mp4';

        // 3. RANGE HANDLING
        $start = 0;
        $end = $fileSize - 1;
        $statusCode = 200;
        $statusMessage = "OK";

        $range = $request->header('Range');
        if ($range && preg_match('/bytes=(\d+)-(\d*)/', $range, $matches)) {
            $start = (int)$matches[1];
            if (!empty($matches[2])) {
                $end = (int)$matches[2];
            }
            $end = min($end, $fileSize - 1);
            $statusCode = 206;
            $statusMessage = "Partial Content";
        }

        $length = $end - $start + 1;

        // 4. NAKED HEADERS (Bypass Laravel Response Logic)
        header("HTTP/1.1 $statusCode $statusMessage");
        header("Content-Type: $mimeType");
        header("Content-Length: $length");
        header("Content-Range: bytes $start-$end/$fileSize");
        header("Accept-Ranges: bytes");
        header("Content-Encoding: identity");
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Expose-Headers: Content-Range, Content-Length, Accept-Ranges");
        header("X-Content-Type-Options: nosniff");
        header("X-Accel-Buffering: no");
        header("Cache-Control: public, max-age=3600");
        header("Pragma: public");

        // 6. DIRECT STREAMING
        $fp = fopen($fullPath, 'rb');
        fseek($fp, $start);

        $bufferSize = 256 * 1024; // 256KB
        while ($length > 0 && !feof($fp)) {
            $read = min($length, $bufferSize);
            echo fread($fp, $read);
            flush();
            $length -= $read;
        }

        fclose($fp);
        exit; // Terminate immediately to prevent ANY framework post-processing
    }
}
