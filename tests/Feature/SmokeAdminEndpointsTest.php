<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SmokeAdminEndpointsTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_admin_post_does_not_500_on_echoed_payload(): void
    {
        $this->seed();

        $token = $this->postJson('/api/v1/auth/login', [
            'email' => 'admin@mitrasova.com',
            'password' => 'password123',
        ])->json('data.token');

        $h = ['Authorization' => 'Bearer ' . $token, 'X-Site-Key' => 'ksp'];

        $cases = [
            ['/api/v1/admin/service-types', ['name' => 'Smoke Type', 'slug' => 'smoke-type', 'description' => null]],
            ['/api/v1/admin/services', ['name' => 'Smoke Service', 'short_desc' => 'x', 'body' => 'x', 'service_type_id' => 1, 'features' => ['a'], 'icon_url' => null, 'thumbnail_url' => null]],
            ['/api/v1/admin/portfolios', ['title' => 'Smoke Portfolio', 'description' => 'x', 'payload' => ['a' => 1], 'thumbnail_url' => null, 'gallery_urls' => []]],
            ['/api/v1/admin/article-categories', ['name' => 'Smoke Cat', 'slug' => 'smoke-cat', 'description' => null]],
            ['/api/v1/admin/articles', ['title' => 'Smoke Article', 'slug' => 'smoke-article', 'body' => 'x', 'thumbnail_url' => null]],
            ['/api/v1/admin/faq-categories', ['name' => 'Smoke Faq Cat', 'slug' => 'smoke-faq-cat']],
            ['/api/v1/admin/faqs', ['question' => 'Q?', 'answer' => 'A']],
            ['/api/v1/admin/testimonials', ['name' => 'Smoke T', 'content' => 'x', 'avatar_url' => null]],
            ['/api/v1/admin/partners', ['name' => 'Smoke P', 'logo_url' => null]],
            ['/api/v1/admin/branches', ['name' => 'Smoke Branch', 'address' => 'x', 'image_url' => null, 'image_urls' => []]],
            ['/api/v1/admin/business-units', ['name' => 'Smoke Unit', 'address' => 'x', 'image_url' => null, 'image_urls' => []]],
            ['/api/v1/admin/pages', ['title' => 'Smoke Page', 'slug' => 'smoke-page', 'sections' => []]],
        ];

        $failures = [];

        foreach ($cases as [$uri, $payload]) {
            $res = $this->withHeaders($h)->postJson($uri, $payload);
            if ($res->status() >= 500) {
                $failures[] = $uri . ' -> ' . $res->status() . ' ' . $res->json('message');
            }
        }

        $this->assertSame([], $failures, implode("\n", $failures));
    }
}
