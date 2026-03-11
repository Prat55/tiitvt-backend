<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class VideoStreamingController extends Controller
{
    public function stream(Request $request, string $path)
    {
        $decodedPath = base64_decode($path, true);
        if ($decodedPath === false) {
            abort(404, 'Video not found.');
        }

        $path = ltrim(str_replace('\\', '/', $decodedPath), '/');
        if ($path === '' || str_contains($path, '..')) {
            abort(404, 'Video not found.');
        }

        $disk = Storage::disk('public');

        if (!$disk->exists($path)) {
            abort(404, 'Video not found.');
        }

        $fullPath = $disk->path($path);
        $fileSize = filesize($fullPath);
        $mimeType = $disk->mimeType($path) ?: 'video/mp4';

        if ($fileSize === false) {
            abort(404, 'Video not found.');
        }

        $headers = [
            'Content-Type' => $mimeType,
            'Accept-Ranges' => 'bytes',
            'Content-Encoding' => 'identity',
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Expose-Headers' => 'Content-Range, Content-Length, Accept-Ranges',
            'X-Content-Type-Options' => 'nosniff',
            'X-Accel-Buffering' => 'no',
            'Cache-Control' => 'public, max-age=3600',
            'Pragma' => 'public',
        ];

        if ($fileSize === 0) {
            return response('', Response::HTTP_OK, $headers + [
                'Content-Length' => '0',
            ]);
        }

        $start = 0;
        $end = $fileSize - 1;
        $statusCode = Response::HTTP_OK;
        $range = $request->header('Range');

        if ($range !== null) {
            $parsedRange = $this->parseRange($range, $fileSize);
            if ($parsedRange === null) {
                return response('', Response::HTTP_REQUESTED_RANGE_NOT_SATISFIABLE, $headers + [
                    'Content-Range' => "bytes */{$fileSize}",
                    'Content-Length' => '0',
                ]);
            }

            [$start, $end] = $parsedRange;
            $statusCode = Response::HTTP_PARTIAL_CONTENT;
            $headers['Content-Range'] = "bytes {$start}-{$end}/{$fileSize}";
        }

        $length = $end - $start + 1;
        $headers['Content-Length'] = (string) $length;

        return response()->stream(function () use ($fullPath, $start, $length) {
            $stream = fopen($fullPath, 'rb');
            if ($stream === false) {
                return;
            }

            try {
                if ($start > 0) {
                    fseek($stream, $start);
                }

                $remaining = $length;
                $bufferSize = 1024 * 1024;

                while ($remaining > 0 && !feof($stream)) {
                    $chunk = fread($stream, min($remaining, $bufferSize));
                    if ($chunk === false || $chunk === '') {
                        break;
                    }

                    echo $chunk;
                    $remaining -= strlen($chunk);
                    flush();
                }
            } finally {
                fclose($stream);
            }
        }, $statusCode, $headers);
    }

    private function parseRange(string $range, int $fileSize): ?array
    {
        $range = trim($range);

        if (str_contains($range, ',')) {
            return null;
        }

        if (!preg_match('/^bytes=(\d*)-(\d*)$/', $range, $matches)) {
            return null;
        }

        [$fullMatch, $startPart, $endPart] = $matches;
        unset($fullMatch);

        if ($startPart === '' && $endPart === '') {
            return null;
        }

        if ($startPart === '') {
            $suffixLength = (int) $endPart;
            if ($suffixLength <= 0) {
                return null;
            }

            $start = max(0, $fileSize - $suffixLength);
            return [$start, $fileSize - 1];
        }

        $start = (int) $startPart;
        if ($start >= $fileSize) {
            return null;
        }

        $end = $endPart === '' ? $fileSize - 1 : min((int) $endPart, $fileSize - 1);
        if ($end < $start) {
            return null;
        }

        return [$start, $end];
    }
}
