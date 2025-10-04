<?php

namespace App\Services;

use App\Models\WebsiteSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class WebsiteSettingsService
{
    /**
     * Get website settings with caching.
     */
    public function getSettings(): ?WebsiteSetting
    {
        return Cache::remember('website_settings', 3600, function () {
            return WebsiteSetting::first();
        });
    }

    /**
     * Clear cache after updating settings.
     */
    public function clearCache(): void
    {
        Cache::forget('website_settings');
    }

    /**
     * Get logo URL.
     */
    public function getLogoUrl(): ?string
    {
        $settings = $this->getSettings();
        return $settings?->logo ? Storage::url($settings->logo) : null;
    }

    /**
     * Get dark logo URL.
     */
    public function getDarkLogoUrl(): ?string
    {
        $settings = $this->getSettings();
        return $settings?->logo_dark ? Storage::url($settings->logo_dark) : $this->getLogoUrl();
    }

    /**
     * Get favicon URL.
     */
    public function getFaviconUrl(): ?string
    {
        $settings = $this->getSettings();
        return $settings?->favicon ? Storage::url($settings->favicon) : null;
    }

    /**
     * Get QR code image URL.
     */
    public function getQrCodeImageUrl(): ?string
    {
        $settings = $this->getSettings();
        return $settings?->qr_code_image ? Storage::url($settings->qr_code_image) : null;
    }

    /**
     * Get website name.
     */
    public function getWebsiteName(): string
    {
        $settings = $this->getSettings();
        return $settings?->website_name ?? 'TIITVT';
    }

    /**
     * Get meta title.
     */
    public function getMetaTitle(): string
    {
        $settings = $this->getSettings();
        return $settings?->meta_title ?? 'TIITVT - Technical Institute of Information Technology';
    }

    /**
     * Get meta description.
     */
    public function getMetaDescription(): string
    {
        $settings = $this->getSettings();
        return $settings?->meta_description ?? 'Technical Institute of Information Technology provides comprehensive IT training programs.';
    }

    /**
     * Get meta keywords.
     */
    public function getMetaKeywords(): string
    {
        $settings = $this->getSettings();
        return $settings?->meta_keywords ?? 'technical training, IT courses, professional certification';
    }
}
