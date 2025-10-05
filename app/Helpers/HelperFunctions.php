<?php

use App\Models\WebsiteSetting;

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
