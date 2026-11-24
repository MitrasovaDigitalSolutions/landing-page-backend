<?php

namespace Tests\Feature;

use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_auth_login_successful_and_unauthorized_checks(): void
    {
        // 1. Login with superadmin
        $loginRes = $this->postJson('/api/v1/auth/login', [
            'email' => 'admin@mitrasova.com',
            'password' => 'password123',
        ]);

        $loginRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['token', 'user' => ['id', 'email', 'is_superadmin', 'sites']]]);

        $token = $loginRes->json('data.token');

        // 2. Access /auth/me
        $meRes = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/auth/me');

        $meRes->assertStatus(200)
            ->assertJsonPath('data.email', 'admin@mitrasova.com');
    }

    public function test_admin_endpoints_require_token(): void
    {
        $this->withHeader('X-Site-Key', 'ksp')
            ->getJson('/api/v1/admin/services')
            ->assertStatus(401);
    }

    public function test_admin_crud_service_and_permissions(): void
    {
        // Superadmin login
        $loginRes = $this->postJson('/api/v1/auth/login', [
            'email' => 'admin@mitrasova.com',
            'password' => 'password123',
        ]);
        $token = $loginRes->json('data.token');

        // 1. Create service type
        $typeRes = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->withHeader('X-Site-Key', 'ksp')
            ->postJson('/api/v1/admin/service-types', [
                'name' => 'Pembiayaan Syariah',
                'description' => 'Layanan berbasis syariah',
            ]);

        $typeRes->assertStatus(201)
            ->assertJsonPath('data.name', 'Pembiayaan Syariah');

        $typeId = $typeRes->json('data.id');

        // 2. Create service
        $serviceRes = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->withHeader('X-Site-Key', 'ksp')
            ->postJson('/api/v1/admin/services', [
                'service_type_id' => $typeId,
                'name' => 'Pembiayaan Usaha Murabahah',
                'short_desc' => 'Pembiayaan akad jual beli',
                'body' => 'Deskripsi lengkap akad murabahah',
                'features' => ['Tanpa riba', 'Margin transparan'],
            ]);

        $serviceRes->assertStatus(201)
            ->assertJsonPath('data.name', 'Pembiayaan Usaha Murabahah');

        $serviceId = $serviceRes->json('data.id');

        // 3. Update service
        $updateRes = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->withHeader('X-Site-Key', 'ksp')
            ->putJson("/api/v1/admin/services/{$serviceId}", [
                'name' => 'Pembiayaan Usaha Murabahah Updated',
            ]);

        $updateRes->assertStatus(200)
            ->assertJsonPath('data.name', 'Pembiayaan Usaha Murabahah Updated');

        // 4. Delete service
        $deleteRes = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->withHeader('X-Site-Key', 'ksp')
            ->deleteJson("/api/v1/admin/services/{$serviceId}");

        $deleteRes->assertStatus(200);
    }

    public function test_tenant_authorization_forbidden_for_other_site_admin(): void
    {
        // Create user who is only admin for 'konveksi'
        $user = User::factory()->create([
            'is_superadmin' => false,
            'password' => bcrypt('password123'),
        ]);

        $konveksi = Site::where('slug', 'konveksi')->first();
        $user->sites()->attach($konveksi->id, ['role' => 'admin']);

        // Login as site admin
        $loginRes = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);
        $token = $loginRes->json('data.token');

        // Can access konveksi
        $allowedRes = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->withHeader('X-Site-Key', 'konveksi')
            ->getJson('/api/v1/admin/services');

        $allowedRes->assertStatus(200);

        // Forbidden on ksp
        $forbiddenRes = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->withHeader('X-Site-Key', 'ksp')
            ->getJson('/api/v1/admin/services');

        $forbiddenRes->assertStatus(403);
    }

    private function superadminToken(): string
    {
        return $this->postJson('/api/v1/auth/login', [
            'email' => 'admin@mitrasova.com',
            'password' => 'password123',
        ])->json('data.token');
    }

    public function test_update_testimonial_and_partner(): void
    {
        $token = $this->superadminToken();

        $testimonialId = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->withHeader('X-Site-Key', 'ksp')
            ->postJson('/api/v1/admin/testimonials', [
                'name' => 'Budi',
                'message' => 'Pelayanan cepat',
            ])->assertStatus(201)->json('data.id');

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->withHeader('X-Site-Key', 'ksp')
            ->putJson("/api/v1/admin/testimonials/{$testimonialId}", [
                'name' => 'Budi Santoso',
                'rating' => 4,
            ])->assertStatus(200)
            ->assertJsonPath('data.name', 'Budi Santoso')
            ->assertJsonPath('data.rating', 4);

        $partnerId = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->withHeader('X-Site-Key', 'ksp')
            ->post('/api/v1/admin/partners', [
                'name' => 'PT Contoh',
                'logo' => \Illuminate\Http\UploadedFile::fake()->image('logo.png', 100, 100),
            ], ['Accept' => 'application/json'])
            ->assertStatus(201)->json('data.id');

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->withHeader('X-Site-Key', 'ksp')
            ->putJson("/api/v1/admin/partners/{$partnerId}", [
                'name' => 'PT Contoh Jaya',
            ])->assertStatus(200)
            ->assertJsonPath('data.name', 'PT Contoh Jaya');
    }

    public function test_update_faq_category(): void
    {
        $token = $this->superadminToken();

        $categoryId = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->withHeader('X-Site-Key', 'ksp')
            ->postJson('/api/v1/admin/faq-categories', ['name' => 'Syarat Umum'])
            ->assertStatus(201)->json('data.id');

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->withHeader('X-Site-Key', 'ksp')
            ->putJson("/api/v1/admin/faq-categories/{$categoryId}", ['name' => 'Syarat & Ketentuan'])
            ->assertStatus(200)
            ->assertJsonPath('data.name', 'Syarat & Ketentuan');
    }

    public function test_superadmin_can_create_user_and_assign_role(): void
    {
        $token = $this->superadminToken();

        $userId = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/admin/users', [
                'name' => 'Admin Konveksi',
                'email' => 'admin.konveksi@mitrasova.com',
                'password' => 'rahasia123',
            ])->assertStatus(201)->json('data.id');

        $siteId = Site::where('slug', 'konveksi')->value('id');

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/v1/admin/sites/{$siteId}/users", [
                'user_id' => $userId,
                'role' => 'admin',
            ])->assertStatus(200);

        $this->assertDatabaseHas('site_user', [
            'site_id' => $siteId,
            'user_id' => $userId,
            'role' => 'admin',
        ]);
    }

    public function test_non_superadmin_forbidden_on_global_admin_routes(): void
    {
        $user = User::factory()->create([
            'is_superadmin' => false,
            'password' => bcrypt('password123'),
        ]);

        $token = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password123',
        ])->json('data.token');

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/admin/sites')
            ->assertStatus(403);
    }

    public function test_destroy_site_refused_when_it_has_services(): void
    {
        $token = $this->superadminToken();
        $ksp = Site::where('slug', 'ksp')->first();

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson("/api/v1/admin/sites/{$ksp->id}")
            ->assertStatus(422);

        $emptySiteId = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/admin/sites', ['name' => 'Unit Uji Coba'])
            ->assertStatus(201)->json('data.id');

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson("/api/v1/admin/sites/{$emptySiteId}")
            ->assertStatus(200);
    }

    public function test_admin_branches_and_business_units_crud(): void
    {
        $token = $this->superadminToken();
        $headers = ['Authorization' => 'Bearer ' . $token, 'X-Site-Key' => 'ksp'];

        $branchId = $this->withHeaders($headers)
            ->post('/api/v1/admin/branches', [
                'name' => 'Kantor Cabang Kerjo',
                'address' => 'Jl. Batujamus-Karanganyar Km.01, Bangunsari',
                'phone' => '(0271)-6493168',
                'is_primary' => true,
                'images' => [\Illuminate\Http\UploadedFile::fake()->image('kantor.jpg', 200, 150)],
            ], ['Accept' => 'application/json'])
            ->assertStatus(201)
            ->assertJsonPath('data.name', 'Kantor Cabang Kerjo')
            ->json('data.id');

        $this->withHeaders($headers)
            ->putJson("/api/v1/admin/branches/{$branchId}", ['phone' => '(0271)-1111111'])
            ->assertStatus(200)
            ->assertJsonPath('data.phone', '(0271)-1111111');

        $unitId = $this->withHeaders($headers)
            ->post('/api/v1/admin/business-units', [
                'name' => 'Mitra Sejati Percetakan',
                'address' => 'Jl. Jambangan - Grompol Km. 1',
                'image' => \Illuminate\Http\UploadedFile::fake()->image('unit.jpg', 200, 150),
            ], ['Accept' => 'application/json'])
            ->assertStatus(201)
            ->json('data.id');

        $this->withHeaders($headers)
            ->deleteJson("/api/v1/admin/business-units/{$unitId}")
            ->assertStatus(200);

        $this->withHeaders($headers)
            ->deleteJson("/api/v1/admin/branches/{$branchId}")
            ->assertStatus(200);
    }

    public function test_settings_update_ignores_derived_url_keys_and_allows_null(): void
    {
        $token = $this->superadminToken();
        $headers = ['Authorization' => 'Bearer ' . $token, 'X-Site-Key' => 'ksp'];

        $this->withHeaders($headers)
            ->postJson('/api/v1/admin/settings', [
                'settings' => [
                    ['group' => 'general', 'key' => 'brand_color', 'value' => '#123456'],
                    ['group' => 'contact', 'key' => 'address', 'value' => null],
                    // nilai turunan dari endpoint publik, dikirim ulang oleh dashboard -> harus diabaikan
                    ['group' => 'contact', 'key' => 'maps_embed_url', 'value' => null],
                    ['group' => 'general', 'key' => 'logo_url', 'value' => null],
                ],
            ])
            ->assertStatus(200)
            ->assertJsonPath('success', true);

        $settings = $this->withHeaders($headers)
            ->getJson('/api/v1/admin/settings')
            ->assertStatus(200);

        $this->assertSame('#123456', $settings->json('data.general.brand_color'));
        $this->assertArrayNotHasKey('maps_embed_url', $settings->json('data.contact') ?? []);
        $this->assertArrayNotHasKey('logo_url', $settings->json('data.general') ?? []);
    }

    public function test_stale_url_setting_row_never_overrides_derived_url(): void
    {
        $site = Site::where('slug', 'ksp')->first();

        // baris lama dari dashboard: key *_url tersimpan relatif
        \App\Models\SiteSetting::create([
            'site_id' => $site->id,
            'group' => 'general',
            'key' => 'logo_url',
            'value' => 'branding/ksp-logo.webp',
        ]);

        $this->withHeader('X-Site-Key', 'ksp')
            ->getJson('/api/v1/settings')
            ->assertStatus(200)
            ->assertJsonMissing(['logo_url' => 'branding/ksp-logo.webp']);
    }

    public function test_duplicate_slug_returns_422_instead_of_500(): void
    {
        $token = $this->superadminToken();
        $headers = ['Authorization' => 'Bearer ' . $token, 'X-Site-Key' => 'ksp'];

        $this->withHeaders($headers)
            ->postJson('/api/v1/admin/pages', ['title' => 'Duplikat', 'slug' => 'duplikat'])
            ->assertStatus(201);

        $this->withHeaders($headers)
            ->postJson('/api/v1/admin/pages', ['title' => 'Duplikat', 'slug' => 'duplikat'])
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_section_can_be_disabled_from_public_page(): void
    {
        $token = $this->superadminToken();
        $headers = ['Authorization' => 'Bearer ' . $token, 'X-Site-Key' => 'ksp'];

        $pageId = $this->withHeaders($headers)
            ->postJson('/api/v1/admin/pages', [
                'slug' => 'tentang-kami',
                'title' => 'Tentang Kami',
                'is_active' => true,
            ])
            ->assertStatus(201)
            ->json('data.id');

        $sectionId = $this->withHeaders($headers)
            ->postJson("/api/v1/admin/pages/{$pageId}/sections", [
                'key' => 'hero',
                'title' => 'Hero',
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.is_active', true)
            ->json('data.id');

        $this->withHeaders($headers)
            ->putJson("/api/v1/admin/sections/{$sectionId}", ['is_active' => false])
            ->assertStatus(200)
            ->assertJsonPath('data.is_active', false);

        $this->withHeader('X-Site-Key', 'ksp')
            ->getJson('/api/v1/pages/tentang-kami')
            ->assertStatus(200)
            ->assertJsonCount(0, 'data.sections');

        // daftar halaman untuk nav
        $this->withHeader('X-Site-Key', 'ksp')
            ->getJson('/api/v1/pages')
            ->assertStatus(200)
            ->assertJsonFragment(['slug' => 'tentang-kami']);

        // admin tetap melihat section yang nonaktif
        $this->withHeaders($headers)
            ->getJson("/api/v1/admin/pages/{$pageId}")
            ->assertStatus(200)
            ->assertJsonCount(1, 'data.sections')
            ->assertJsonPath('data.sections.0.is_active', false);
    }

    public function test_service_price_fields_and_optional_body(): void
    {
        $token = $this->postJson('/api/v1/auth/login', [
            'email' => 'admin@mitrasova.com',
            'password' => 'password123',
        ])->json('data.token');

        $headers = [
            'Authorization' => 'Bearer ' . $token,
            'X-Site-Key' => 'ksp',
        ];

        $typeId = $this->withHeaders($headers)
            ->postJson('/api/v1/admin/service-types', ['name' => 'Konveksi', 'slug' => 'konveksi'])
            ->assertStatus(201)
            ->json('data.id');

        // body tidak dikirim sama sekali, harga diisi
        $res = $this->withHeaders($headers)->postJson('/api/v1/admin/services', [
            'service_type_id' => $typeId,
            'name' => 'Kaos Komunitas',
            'short_desc' => 'Kaos komunitas dan tim',
            'price' => 75000,
            'price_note' => '/pcs',
        ]);

        $res->assertStatus(201)
            ->assertJsonPath('data.price', 75000)
            ->assertJsonPath('data.price_note', '/pcs')
            ->assertJsonPath('data.body', null);

        $id = $res->json('data.id');

        // string kosong = kosongkan harga (nullable)
        $this->withHeaders($headers)
            ->putJson("/api/v1/admin/services/{$id}", ['price' => '', 'price_note' => ''])
            ->assertStatus(200)
            ->assertJsonPath('data.price', null)
            ->assertJsonPath('data.price_note', null);

        // harga negatif ditolak
        $this->withHeaders($headers)
            ->putJson("/api/v1/admin/services/{$id}", ['price' => -1])
            ->assertStatus(422);
    }
}
