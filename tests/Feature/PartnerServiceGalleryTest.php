<?php

namespace Tests\Feature;

use App\Models\Partner;
use App\Models\Service;
use App\Models\ServiceType;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PartnerServiceGalleryTest extends TestCase
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

        return ['Authorization' => 'Bearer ' . $token, 'X-Site-Key' => 'ksp'];
    }

    public function test_partner_phone_is_saved_and_exposed_publicly(): void
    {
        $response = $this->withHeaders($this->headers())
            ->post('/api/v1/admin/partners', [
                'name' => 'Bank Contoh',
                'phone' => '081234567890',
                'logo' => UploadedFile::fake()->create('logo.png', 32, 'image/png'),
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.phone', '081234567890');

        $this->assertDatabaseHas('partners', [
            'id' => $response->json('data.id'),
            'phone' => '081234567890',
        ]);

        $this->withHeader('X-Site-Key', 'ksp')
            ->getJson('/api/v1/partners')
            ->assertStatus(200)
            ->assertJsonFragment(['phone' => '081234567890']);
    }

    public function test_partner_phone_can_be_updated(): void
    {
        $site = Site::where('slug', 'ksp')->firstOrFail();
        $partner = Partner::create([
            'site_id' => $site->id,
            'name' => 'Mitra Lama',
            'logo_path' => 'partners/ksp/logo.png',
        ]);

        $this->withHeaders($this->headers())
            ->putJson("/api/v1/admin/partners/{$partner->id}", ['phone' => '087700001111'])
            ->assertStatus(200)
            ->assertJsonPath('data.phone', '087700001111');
    }

    public function test_service_accepts_multiple_gallery_images(): void
    {
        $site = Site::where('slug', 'ksp')->firstOrFail();
        $type = ServiceType::where('site_id', $site->id)->firstOrFail();

        $response = $this->withHeaders($this->headers())
            ->post('/api/v1/admin/services', [
                'service_type_id' => $type->id,
                'name' => 'Produk Galeri',
                'slug' => 'produk-galeri',
                'short_desc' => 'Produk dengan beberapa foto',
                'thumbnail' => UploadedFile::fake()->create('cover.jpg', 64, 'image/jpeg'),
                'images' => [
                    UploadedFile::fake()->create('foto-1.jpg', 64, 'image/jpeg'),
                    UploadedFile::fake()->create('foto-2.jpg', 64, 'image/jpeg'),
                ],
            ])
            ->assertStatus(201)
            ->assertJsonCount(2, 'data.images')
            ->assertJsonCount(2, 'data.image_urls');

        $service = Service::findOrFail($response->json('data.id'));
        $this->assertCount(2, $service->images);

        $this->withHeader('X-Site-Key', 'ksp')
            ->getJson('/api/v1/services/produk-galeri')
            ->assertStatus(200)
            ->assertJsonCount(2, 'data.images')
            ->assertJsonCount(2, 'data.image_urls');
    }

    public function test_service_gallery_is_replaced_when_new_files_are_sent(): void
    {
        $site = Site::where('slug', 'ksp')->firstOrFail();
        $type = ServiceType::where('site_id', $site->id)->firstOrFail();

        $create = $this->withHeaders($this->headers())
            ->post('/api/v1/admin/services', [
                'service_type_id' => $type->id,
                'name' => 'Produk Ganti Galeri',
                'slug' => 'produk-ganti-galeri',
                'short_desc' => 'Deskripsi',
                'images' => [
                    UploadedFile::fake()->create('a.jpg', 32, 'image/jpeg'),
                    UploadedFile::fake()->create('b.jpg', 32, 'image/jpeg'),
                ],
            ])
            ->assertStatus(201);

        $id = $create->json('data.id');

        $this->withHeaders($this->headers())
            ->post("/api/v1/admin/services/{$id}", [
                '_method' => 'PUT',
                'images' => [UploadedFile::fake()->create('c.jpg', 32, 'image/jpeg')],
            ])
            ->assertStatus(200)
            ->assertJsonCount(1, 'data.images');
    }

    public function test_testimonial_accepts_screenshot_image(): void
    {
        $response = $this->withHeaders($this->headers())
            ->post('/api/v1/admin/testimonials', [
                'name' => 'Budi',
                'message' => 'Pelayanan bagus dan tepat waktu.',
                'image' => UploadedFile::fake()->create('wa.png', 32, 'image/png'),
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.name', 'Budi');

        $this->assertNotNull($response->json('data.image_path'));
        $this->assertNotNull($response->json('data.image_url'));

        $this->assertDatabaseHas('testimonials', [
            'id' => $response->json('data.id'),
            'image_path' => $response->json('data.image_path'),
        ]);
    }

    public function test_gallery_images_are_registered_in_media_usage(): void
    {
        $site = Site::where('slug', 'ksp')->firstOrFail();
        $type = ServiceType::where('site_id', $site->id)->firstOrFail();

        $response = $this->withHeaders($this->headers())
            ->post('/api/v1/admin/services', [
                'service_type_id' => $type->id,
                'name' => 'Produk Media',
                'slug' => 'produk-media',
                'short_desc' => 'Deskripsi',
                'images' => [UploadedFile::fake()->create('media.jpg', 32, 'image/jpeg')],
            ])
            ->assertStatus(201);

        $path = $response->json('data.images.0');

        $media = $this->withHeaders($this->headers())
            ->getJson('/api/v1/admin/media?q=' . urlencode($path))
            ->assertStatus(200);

        $item = collect($media->json('data'))->firstWhere('path', $path);
        $this->assertNotNull($item);
        $this->assertSame('service', $item['usage'][0]['type']);
        $this->assertSame('images.0', $item['usage'][0]['field']);
    }
}
