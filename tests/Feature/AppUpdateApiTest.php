<?php

namespace Tests\Feature;

use App\Models\AppUpdate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppUpdateApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_returns_latest_version_for_type()
    {
        // Setup older update
        AppUpdate::create([
            'type' => 'tiitvt',
            'version' => '1.0.0',
            'version_code' => 1,
            'apk_path' => 'app-updates/v1.apk',
            'published_at' => now()->subDay(),
        ]);

        // Setup newer update
        AppUpdate::create([
            'type' => 'tiitvt',
            'version' => '1.1.0',
            'version_code' => 2,
            'apk_path' => 'app-updates/v1.1.apk',
            'published_at' => now()->subHour(),
        ]);

        // Setup update for different type
        AppUpdate::create([
            'type' => 'it-centre',
            'version' => '2.0.0',
            'version_code' => 5,
            'apk_path' => 'app-updates/it-v2.apk',
            'published_at' => now()->subHour(),
        ]);

        $response = $this->getJson('/api/app-updates/latest?type=tiitvt');

        $response->assertStatus(200)
            ->assertJson([
                'version' => '1.1.0',
                'version_code' => 2,
            ]);
    }

    public function test_api_returns_404_if_no_updates()
    {
        $response = $this->getJson('/api/app-updates/latest?type=tiitvt');

        $response->assertStatus(404);
    }

    public function test_api_validates_type()
    {
        $response = $this->getJson('/api/app-updates/latest?type=invalid');

        $response->assertStatus(422);
    }
}
