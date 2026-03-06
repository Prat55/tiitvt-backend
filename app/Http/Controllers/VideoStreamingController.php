<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VideoStreamingController extends Controller
{
    /**
     * Stream a video file using range requests for bit-by-bit delivery.
     */
    public function stream(string $path)
    {
        $path = base64_decode($path);

        if (!Storage::disk('public')->exists($path)) {
            abort(404, 'Video not found.');
        }

        $fullPath = Storage::disk('public')->path($path);
        $size = filesize($fullPath);
        $start = 0;
        $end = $size - 1;

        $headers = [
            'Content-Type' => Storage::disk('public')->mimeType($path) ?: 'video/mp4',
            'Accept-Ranges' => 'bytes',
        ];

        if (request()->headers->has('Range')) {
            $range = request()->header('Range');
            if (preg_match('/bytes=(\d+)-(\d+)?/', $range, $matches)) {
                $start = intval($matches[1]);
                $end = isset($matches[2]) ? intval($matches[2]) : $size - 1;
            }

            $headers['Content-Range'] = 'bytes ' . $start . '-' . $end . '/' . $size;
            $status = 206;
        } else {
            $status = 200;
        }

        $headers['Content-Length'] = $end - $start + 1;

        return response()->stream(function () use ($fullPath, $start, $end) {
            $file = fopen($fullPath, 'rb');
            fseek($file, $start);
            $buffer = 8192;
            while (!feof($file) && ($pos = ftell($file)) <= $end) {
                if ($pos + $buffer > $end) {
                    $buffer = $end - $pos + 1;
                }
                echo fread($file, $buffer);
                flush();
            }
            fclose($file);
        }, $status, $headers);
    }
}
