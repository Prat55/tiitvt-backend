<?php

namespace Database\Seeders;

use App\Models\WebsiteSetting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class WebsiteSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if website settings already exist
        if (WebsiteSetting::count() > 0) {
            $this->command->info('Website settings already exist. Skipping...');
            return;
        }

        // Create default website settings
        WebsiteSetting::create([
            'website_name' => 'TIITVT - Technical Institute of Information Technology',
            'logo' => null, // Default logo path - will fallback to public asset
            'logo_dark' => null, // Dark theme logo path
            'favicon' => null, // Favicon path - will fallback to default favicon
            'qr_code_image' => null, // QR code image path
            'meta_title' => 'TIITVT - Technical Institute of Information Technology | Professional Training & Certification',
            'meta_keywords' => 'technical training, IT courses, professional certification, skill development, computer science, business management, engineering',
            'meta_description' => 'TIITVT offers comprehensive technical training and professional certification programs in IT, business, engineering, and creative arts. Transform your career with our industry-focused courses.',
            'meta_author' => 'TIITVT',
            'primary_email' => 'info@tiitvt.com',
            'secondary_email' => 'support@tiitvt.com',
            'primary_phone' => '+91-98765-43210',
            'secondary_phone' => '+91-98765-43211',
            'address' => '123 Technical Park, Innovation Street, Tech City, State - 123456, India',
            'facebook_url' => 'https://facebook.com/tiitvt',
            'twitter_url' => 'https://twitter.com/tiitvt',
            'instagram_url' => 'https://instagram.com/tiitvt',
            'linkedin_url' => 'https://linkedin.com/company/tiitvt',
        ]);

        $this->command->info('Website settings seeded successfully!');
    }
}
