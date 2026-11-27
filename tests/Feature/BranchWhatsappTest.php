<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BranchWhatsappTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function headers(): array
    {
        $token = $this->postJson('/api/v1/auth/login', [
            'email' => 'admin@mitrasova.com',
            'password' => 'password123',
        ])->json('data.token');

        return ['Authorization' => 'Bearer ' . $token, 'X-Site-Key' => 'ksp', 'Accept' => 'application/json'];
    }

    public function test_branch_whatsapp_is_saved_and_exposed_publicly(): void
    {
        $response = $this->withHeaders($this->headers())
            ->postJson('/api/v1/admin/branches', [
                'name' => 'Kantor Cabang Uji',
                'address' => 'Jl. Uji No. 1',
                'phone' => '(0271)-111111',
                'whatsapp' => '6281234567890',
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.whatsapp', '6281234567890');

        $this->assertDatabaseHas('branches', [
            'id' => $response->json('data.id'),
            'whatsapp' => '6281234567890',
        ]);

        $this->withHeader('X-Site-Key', 'ksp')
            ->getJson('/api/v1/branches')
            ->assertStatus(200)
            ->assertJsonFragment(['whatsapp' => '6281234567890']);
    }

    public function test_branch_whatsapp_can_be_updated_and_cleared(): void
    {
        $id = $this->withHeaders($this->headers())
            ->postJson('/api/v1/admin/branches', [
                'name' => 'Kantor Cabang Uji 2',
                'address' => 'Jl. Uji No. 2',
                'whatsapp' => '6281111111111',
            ])
            ->assertStatus(201)
            ->json('data.id');

        $this->withHeaders($this->headers())
            ->putJson("/api/v1/admin/branches/{$id}", ['whatsapp' => '6282222222222'])
            ->assertStatus(200)
            ->assertJsonPath('data.whatsapp', '6282222222222');

        $this->withHeaders($this->headers())
            ->putJson("/api/v1/admin/branches/{$id}", ['whatsapp' => null])
            ->assertStatus(200)
            ->assertJsonPath('data.whatsapp', null);
    }
}
