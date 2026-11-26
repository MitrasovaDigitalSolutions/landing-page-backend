<?php

namespace Tests\Feature;

use App\Models\Faq;
use App\Models\FaqCategory;
use App\Models\Page;
use App\Models\Partner;
use App\Models\Portfolio;
use App\Models\Service;
use App\Models\ServiceType;
use App\Models\Site;
use App\Models\Testimonial;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TransportFleetTest extends TestCase
{
    use RefreshDatabase;

    public function test_fleet_content_replaces_template_for_transport_only(): void
    {
        $this->seed();
        Storage::fake('public');

        $ksp = Site::where('slug', 'ksp')->firstOrFail();
        $kspServices = Service::where('site_id', $ksp->id)->count();
        $kspPages = Page::where('site_id', $ksp->id)->count();

        $this->artisan('cms:seed-transport-fleet', ['--replace' => true])->assertExitCode(0);

        $transport = Site::where('slug', 'transportasi')->firstOrFail();

        $this->assertSame(16, Service::where('site_id', $transport->id)->count());
        $this->assertEqualsCanonicalizing([
            'hiace-commuter', 'elf-coaster-jumbo', 'medium-bus-kresna', 'medium-bus-arjuna',
            'big-bus-sadewa', 'big-bus-werkudara', 'big-bus-puntadewa', 'big-bus-gatot-kaca',
            'big-bus-abimanyu', 'big-bus-antasena', 'big-bus-wisanggeni', 'big-bus-parikesit',
            'big-bus-antareja', 'big-bus-basudewa', 'big-bus-baladewa', 'big-bus-nakula',
        ], Service::where('site_id', $transport->id)->pluck('slug')->all());
        $this->assertSame(3, ServiceType::where('site_id', $transport->id)->count());
        $this->assertEqualsCanonicalizing(
            ['hiace-elf', 'medium-bus', 'big-bus'],
            ServiceType::where('site_id', $transport->id)->pluck('slug')->all()
        );
        $this->assertSame(11, Faq::where('site_id', $transport->id)->count());
        $this->assertSame(4, FaqCategory::where('site_id', $transport->id)->count());
        $this->assertSame(43, Partner::where('site_id', $transport->id)->count());
        $this->assertSame(0, Portfolio::where('site_id', $transport->id)->count());
        $this->assertSame(0, Testimonial::where('site_id', $transport->id)->count());

        $this->assertNull(Service::where('site_id', $transport->id)->where('slug', 'hiace-commuter')->firstOrFail()->price);
        $this->assertNull(Partner::where('site_id', $transport->id)->firstOrFail()->phone);

        $hero = Page::where('site_id', $transport->id)->where('slug', 'home')->firstOrFail()
            ->sections()->where('key', 'hero')->firstOrFail();
        $this->assertContains('16', array_column($hero->payload['stats'], 'value'));

        $visiMisi = Page::where('site_id', $transport->id)->where('slug', 'profil')->firstOrFail()
            ->sections()->where('key', 'visi-misi')->firstOrFail();
        $this->assertCount(5, $visiMisi->payload['misi']);

        // tenant lain tidak berubah
        $this->assertSame($kspServices, Service::where('site_id', $ksp->id)->count());
        $this->assertSame($kspPages, Page::where('site_id', $ksp->id)->count());

        // idempotent: dijalankan dua kali tidak menggandakan apa pun
        $this->artisan('cms:seed-transport-fleet', ['--replace' => true])->assertExitCode(0);
        $this->assertSame(16, Service::where('site_id', $transport->id)->count());
        $this->assertSame(3, ServiceType::where('site_id', $transport->id)->count());
        $this->assertSame(11, Faq::where('site_id', $transport->id)->count());
        $this->assertSame(43, Partner::where('site_id', $transport->id)->count());
        $this->assertSame(0, Portfolio::where('site_id', $transport->id)->count());
    }
}
