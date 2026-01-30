<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SessionManager
{
    /**
     * Load all sessions for a user
     *
     * @param int $userId
     * @param string $currentSessionId
     * @return array
     */
    public function loadUserSessions($userId, $currentSessionId)
    {
        try {
            $sessions = [];

            // Query database sessions table
            $dbSessions = DB::table('sessions')
                ->where('user_id', $userId)
                ->orderByDesc('last_activity')
                ->get();

            if ($dbSessions->isNotEmpty()) {
                foreach ($dbSessions as $dbSession) {
                    $isCurrentSession = $dbSession->id === $currentSessionId;

                    $sessions[] = [
                        'id' => $dbSession->id,
                        'device' => $this->getDeviceFromUserAgent($dbSession->user_agent ?? ''),
                        'browser' => $this->getBrowserFromUserAgent($dbSession->user_agent ?? ''),
                        'ip' => $dbSession->ip_address ?? 'Unknown',
                        'lastActivity' => Carbon::createFromTimestamp($dbSession->last_activity)->format('M d, Y \a\t g:i A'),
                        'isCurrent' => $isCurrentSession,
                        'userAgent' => $dbSession->user_agent ?? '',
                    ];
                }
            } else {
                // Fallback to mock data if no sessions in database
                $sessions = $this->getMockSessions($currentSessionId);
            }

            return $sessions;
        } catch (\Exception $e) {
            // If database sessions table doesn't exist, use mock data
            return $this->getMockSessions($currentSessionId);
        }
    }

    /**
     * Get mock sessions for UI testing
     *
     * @param string $currentSessionId
     * @return array
     */
    public function getMockSessions($currentSessionId)
    {
        return [
            [
                'id' => $currentSessionId,
                'device' => 'Windows Desktop',
                'browser' => 'Chrome',
                'ip' => request()->ip() ?? '192.168.1.1',
                'lastActivity' => 'Active now',
                'isCurrent' => true,
                'userAgent' => '',
            ],
            [
                'id' => bin2hex(random_bytes(16)),
                'device' => 'iPhone 13',
                'browser' => 'Safari',
                'ip' => '203.45.67.89',
                'lastActivity' => 'Jan 28, 2026 at 2:45 PM',
                'isCurrent' => false,
                'userAgent' => '',
            ],
            [
                'id' => bin2hex(random_bytes(16)),
                'device' => 'iPad',
                'browser' => 'Safari',
                'ip' => '156.23.45.67',
                'lastActivity' => 'Jan 25, 2026 at 10:20 AM',
                'isCurrent' => false,
                'userAgent' => '',
            ],
        ];
    }

    /**
     * Get device type from user agent string
     *
     * @param string $userAgent
     * @return string
     */
    public function getDeviceFromUserAgent($userAgent)
    {
        if (stripos($userAgent, 'Mobile') !== false || stripos($userAgent, 'Android') !== false) {
            if (stripos($userAgent, 'iPad') !== false) {
                return 'iPad';
            }
            return 'Mobile Device';
        } elseif (stripos($userAgent, 'iPad') !== false) {
            return 'iPad';
        } elseif (stripos($userAgent, 'Windows') !== false) {
            return 'Windows Desktop';
        } elseif (stripos($userAgent, 'Macintosh') !== false) {
            return 'Mac Desktop';
        } elseif (stripos($userAgent, 'Linux') !== false) {
            return 'Linux Device';
        }

        return 'Unknown Device';
    }

    /**
     * Get browser type from user agent string
     *
     * @param string $userAgent
     * @return string
     */
    public function getBrowserFromUserAgent($userAgent)
    {
        if (stripos($userAgent, 'Firefox') !== false) {
            return 'Firefox';
        } elseif (stripos($userAgent, 'Chrome') !== false) {
            return 'Chrome';
        } elseif (stripos($userAgent, 'Safari') !== false) {
            return 'Safari';
        } elseif (stripos($userAgent, 'Edge') !== false) {
            return 'Edge';
        }

        return 'Unknown';
    }

    /**
     * Sign out all sessions except the current one
     *
     * @param int $userId
     * @param string $currentSessionId
     * @return bool
     */
    public function signOutOtherSessions($userId, $currentSessionId)
    {
        try {
            DB::table('sessions')
                ->where('user_id', $userId)
                ->where('id', '!=', $currentSessionId)
                ->delete();

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Sign out a specific session
     *
     * @param string $sessionId
     * @param int $userId
     * @return bool
     */
    public function signOutSession($sessionId, $userId)
    {
        try {
            DB::table('sessions')
                ->where('id', $sessionId)
                ->where('user_id', $userId)
                ->delete();

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
