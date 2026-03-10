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
        // 1. Suppress errors and clearing output buffer
        @ini_set('display_errors', '0');
        if (ob_get_level() > 0) {
            ob_end_clean();
        }

        // 2. Prevent session locking while streaming
        if (session_id()) {
            session_write_close();
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

        $range = $request->header('Range');
        if ($range && preg_match('/bytes=(\d+)-(\d*)/', $range, $matches)) {
            $start = (int) $matches[1];

            if (!empty($matches[2])) {
                $end = (int) $matches[2];
            } else {
                // Default chunk size if end is not specified (e.g. 2MB)
                $end = min($start + (2 * 1024 * 1024), $fileSize - 1);
            }

            $end = min($end, $fileSize - 1);
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
            'Pragma'                 => 'public',
            'Cache-Control'          => 'public, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
            'X-Accel-Buffering'      => 'no', // disables Nginx buffering
        ];
    }

    private function streamChunks(string $fullPath, int $start, int $length): void
    {
        $handle = fopen($fullPath, 'rb');
        if (!$handle) {
            return;
        }

        fseek($handle, $start);

        $remaining = $length;

        try {
            while ($remaining > 0 && !feof($handle) && !connection_aborted()) {
                $toRead = min(self::CHUNK_SIZE, $remaining);
                $data   = fread($handle, $toRead);

                if ($data === false || $data === '') {
                    break;
                }

                echo $data;

                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();

                $remaining -= strlen($data);
            }
        } finally {
            fclose($handle);
        }
    }
}
