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
}
