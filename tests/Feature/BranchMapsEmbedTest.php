<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BranchMapsEmbedTest extends TestCase
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

    public function test_branch_maps_embed_is_saved_and_exposed_publicly(): void
    {
        $embed = 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d1045.89';

        $response = $this->withHeaders($this->headers())
            ->postJson('/api/v1/admin/branches', [
                'name' => 'Kantor Cabang Uji Maps',
                'address' => 'Jl. Uji No. 3',
                'maps_embed' => $embed,
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.maps_embed', $embed);

        $this->assertDatabaseHas('branches', [
            'id' => $response->json('data.id'),
            'maps_embed' => $embed,
        ]);

        $this->withHeader('X-Site-Key', 'ksp')
            ->getJson('/api/v1/branches')
            ->assertStatus(200)
            ->assertJsonFragment(['maps_embed' => $embed]);
    }

    public function test_branch_maps_embed_can_be_updated_cleared_and_validated(): void
    {
        $id = $this->withHeaders($this->headers())
            ->postJson('/api/v1/admin/branches', [
                'name' => 'Kantor Cabang Uji Maps 2',
                'address' => 'Jl. Uji No. 4',
                'maps_embed' => 'https://www.google.com/maps/embed?pb=awal',
            ])
            ->assertStatus(201)
            ->json('data.id');

        $this->withHeaders($this->headers())
            ->putJson("/api/v1/admin/branches/{$id}", ['maps_embed' => 'https://www.google.com/maps/embed?pb=baru'])
            ->assertStatus(200)
            ->assertJsonPath('data.maps_embed', 'https://www.google.com/maps/embed?pb=baru');

        $this->withHeaders($this->headers())
            ->putJson("/api/v1/admin/branches/{$id}", ['maps_embed' => str_repeat('a', 2001)])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['maps_embed']);

        $this->withHeaders($this->headers())
            ->putJson("/api/v1/admin/branches/{$id}", ['maps_embed' => null])
            ->assertStatus(200)
            ->assertJsonPath('data.maps_embed', null);
    }
}
