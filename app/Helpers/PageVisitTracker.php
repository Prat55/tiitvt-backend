<?php

namespace App\Helpers;

use App\Models\PageVisit;
use Illuminate\Http\Request;

class PageVisitTracker
{
    /**
     * Track a page visit
     *
     * @param string $pageType
     * @param Request $request
     * @param array $additionalData
     * @return PageVisit
     */
    public static function track(string $pageType, Request $request, array $additionalData = []): PageVisit
    {
        $userAgent = $request->userAgent();
        $browserInfo = self::parseUserAgent($userAgent);

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

        return PageVisit::create($visitData);
    }

    /**
     * Parse user agent to extract browser and device information
     *
     * @param string|null $userAgent
     * @return array
     */
    private static function parseUserAgent(?string $userAgent): array
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
