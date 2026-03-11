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
        $this->assertSame(substr($content, 0, 1024), $response->streamedContent());
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
        $this->assertSame($content, $response->streamedContent());
    }

    public function test_streaming_handles_large_initial_range_without_corrupting_output()
    {
        Storage::fake('public');
        $filename = 'test_video.mp4';
        $content = str_repeat('0123456789', 200000); // 2,000,000 bytes
        Storage::disk('public')->put($filename, $content);

        $path = base64_encode($filename);

        $response = $this->get(route('api.videos.stream', ['path' => $path]), [
            'Range' => 'bytes=0-1000000'
        ]);

        $response->assertStatus(206);
        $response->assertHeader('Content-Range', 'bytes 0-1000000/2000000');
        $response->assertHeader('Content-Length', '1000001');
        $this->assertSame(substr($content, 0, 1000001), $response->streamedContent());
    }

    public function test_streaming_rejects_unsatisfiable_ranges()
    {
        Storage::fake('public');
        $filename = 'test_video.mp4';
        $content = str_repeat('a', 10 * 1024); // 10KB
        Storage::disk('public')->put($filename, $content);

        $path = base64_encode($filename);

        $response = $this->get(route('api.videos.stream', ['path' => $path]), [
            'Range' => 'bytes=999999-1000000'
        ]);

        $response->assertStatus(416);
        $response->assertHeader('Content-Range', 'bytes */10240');
        $response->assertHeader('Content-Length', '0');
    }
}
