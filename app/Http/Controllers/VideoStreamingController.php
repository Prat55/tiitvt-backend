<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VideoStreamingController extends Controller
{
    private const CHUNK_SIZE = 256 * 1024; // 256KB — better for streaming

    public function stream(Request $request, string $path)
    {
        // Disable PHP output buffering immediately
        if (ob_get_level()) {
            ob_end_clean();
        }

        $path = ltrim(str_replace('..', '', base64_decode($path)), '/');

        if (!Storage::disk('public')->exists($path)) {
            abort(404, 'Video not found.');
        }

        $fullPath = Storage::disk('public')->path($path);
        $mimeType = Storage::disk('public')->mimeType($path) ?: 'video/mp4';
        $fileSize = filesize($fullPath);

        [$start, $end, $statusCode] = $this->parseRange($request, $fileSize);
        $length = $end - $start + 1;

        $headers = $this->buildHeaders($mimeType, $fileSize, $start, $end, $length);

        return new StreamedResponse(
            function () use ($fullPath, $start, $length) {
                $this->streamChunks($fullPath, $start, $length);
            },
            $statusCode,
            $headers
        );
    }

    private function parseRange(Request $request, int $fileSize): array
    {
        $start = 0;
        $end   = $fileSize - 1;
        $statusCode = 200;

        if ($request->hasHeader('Range')) {
            preg_match('/bytes=(\d+)-(\d*)/', $request->header('Range'), $matches);

            $start = (int) $matches[1];
            $end   = !empty($matches[2]) ? (int) $matches[2] : min($start + (2 * 1024 * 1024), $fileSize - 1);
            $end   = min($end, $fileSize - 1);
            $statusCode = 206;

            if ($start > $end || $start >= $fileSize) {
                abort(416, 'Requested Range Not Satisfiable');
            }
        }

        return [$start, $end, $statusCode];
    }

    private function buildHeaders(
        string $mimeType,
        int $fileSize,
        int $start,
        int $end,
        int $length
    ): array {
        return [
            'Content-Type'           => $mimeType,
            'Accept-Ranges'          => 'bytes',
            'Content-Length'         => $length,
            'Content-Range'          => "bytes {$start}-{$end}/{$fileSize}",
            'Content-Disposition'    => 'inline',
            'Cache-Control'          => 'public, max-age=3600',
            'Connection'             => 'keep-alive', // 👈 key fix
            'X-Content-Type-Options' => 'nosniff',
            'X-Accel-Buffering'      => 'no', // 👈 disables Nginx buffering
        ];
    }

    private function streamChunks(string $fullPath, int $start, int $length): void
    {
        $handle = fopen($fullPath, 'rb');
        fseek($handle, $start);

        $remaining = $length;

        while (!feof($handle) && $remaining > 0 && !connection_aborted()) {
            $toRead = min(self::CHUNK_SIZE, $remaining);
            $data   = fread($handle, $toRead);

            echo $data;

            // Flush both PHP and system buffers
            if (ob_get_level() > 0) {
                ob_flush();
            }
            flush();

            $remaining -= strlen($data);
        }

        fclose($handle);
    }
}
