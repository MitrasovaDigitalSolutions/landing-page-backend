<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TenantLogoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Storage::fake('public');
    }

    private function headers(string $siteKey): array
    {
        $token = $this->postJson('/api/v1/auth/login', [
            'email' => 'admin@mitrasova.com',
            'password' => 'password123',
        ])->json('data.token');

        return ['Authorization' => 'Bearer ' . $token, 'X-Site-Key' => $siteKey, 'Accept' => 'application/json'];
    }

    private function fakeLogo(string $name = 'logo.png'): UploadedFile
    {
        return UploadedFile::fake()->create($name, 32, 'image/png');
    }

    public function test_admin_uploads_logo_and_public_settings_expose_it(): void
    {
        $response = $this->withHeaders($this->headers('transportasi'))
            ->post('/api/v1/admin/settings', ['logo' => $this->fakeLogo()])
            ->assertStatus(200)
            ->assertJsonPath('success', true);

        $path = $response->json('data.logo_path');

        $this->assertStringStartsWith('branding/transportasi-logo-', $path);
        Storage::disk('public')->assertExists($path);
        $this->assertSame(
            $path,
            \App\Models\SiteSetting::where('site_id', \App\Models\Site::where('slug', 'transportasi')->firstOrFail()->id)
                ->where('key', 'logo_path')
                ->firstOrFail()
                ->value
        );

        $settings = $this->withHeader('X-Site-Key', 'transportasi')
            ->getJson('/api/v1/settings')
            ->assertStatus(200);

        $this->assertSame($path, $settings->json('data.general.logo_path'));
        $this->assertSame(url('storage/' . $path), $settings->json('data.general.logo_url'));
    }

    public function test_replacing_logo_creates_new_path(): void
    {
        $first = $this->withHeaders($this->headers('transportasi'))
            ->post('/api/v1/admin/settings', ['logo' => $this->fakeLogo()])
            ->assertStatus(200)
            ->json('data.logo_path');

        $second = $this->withHeaders($this->headers('transportasi'))
            ->post('/api/v1/admin/settings', ['logo' => $this->fakeLogo('logo-baru.png')])
            ->assertStatus(200)
            ->json('data.logo_path');

        $this->assertNotSame($first, $second);
        Storage::disk('public')->assertExists($first);
        Storage::disk('public')->assertExists($second);
    }

    public function test_logo_upload_rejects_non_image(): void
    {
        $this->withHeaders($this->headers('transportasi'))
            ->post('/api/v1/admin/settings', [
                'logo' => UploadedFile::fake()->create('logo.pdf', 32, 'application/pdf'),
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['logo']);
    }

    public function test_logo_upload_only_affects_current_tenant(): void
    {
        $kspLogo = $this->withHeader('X-Site-Key', 'ksp')
            ->getJson('/api/v1/settings')
            ->json('data.general.logo_path');

        $this->withHeaders($this->headers('transportasi'))
            ->post('/api/v1/admin/settings', ['logo' => $this->fakeLogo()])
            ->assertStatus(200);

        $this->assertSame(
            $kspLogo,
            $this->withHeader('X-Site-Key', 'ksp')->getJson('/api/v1/settings')->json('data.general.logo_path')
        );
    }
}
