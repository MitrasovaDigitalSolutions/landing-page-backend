<?php

namespace Tests\Feature;

use App\Models\Service;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaLibraryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Storage::fake('public');
    }

    private function headers(): array
    {
        $token = $this->postJson('/api/v1/auth/login', [
            'email' => 'admin@mitrasova.com',
            'password' => 'password123',
        ])->json('data.token');

        return [
            'Authorization' => 'Bearer ' . $token,
            'X-Site-Key' => 'ksp',
        ];
    }

    public function test_media_list_shows_files_with_usage_and_unused_filter(): void
    {
        $site = Site::where('slug', 'ksp')->first();
        $service = Service::where('site_id', $site->id)->firstOrFail();

        Storage::disk('public')->put('services/ksp/thumb.jpg', 'old');
        Storage::disk('public')->put('services/ksp/unused.jpg', 'old');

        $service->update(['thumbnail_path' => 'services/ksp/thumb.jpg']);

        $res = $this->withHeaders($this->headers())->getJson('/api/v1/admin/media');

        $res->assertStatus(200)->assertJsonPath('meta.total', 2);

        $items = collect($res->json('data'))->keyBy('path');
        $this->assertSame('service', $items['services/ksp/thumb.jpg']['usage'][0]['type']);
        $this->assertSame($service->id, $items['services/ksp/thumb.jpg']['usage'][0]['id']);
        $this->assertSame([], $items['services/ksp/unused.jpg']['usage']);

        $unused = $this->withHeaders($this->headers())->getJson('/api/v1/admin/media?unused=1');
        $unused->assertStatus(200)->assertJsonCount(1, 'data');
        $this->assertSame('services/ksp/unused.jpg', $unused->json('data.0.path'));
    }

    public function test_delete_is_refused_while_used_unless_forced(): void
    {
        $site = Site::where('slug', 'ksp')->first();
        $service = Service::where('site_id', $site->id)->firstOrFail();

        Storage::disk('public')->put('services/ksp/used.jpg', 'old');
        $service->update(['thumbnail_path' => 'services/ksp/used.jpg']);

        $this->withHeaders($this->headers())
            ->deleteJson('/api/v1/admin/media', ['path' => 'services/ksp/used.jpg'])
            ->assertStatus(409)
            ->assertJsonPath('data.usage.0.type', 'service');

        Storage::disk('public')->assertExists('services/ksp/used.jpg');

        $this->withHeaders($this->headers())
            ->deleteJson('/api/v1/admin/media', ['path' => 'services/ksp/used.jpg', 'force' => true])
            ->assertStatus(200);

        Storage::disk('public')->assertMissing('services/ksp/used.jpg');
    }

    public function test_replace_creates_new_path_and_updates_references(): void
    {
        $site = Site::where('slug', 'ksp')->first();
        $service = Service::where('site_id', $site->id)->firstOrFail();

        Storage::disk('public')->put('services/ksp/swap.jpg', 'old-bytes');
        Storage::disk('public')->put('services/ksp/old2.jpg', 'old-bytes');
        $service->update(['thumbnail_path' => 'services/ksp/swap.jpg']);

        // ekstensi boleh berbeda karena file disimpan sebagai path baru
        $res = $this->withHeaders($this->headers())
            ->post('/api/v1/admin/media/replace', [
                'path' => 'services/ksp/swap.jpg',
                'file' => UploadedFile::fake()->image('new.png'),
            ])
            ->assertStatus(200);

        $newPath = $res->json('data.path');

        $this->assertNotSame('services/ksp/swap.jpg', $newPath);
        $this->assertSame(1, $res->json('data.references_updated'));
        $this->assertSame($newPath, $service->fresh()->thumbnail_path);
        Storage::disk('public')->assertExists($newPath);
        Storage::disk('public')->assertMissing('services/ksp/swap.jpg');
    }

    public function test_replace_unused_file_removes_old_one(): void
    {
        Storage::disk('public')->put('services/ksp/old2.jpg', 'old-bytes');

        $res = $this->withHeaders($this->headers())
            ->post('/api/v1/admin/media/replace', [
                'path' => 'services/ksp/old2.jpg',
                'file' => UploadedFile::fake()->image('baru.jpg'),
            ])
            ->assertStatus(200);

        $this->assertSame(0, $res->json('data.references_updated'));
        Storage::disk('public')->assertMissing('services/ksp/old2.jpg');
    }

    public function test_media_is_scoped_per_site(): void
    {
        Storage::disk('public')->put('services/konveksi/other.jpg', 'x');

        $res = $this->withHeaders($this->headers())->getJson('/api/v1/admin/media');
        $res->assertStatus(200)->assertJsonCount(0, 'data');

        $this->withHeaders($this->headers())
            ->deleteJson('/api/v1/admin/media', ['path' => 'services/konveksi/other.jpg', 'force' => true])
            ->assertStatus(403);

        Storage::disk('public')->assertExists('services/konveksi/other.jpg');
    }

    public function test_media_rejects_path_traversal(): void
    {
        $this->withHeaders($this->headers())
            ->deleteJson('/api/v1/admin/media', ['path' => '../../.env', 'force' => true])
            ->assertStatus(422);
    }
}
