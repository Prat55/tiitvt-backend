<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AccessControlTrigger;
use App\Models\SiteAccessControl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AccessControlController extends Controller
{
    /**
     * Handle access control trigger.
     */
    public function trigger(Request $request)
    {
        // Validate APP_KEY
        $appKey = $request->header('X-APP-KEY') ?? $request->input('app_key');

        if (!$appKey || $appKey !== config('app.key')) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or missing APP_KEY'
            ], 401);
        }

        // Validate access parameter (accepts boolean or string boolean)
        $request->validate([
            'access' => 'required',
        ]);

        // Convert to boolean (handles "true", "false", "1", "0", true, false, etc.)
        $accessInput = $request->input('access');
        $accessValue = filter_var($accessInput, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        if ($accessValue === null) {
            // Try string comparison for "true"/"false"
            $accessValue = in_array(strtolower($accessInput), ['true', '1', 'yes', 'on'], true);
        }

        // Get optional block message
        $blockMessage = $request->input('message', null);

        // Update access control state
        SiteAccessControl::setAccess($accessValue, $blockMessage);

        // Log the trigger
        AccessControlTrigger::create([
            'url' => $request->fullUrl(),
            'ip_address' => $request->ip(),
            'parameters' => $request->all(),
            'access_value' => $accessValue,
        ]);

        return response()->json([
            'success' => true,
            'message' => $accessValue
                ? 'Website access has been enabled.'
                : 'Website access has been blocked.',
            'access' => $accessValue,
        ]);
    }
}
