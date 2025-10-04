<?php

namespace App\Http\Controllers;

use App\Services\WebsiteSettingsService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class FaviconController extends Controller
{
    /**
     * Serve the dynamic favicon.
     */
    public function favicon(WebsiteSettingsService $websiteSettings)
    {
        $faviconUrl = $websiteSettings->getFaviconUrl();

        if (!$faviconUrl) {
            // Return default favicon or 404
            return response()->file(public_path('default/favicon.ico'));
        }

        $faviconPath = Storage::path(str_replace('/storage/', '', $faviconUrl));

        if (!file_exists($faviconPath)) {
            return response()->file(public_path('default/favicon.ico'));
        }

        $content = file_get_contents($faviconPath);
        $mimeType = $this->getMimeType($faviconPath);

        return response($content)
            ->header('Content-Type', $mimeType)
            ->header('Cache-Control', 'public, max-age=31536000'); // Cache for 1 year
    }

    /**
     * Get MIME type based on file extension.
     */
    private function getMimeType(string $filePath): string
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        return match ($extension) {
            'ico' => 'image/x-icon',
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'svg' => 'image/svg+xml',
            default => 'image/x-icon',
        };
    }
}
