<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_missing_site_key_returns_400(): void
    {
        $response = $this->getJson('/api/v1/settings');

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Header X-Site-Key is required',
            ]);
    }

    public function test_invalid_site_key_returns_404(): void
    {
        $response = $this->withHeader('X-Site-Key', 'invalid-site-key')
            ->getJson('/api/v1/settings');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Site not found or inactive',
            ]);
    }

    public function test_settings_endpoint_with_valid_api_key(): void
    {
        $response = $this->withHeader('X-Site-Key', 'key-ksp-12345')
            ->getJson('/api/v1/settings');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'general' => ['site_title', 'tagline'],
                    'contact' => ['phone', 'email'],
                ],
            ]);
    }

    public function test_tenancy_works_with_site_slug_in_header(): void
    {
        $response = $this->withHeader('X-Site-Key', 'ksp')
            ->getJson('/api/v1/settings');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    public function test_page_detail_with_sections(): void
    {
        $response = $this->withHeader('X-Site-Key', 'key-ksp-12345')
            ->getJson('/api/v1/pages/home');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.slug', 'home')
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'slug',
                    'title',
                    'sections' => [
                        '*' => ['id', 'key', 'title', 'order', 'image_url'],
                    ],
                ],
            ]);
    }

    public function test_service_types_endpoint(): void
    {
        $response = $this->withHeader('X-Site-Key', 'key-ksp-12345')
            ->getJson('/api/v1/service-types');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['id', 'name', 'slug', 'services_count'],
                ],
            ]);
    }

    public function test_services_index_and_detail(): void
    {
        $indexResponse = $this->withHeader('X-Site-Key', 'key-ksp-12345')
            ->getJson('/api/v1/services');

        $indexResponse->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'data',
                'meta' => ['current_page', 'per_page', 'total'],
            ]);

        $detailResponse = $this->withHeader('X-Site-Key', 'key-ksp-12345')
            ->getJson('/api/v1/services/simpanan-sukarela-sejahtera');

        $detailResponse->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.slug', 'simpanan-sukarela-sejahtera')
            ->assertJsonStructure([
                'data' => ['features', 'service_type'],
            ]);
    }

    public function test_portfolios_endpoints(): void
    {
        $response = $this->withHeader('X-Site-Key', 'key-ksp-12345')
            ->getJson('/api/v1/portfolios');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $detail = $this->withHeader('X-Site-Key', 'key-ksp-12345')
            ->getJson('/api/v1/portfolios/proyek-unggulan-ksp');

        $detail->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.slug', 'proyek-unggulan-ksp');
    }

    public function test_articles_endpoints(): void
    {
        $catResponse = $this->withHeader('X-Site-Key', 'key-ksp-12345')
            ->getJson('/api/v1/article-categories');

        $catResponse->assertStatus(200)
            ->assertJsonPath('success', true);

        $indexResponse = $this->withHeader('X-Site-Key', 'key-ksp-12345')
            ->getJson('/api/v1/articles');

        $indexResponse->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['title', 'slug', 'category', 'author'],
                ],
            ]);

        $showResponse = $this->withHeader('X-Site-Key', 'key-ksp-12345')
            ->getJson('/api/v1/articles/tips-memilih-layanan-terbaik-untuk-usaha');

        $showResponse->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.slug', 'tips-memilih-layanan-terbaik-untuk-usaha');
    }

    public function test_faqs_testimonials_partners(): void
    {
        $faq = $this->withHeader('X-Site-Key', 'key-ksp-12345')->getJson('/api/v1/faqs');
        $faq->assertStatus(200)->assertJsonPath('success', true);

        $faqGrouped = $this->withHeader('X-Site-Key', 'key-ksp-12345')->getJson('/api/v1/faqs?grouped=true');
        $faqGrouped->assertStatus(200)->assertJsonPath('success', true);

        $testimonials = $this->withHeader('X-Site-Key', 'key-ksp-12345')->getJson('/api/v1/testimonials');
        $testimonials->assertStatus(200)->assertJsonPath('success', true);

        $partners = $this->withHeader('X-Site-Key', 'key-ksp-12345')->getJson('/api/v1/partners');
        $partners->assertStatus(200)->assertJsonPath('success', true);
    }

    public function test_inquiry_store_validation_and_creation(): void
    {
        // Validation error
        $failResponse = $this->withHeader('X-Site-Key', 'key-ksp-12345')
            ->postJson('/api/v1/inquiries', []);

        $failResponse->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'phone', 'message']);

        // Successful submission
        $successResponse = $this->withHeader('X-Site-Key', 'key-ksp-12345')
            ->postJson('/api/v1/inquiries', [
                'name' => 'Budi Santoso',
                'phone' => '08123456789',
                'email' => 'budi@example.com',
                'subject' => 'Pertanyaan Simpanan',
                'message' => 'Halo, saya ingin menanyakan ketentuan simpanan berjangka.',
            ]);

        $successResponse->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'new')
            ->assertJsonPath('data.name', 'Budi Santoso');

        // Test with file upload (within 10MB)
        \Illuminate\Support\Facades\Storage::fake('public');
        $file = \Illuminate\Http\UploadedFile::fake()->create('sample.pdf', 500, 'application/pdf');

        $uploadResponse = $this->withHeader('X-Site-Key', 'key-ksp-12345')
            ->post('/api/v1/inquiries', [
                'name' => 'Doni Konveksi',
                'phone' => '08129876543',
                'message' => 'Minta penawaran bordir jaket sesuai file.',
                'attachment' => $file,
            ], ['Accept' => 'application/json']);

        $uploadResponse->assertStatus(201)
            ->assertJsonPath('success', true);

        // Test file exceeding 10MB (10240 KB)
        $largeFile = \Illuminate\Http\UploadedFile::fake()->create('heavy.pdf', 11000, 'application/pdf');
        $oversizedResponse = $this->withHeader('X-Site-Key', 'key-ksp-12345')
            ->post('/api/v1/inquiries', [
                'name' => 'Doni Konveksi',
                'phone' => '08129876543',
                'message' => 'File terlalu besar.',
                'attachment' => $largeFile,
            ], ['Accept' => 'application/json']);

        $oversizedResponse->assertStatus(422)
            ->assertJsonValidationErrors(['attachment']);
    }

    public function test_docs_and_openapi_specification(): void
    {
        $docs = $this->get('/docs');
        $docs->assertStatus(200);

        $spec = $this->get('/openapi.json');
        $spec->assertStatus(200)
            ->assertHeader('Content-Type', 'application/json');
    }
}
