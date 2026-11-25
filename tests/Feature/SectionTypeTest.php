<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\SectionType;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SectionTypeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function token(): string
    {
        return $this->postJson('/api/v1/auth/login', [
            'email' => 'admin@mitrasova.com',
            'password' => 'password123',
        ])->json('data.token');
    }

    private function headers(): array
    {
        return ['Authorization' => 'Bearer ' . $this->token(), 'X-Site-Key' => 'ksp'];
    }

    private function createPage(): int
    {
        $site = Site::where('slug', 'ksp')->first();

        return Page::create([
            'site_id' => $site->id,
            'slug' => 'profil',
            'title' => 'Profil',
        ])->id;
    }

    public function test_seeder_creates_eleven_types_with_alias(): void
    {
        $keys = SectionType::orderBy('key')->pluck('key')->all();

        $expected = ['about', 'cta', 'featured', 'hero', 'instagram', 'office', 'program', 'sejarah', 'strategi', 'values', 'visi-misi'];
        sort($expected);

        $this->assertCount(11, $keys);
        $this->assertSame($expected, $keys);
        $this->assertSame(['vision_mission'], SectionType::where('key', 'visi-misi')->first()->aliases);
    }

    public function test_public_endpoint_lists_active_types_with_site_key(): void
    {
        $this->withHeader('X-Site-Key', 'ksp')
            ->getJson('/api/v1/section-types')
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(11, 'data')
            ->assertJsonStructure([
                'data' => [
                    ['id', 'key', 'name', 'description', 'fields', 'aliases', 'is_active'],
                ],
            ]);
    }

    public function test_public_endpoint_requires_site_key(): void
    {
        $this->getJson('/api/v1/section-types')->assertStatus(400);
    }

    public function test_admin_endpoint_requires_token_and_lists_all_types(): void
    {
        $this->withHeader('X-Site-Key', 'ksp')
            ->getJson('/api/v1/admin/section-types')
            ->assertStatus(401);

        $this->withHeaders($this->headers())
            ->getJson('/api/v1/admin/section-types')
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(11, 'data');
    }

    public function test_store_valid_visi_misi_returns_201_without_warnings(): void
    {
        $pageId = $this->createPage();

        $this->withHeaders($this->headers())
            ->postJson("/api/v1/admin/pages/{$pageId}/sections", [
                'key' => 'visi-misi',
                'payload' => [
                    'visi' => 'Menjadi ekosistem keuangan berbasis komunitas yang inovatif.',
                    'misi' => [
                        ['title' => 'Melayani Semua Lapisan', 'desc' => 'Menyediakan layanan simpan pinjam yang mudah diakses.'],
                    ],
                ],
            ])
            ->assertStatus(201)
            ->assertJsonCount(0, 'warnings');
    }

    public function test_store_repeater_item_without_required_title_returns_payload_error(): void
    {
        $pageId = $this->createPage();

        $this->withHeaders($this->headers())
            ->postJson("/api/v1/admin/pages/{$pageId}/sections", [
                'key' => 'visi-misi',
                'payload' => [
                    'misi' => [
                        ['desc' => 'Tanpa judul'],
                    ],
                ],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['payload.misi.0.title']);
    }

    public function test_store_accepts_loose_anchor_url(): void
    {
        $pageId = $this->createPage();

        $this->withHeaders($this->headers())
            ->postJson("/api/v1/admin/pages/{$pageId}/sections", [
                'key' => 'cta',
                'payload' => ['button_url' => '#kontak'],
            ])
            ->assertStatus(201);
    }

    public function test_store_rejects_invalid_url(): void
    {
        $pageId = $this->createPage();

        $this->withHeaders($this->headers())
            ->postJson("/api/v1/admin/pages/{$pageId}/sections", [
                'key' => 'cta',
                'payload' => ['button_url' => 'bukan url'],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['payload.button_url']);
    }

    public function test_unknown_key_allowed_with_warning_when_not_strict(): void
    {
        config(['cms.section_types.strict' => false]);
        $pageId = $this->createPage();

        $response = $this->withHeaders($this->headers())
            ->postJson("/api/v1/admin/pages/{$pageId}/sections", [
                'key' => 'legacy-banner',
                'payload' => ['whatever' => 'value'],
            ])
            ->assertStatus(201);

        $this->assertNotEmpty($response->json('warnings'));
    }

    public function test_unknown_key_rejected_when_strict(): void
    {
        config(['cms.section_types.strict' => true]);
        $pageId = $this->createPage();

        $this->withHeaders($this->headers())
            ->postJson("/api/v1/admin/pages/{$pageId}/sections", [
                'key' => 'legacy-banner',
                'payload' => ['whatever' => 'value'],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['key']);
    }

    public function test_alias_is_recognized_and_key_is_preserved(): void
    {
        config(['cms.section_types.strict' => true]);
        $pageId = $this->createPage();

        $this->withHeaders($this->headers())
            ->postJson("/api/v1/admin/pages/{$pageId}/sections", [
                'key' => 'vision_mission',
                'payload' => ['visi' => 'Visi kami'],
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.key', 'vision_mission')
            ->assertJsonCount(0, 'warnings');
    }

    public function test_empty_payload_is_always_accepted(): void
    {
        $pageId = $this->createPage();

        $this->withHeaders($this->headers())
            ->postJson("/api/v1/admin/pages/{$pageId}/sections", [
                'key' => 'hero',
                'payload' => [],
            ])
            ->assertStatus(201);
    }

    public function test_extra_payload_fields_are_preserved(): void
    {
        $pageId = $this->createPage();

        $this->withHeaders($this->headers())
            ->postJson("/api/v1/admin/pages/{$pageId}/sections", [
                'key' => 'hero',
                'payload' => ['badge' => 'KSP', 'legacy_field' => 'keep-me'],
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.payload.legacy_field', 'keep-me');
    }

    public function test_checker_command_exits_zero_on_seed_data(): void
    {
        $this->artisan('cms:check-section-payloads')
            ->assertExitCode(0);
    }
}
