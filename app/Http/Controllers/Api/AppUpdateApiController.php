<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppUpdate;
use Illuminate\Http\Request;

class AppUpdateApiController extends Controller
{
    /**
     * Get the latest app update for a given type.
     */
    public function latest(Request $request)
    {
        $request->validate([
            'type' => 'required|in:tiitvt,it-centre',
        ]);

        $latest = AppUpdate::where('type', $request->type)
            ->where('published_at', '<=', now())
            ->orderBy('version_code', 'desc')
            ->first();

        if (!$latest) {
            return response()->json([
                'message' => 'No updates found for this type.',
            ], 404);
        }

        return response()->json([
            'version' => $latest->version,
            'version_code' => $latest->version_code,
            'changelog' => $latest->changelog,
            'published_at' => $latest->published_at,
            'apk_link' => $latest->apk_path ? asset('storage/' . $latest->apk_path) : null,
        ]);
    }
}
