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
