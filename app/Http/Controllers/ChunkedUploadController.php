<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ChunkedUploadController extends Controller
{
    /**
     * Initialize a chunked upload.
     */
    public function init(Request $request)
    {
        $request->validate([
            'filename' => 'required|string',
            'totalSize' => 'required|integer',
        ]);

        $uploadId = Str::uuid()->toString();
        $extension = pathinfo($request->filename, PATHINFO_EXTENSION);
        $tempName = $uploadId . '.' . $extension;

        // Store metadata in a small json file or just use the uploadId as reference
        Storage::disk('public')->put('lectures/tmp/' . $uploadId . '.json', json_encode([
            'original_name' => $request->filename,
            'total_size' => $request->totalSize,
            'temp_name' => $tempName,
            'chunks_received' => [],
            'created_at' => now()->timestamp,
        ]));

        return response()->json([
            'uploadId' => $uploadId,
        ]);
    }

    /**
     * Upload a single chunk.
     */
    public function uploadChunk(Request $request, $uploadId)
    {
        $request->validate([
            'chunk' => 'required|file',
            'index' => 'required|integer',
        ]);

        $metaPath = 'lectures/tmp/' . $uploadId . '.json';
        if (!Storage::disk('public')->exists($metaPath)) {
            return response()->json(['error' => 'Upload session not found'], 404);
        }

        $chunkPath = 'lectures/tmp/' . $uploadId . '_chunks/' . $request->index;
        Storage::disk('public')->put($chunkPath, file_get_contents($request->chunk->getRealPath()));

        return response()->json(['status' => 'success']);
    }

    /**
     * Complete the upload and merge chunks.
     */
    public function complete(Request $request, $uploadId)
    {
        $metaPath = 'lectures/tmp/' . $uploadId . '.json';
        if (!Storage::disk('public')->exists($metaPath)) {
            return response()->json(['error' => 'Upload session not found'], 404);
        }

        $meta = json_decode(Storage::disk('public')->get($metaPath), true);
        $tempRelativePath = 'lectures/tmp/' . $meta['temp_name'];
        $tempFilePath = Storage::disk('public')->path($tempRelativePath);

        $chunkDir = 'lectures/tmp/' . $uploadId . '_chunks';
        $chunks = Storage::disk('public')->files($chunkDir);

        // Sort chunks numerically based on their filename (index)
        usort($chunks, function ($a, $b) {
            return (int)basename($a) <=> (int)basename($b);
        });

        $out = fopen($tempFilePath, 'wb');

        foreach ($chunks as $chunkRelativePath) {
            $chunkFullPath = Storage::disk('public')->path($chunkRelativePath);
            $in = fopen($chunkFullPath, 'rb');
            stream_copy_to_stream($in, $out);
            fclose($in);
            Storage::disk('public')->delete($chunkRelativePath); // Remove chunk after merging
        }
        fclose($out);
        Storage::disk('public')->deleteDirectory($chunkDir); // Remove chunk directory

        // Keep upload completion fast: remux MP4 for faststart without full re-encode.
        $extension = strtolower(pathinfo($tempRelativePath, PATHINFO_EXTENSION));
        if ($extension === 'mp4') {
            $this->applyFaststartRemux($tempFilePath);
        }

        return response()->json([
            'path' => 'lectures/tmp/' . $meta['temp_name'],
            'filename' => $meta['original_name']
        ]);
    }

    /**
     * Remux MP4 so metadata is at the front for faster initial playback.
     */
    private function applyFaststartRemux(string $filePath): void
    {
        $ffmpeg = config('app.ffmpeg_path', 'ffmpeg');

        // Output temp file next to original, keep .mp4 extension
        $normalizedPath = preg_replace('/\.mp4$/i', '.faststart.mp4', $filePath) ?: ($filePath . '.faststart.mp4');

        // -c copy avoids expensive encode during the upload completion request.
        $command = sprintf(
            '%s -y -i %s -movflags +faststart -c copy %s 2>/dev/null',
            escapeshellcmd($ffmpeg),
            escapeshellarg($filePath),
            escapeshellarg($normalizedPath)
        );

        exec($command, $output, $exitCode);

        if ($exitCode === 0 && file_exists($normalizedPath) && filesize($normalizedPath) > 0) {
            rename($normalizedPath, $filePath);
        } elseif (file_exists($normalizedPath)) {
            @unlink($normalizedPath);
        }
    }
}
