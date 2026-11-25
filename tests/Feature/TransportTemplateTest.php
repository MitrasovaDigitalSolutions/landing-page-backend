<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\Service;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TransportTemplateTest extends TestCase
{
    use RefreshDatabase;

    public function test_template_only_touches_transportasi_and_adds_nothing_destructive(): void
    {
        $this->seed();
        Storage::fake('public');

        $ksp = Site::where('slug', 'ksp')->firstOrFail();
        $kspServices = Service::where('site_id', $ksp->id)->count();
        $kspPages = Page::where('site_id', $ksp->id)->count();

        $this->artisan('cms:seed-transport-template')->assertExitCode(0);

        $transport = Site::where('slug', 'transportasi')->firstOrFail();

        $this->assertSame(8, Page::where('site_id', $transport->id)->count());
        $this->assertSame(9, Service::where('site_id', $transport->id)->whereNotNull('price')->count());
        $this->assertSame(
            ['hero', 'about', 'cta', 'office'],
            Page::where('site_id', $transport->id)->where('slug', 'home')->firstOrFail()
                ->sections()->pluck('key')->all()
        );

        // tenant lain tidak berubah
        $this->assertSame($kspServices, Service::where('site_id', $ksp->id)->count());
        $this->assertSame($kspPages, Page::where('site_id', $ksp->id)->count());

        // idempotent: dijalankan dua kali tidak menggandakan halaman
        $this->artisan('cms:seed-transport-template')->assertExitCode(0);
        $this->assertSame(8, Page::where('site_id', $transport->id)->count());
    }
}
