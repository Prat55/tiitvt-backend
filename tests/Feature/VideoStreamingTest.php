<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

class VideoStreamingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Sanctum::actingAs(User::factory()->create());
    }

    public function test_streaming_returns_partial_content_for_range_request()
    {
        Storage::fake('public');
        $filename = 'test_video.mp4';
        $content = str_repeat('a', 10 * 1024); // 10KB
        Storage::disk('public')->put($filename, $content);

        $path = base64_encode($filename);

        $response = $this->get(route('api.videos.stream', ['path' => $path]), [
            'Range' => 'bytes=0-1023'
        ]);

        $response->assertStatus(206);
        $response->assertHeader('Content-Range', 'bytes 0-1023/10240');
        $response->assertHeader('Content-Length', '1024');
    }

    public function test_streaming_returns_404_for_non_existent_file()
    {
        Storage::fake('public');
        $path = base64_encode('non_existent.mp4');

        $response = $this->get(route('api.videos.stream', ['path' => $path]));

        $response->assertStatus(404);
    }

    public function test_streaming_returns_full_content_without_range_header()
    {
        Storage::fake('public');
        $filename = 'test_video.mp4';
        $content = str_repeat('a', 10 * 1024); // 10KB
        Storage::disk('public')->put($filename, $content);

        $path = base64_encode($filename);

        $response = $this->get(route('api.videos.stream', ['path' => $path]));

        $response->assertStatus(200);
        $response->assertHeader('Content-Length', '10240');
        $response->assertHeader('Accept-Ranges', 'bytes');
    }
}
