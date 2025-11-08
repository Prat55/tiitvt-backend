<?php

use App\Models\WebsiteSetting;
use Illuminate\Support\Facades\Log;

if (!function_exists('getWebsiteSettings')) {
    function getWebsiteSettings()
    {
        return WebsiteSetting::first();
    }
}

if (!function_exists('getWebsiteName')) {
    function getWebsiteName()
    {
        return getWebsiteSettings()->website_name ?? config('app.name');
    }
}

if (!function_exists('getWebsitePrimaryEmail')) {
    function getWebsitePrimaryEmail()
    {
        return getWebsiteSettings()->primary_email ?? null;
    }
}

if (!function_exists('getWebsiteSecondaryEmail')) {
    function getWebsiteSecondaryEmail()
    {
        return getWebsiteSettings()->secondary_email ?? null;
    }
}

if (!function_exists('getWebsitePrimaryPhone')) {
    function getWebsitePrimaryPhone()
    {
        return getWebsiteSettings()->primary_phone ?? null;
    }
}

if (!function_exists('getWebsiteSecondaryPhone')) {
    function getWebsiteSecondaryPhone()

    {
        return getWebsiteSettings()->secondary_phone ?? null;
    }
}

if (!function_exists('hasAuthRole')) {
    function hasAuthRole($role)
    {
        return auth()->user()->hasRole($role);
    }
}

if (!function_exists('encodeTiitvtRegNo')) {
    function encodeTiitvtRegNo($regNo)
    {
        return str_replace('/', '_', $regNo);
    }
}

if (!function_exists('decodeTiitvtRegNo')) {
    function decodeTiitvtRegNo($encodedRegNo)
    {
        return str_replace('_', '/', $encodedRegNo);
    }
}

if (!function_exists('getUserCenterId')) {
    function getUserCenterId()
    {
        $user = auth()->user();
        if (!$user) {
            return null;
        }

        // Check if user has center role and get their center_id
        if ($user->hasRole('center')) {
            return $user->center->id ?? null;
        }

        // Admin users can access all centers
        return null;
    }
}

if (!function_exists('canAccessStudent')) {
    function canAccessStudent($student)
    {
        $userCenterId = getUserCenterId();

        // Admin users can access all students
        if ($userCenterId === null) {
            return true;
        }

        // Center users can only access students from their center
        return $student->center_id === $userCenterId;
    }
}

if (!function_exists('trackPageVisit')) {
    /**
     * Track a page visit with IP and browser information
     *
     * @param string $pageType
     * @param array $additionalData
     * @return \App\Models\PageVisit|null
     */
    function trackPageVisit(string $pageType, array $additionalData = [])
    {
        try {
            $request = request();
            $userAgent = $request->userAgent();
            $browserInfo = parseUserAgent($userAgent);

            $visitData = [
                'page_type' => $pageType,
                'page_url' => $request->fullUrl(),
                'ip_address' => $request->ip(),
                'user_agent' => $userAgent,
                'browser' => $browserInfo['browser'] ?? null,
                'browser_version' => $browserInfo['version'] ?? null,
                'platform' => $browserInfo['platform'] ?? null,
                'device_type' => $browserInfo['device_type'] ?? null,
                'referer' => $request->header('referer'),
                'additional_data' => $additionalData,
            ];

            // Add token if provided
            if (isset($additionalData['token'])) {
                $visitData['token'] = $additionalData['token'];
            }

            // Add student_id if provided
            if (isset($additionalData['student_id'])) {
                $visitData['student_id'] = $additionalData['student_id'];
            }

            // Add certificate_id if provided
            if (isset($additionalData['certificate_id'])) {
                $visitData['certificate_id'] = $additionalData['certificate_id'];
            }

            return \App\Models\PageVisit::create($visitData);
        } catch (\Exception $e) {
            // Silently fail if tracking fails - don't break the page
            Log::error('Failed to track page visit: ' . $e->getMessage());
            return null;
        }
    }
}

if (!function_exists('parseUserAgent')) {
    /**
     * Parse user agent to extract browser and device information
     *
     * @param string|null $userAgent
     * @return array
     */
    function parseUserAgent(?string $userAgent): array
    {
        if (!$userAgent) {
            return [];
        }

        $info = [
            'browser' => null,
            'version' => null,
            'platform' => null,
            'device_type' => 'desktop',
        ];

        // Detect browser
        if (preg_match('/MSIE|Trident/i', $userAgent)) {
            $info['browser'] = 'Internet Explorer';
            if (preg_match('/MSIE (\d+\.\d+)/', $userAgent, $matches)) {
                $info['version'] = $matches[1];
            }
        } elseif (preg_match('/Edge/i', $userAgent)) {
            $info['browser'] = 'Edge';
            if (preg_match('/Edge\/(\d+\.\d+)/', $userAgent, $matches)) {
                $info['version'] = $matches[1];
            }
        } elseif (preg_match('/Chrome/i', $userAgent)) {
            $info['browser'] = 'Chrome';
            if (preg_match('/Chrome\/(\d+\.\d+)/', $userAgent, $matches)) {
                $info['version'] = $matches[1];
            }
        } elseif (preg_match('/Safari/i', $userAgent) && !preg_match('/Chrome/i', $userAgent)) {
            $info['browser'] = 'Safari';
            if (preg_match('/Version\/(\d+\.\d+)/', $userAgent, $matches)) {
                $info['version'] = $matches[1];
            }
        } elseif (preg_match('/Firefox/i', $userAgent)) {
            $info['browser'] = 'Firefox';
            if (preg_match('/Firefox\/(\d+\.\d+)/', $userAgent, $matches)) {
                $info['version'] = $matches[1];
            }
        } elseif (preg_match('/Opera|OPR/i', $userAgent)) {
            $info['browser'] = 'Opera';
            if (preg_match('/(?:Opera|OPR)\/(\d+\.\d+)/', $userAgent, $matches)) {
                $info['version'] = $matches[1];
            }
        }

        // Detect platform
        if (preg_match('/Windows/i', $userAgent)) {
            $info['platform'] = 'Windows';
        } elseif (preg_match('/Macintosh|Mac OS X/i', $userAgent)) {
            $info['platform'] = 'macOS';
        } elseif (preg_match('/Linux/i', $userAgent)) {
            $info['platform'] = 'Linux';
        } elseif (preg_match('/Android/i', $userAgent)) {
            $info['platform'] = 'Android';
        } elseif (preg_match('/iPhone|iPad|iPod/i', $userAgent)) {
            $info['platform'] = 'iOS';
        }

        // Detect device type
        if (preg_match('/Mobile|Android|iPhone|iPad|iPod/i', $userAgent)) {
            if (preg_match('/iPad/i', $userAgent)) {
                $info['device_type'] = 'tablet';
            } else {
                $info['device_type'] = 'mobile';
            }
        }

        return $info;
    }
}
