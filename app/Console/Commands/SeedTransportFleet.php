<?php

namespace App\Console\Commands;

use App\Models\Faq;
use App\Models\FaqCategory;
use App\Models\Page;
use App\Models\PageSection;
use App\Models\Partner;
use App\Models\Portfolio;
use App\Models\Service;
use App\Models\ServiceType;
use App\Models\Site;
use App\Models\SiteSetting;
use App\Models\Testimonial;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Mengisi konten armada nyata untuk tenant "transportasi" (Mitra Sejati Trans).
 * Default hanya upsert (idempotent, tidak menghapus). Flag --replace menghapus konten
 * template yang tidak ada di daftar baru: layanan, tipe layanan kosong, FAQ, partner,
 * portofolio, dan testimoni. Cabang, artikel, dan halaman tetap.
 */
class SeedTransportFleet extends Command
{
    protected $signature = 'cms:seed-transport-fleet {--replace : Hapus konten template yang tidak ada di daftar baru}';

    protected $description = 'Isi konten armada nyata untuk tenant transportasi (idempotent)';

    private int $writes = 0;

    public function handle(): int
    {
        $site = Site::where('slug', 'transportasi')->first();
        if (! $site) {
            $this->error('Site "transportasi" tidak ditemukan.');

            return self::FAILURE;
        }

        $this->info("Site: {$site->name} ({$site->slug})");

        if ($site->name !== 'Mitra Sejati Trans') {
            $this->save(fn () => $site->update(['name' => 'Mitra Sejati Trans']));
        }

        $this->settings($site);
        $this->services($site);
        $this->faqs($site);
        $this->partners($site);
        $this->pages($site);

        if ($this->option('replace')) {
            $this->replace($site);
        }

        $this->newLine();
        $this->info("Selesai. {$this->writes} baris dibuat/diperbarui" . ($this->option('replace') ? ', konten template lama dibersihkan.' : ' (tidak ada yang dihapus).'));

        return self::SUCCESS;
    }

    private function save(callable $callback): void
    {
        $callback();
        $this->writes++;
    }

    private function setting(Site $site, string $group, string $key, mixed $value): void
    {
        $this->save(fn () => SiteSetting::updateOrCreate(
            ['site_id' => $site->id, 'key' => $key],
            ['group' => $group, 'value' => $value]
        ));
    }

    // ------------------------------------------------------------------ settings

    private function settings(Site $site): void
    {
        $this->setting($site, 'general', 'site_title', 'Mitra Sejati Trans');
        $this->setting($site, 'general', 'tagline', 'Sewa Bus Pariwisata & Travel');
        $this->setting($site, 'services', 'layout', 'carousel');
        $this->setting($site, 'modules', 'is_active', [
            'services' => true,
            'portfolios' => false,
            'business_units' => false,
            'careers' => true,
            'news' => true,
            'contact' => true,
        ]);

        $this->line('  settings: 4');
    }

    // ------------------------------------------------------------------ data

    /** @return array<string, array{name: string, icon: string}> */
    private function serviceTypes(): array
    {
        return [
            'hiace-elf' => ['name' => 'Hiace & Elf', 'icon' => 'car'],
            'medium-bus' => ['name' => 'Medium Bus', 'icon' => 'bus'],
            'big-bus' => ['name' => 'Big Bus', 'icon' => 'bus'],
        ];
    }

    /**
     * @return array<string, array{name: string, type: string, short_desc: string, features: array<int, string>}>
     */
    private function serviceData(): array
    {
        $hiace = ['Full AC', 'Karaoke', 'Free WiFi', 'TV LED', 'Free bantal & selimut (long weekend)', 'Driver profesional', 'Bersih & nyaman'];
        $elf = ['Full AC', 'Karaoke', 'Free WiFi', 'TV LED', 'Driver profesional', 'Bersih & nyaman'];
        $medium = ['JetBus 3', 'Kapasitas 33+1 seat', 'USB charger', 'AC', 'TV LED'];
        $jetbus3 = ['JetBus 3', 'USB charger', 'Dispenser (by request)', 'Cooling box', '2 TV LED', 'Leg rest (by request, biaya tambahan)'];
        $jetbus5 = ['JetBus 5', 'USB charger', 'Dispenser', 'Cooling box', '2 TV LED', 'Leg rest (by request, biaya tambahan)'];

        $services = [
            'hiace-commuter' => ['Hiace Commuter', 'hiace-elf', 'Kapasitas 14 seat · Cocok untuk keluarga dan rombongan kecil', $hiace],
            'elf-coaster-jumbo' => ['Elf Coaster Jumbo', 'hiace-elf', 'Kapasitas 18 seat · Rombongan sedang', $elf],
            'medium-bus-kresna' => ['Kresna', 'medium-bus', 'Kapasitas 33+1 seat · JetBus 3', $medium],
            'medium-bus-arjuna' => ['Arjuna', 'medium-bus', 'Kapasitas 33+1 seat · JetBus 3', $medium],
            'big-bus-sadewa' => ['Sadewa', 'big-bus', 'Kapasitas 50–52+1 seat · JetBus 3', $jetbus3],
            'big-bus-werkudara' => ['Werkudara', 'big-bus', 'Kapasitas 50–52+1 seat · JetBus 3', $jetbus3],
            'big-bus-puntadewa' => ['Puntadewa', 'big-bus', 'Kapasitas 50–52+1 seat · JetBus 3', $jetbus3],
            'big-bus-gatot-kaca' => ['Gatot Kaca', 'big-bus', 'Kapasitas 50–52+1 seat · JetBus 3', $jetbus3],
            'big-bus-abimanyu' => ['Abimanyu', 'big-bus', 'Kapasitas 50–52+1 seat · JetBus 3', $jetbus3],
            'big-bus-antasena' => ['Antasena', 'big-bus', 'Kapasitas 50–52+1 seat · JetBus 3', $jetbus3],
            'big-bus-wisanggeni' => ['Wisanggeni', 'big-bus', 'Kapasitas 50–52+1 seat · JetBus 5', $jetbus5],
            'big-bus-parikesit' => ['Parikesit', 'big-bus', 'Kapasitas 50–52+1 seat · JetBus 5', $jetbus5],
            'big-bus-antareja' => ['Antareja', 'big-bus', 'Kapasitas 50–52+1 seat · JetBus 5', $jetbus5],
            'big-bus-basudewa' => ['Basudewa', 'big-bus', 'Kapasitas 50–52+1 seat · JetBus 5', $jetbus5],
            'big-bus-baladewa' => ['Baladewa', 'big-bus', 'Kapasitas 50–52+1 seat · JetBus 5', $jetbus5],
            'big-bus-nakula' => ['Nakula', 'big-bus', 'Kapasitas 50–52+1 seat · JetBus 5', $jetbus5],
        ];

        $featured = ['hiace-commuter', 'medium-bus-kresna', 'big-bus-sadewa'];

        $data = [];
        foreach ($services as $slug => [$name, $type, $shortDesc, $features]) {
            $data[$slug] = [
                'name' => $name,
                'type' => $type,
                'short_desc' => $shortDesc,
                'features' => $features,
                'is_featured' => in_array($slug, $featured, true),
            ];
        }

        return $data;
    }

    /** @return array<string, string> slug => name */
    private function faqCategories(): array
    {
        return [
            'fasilitas' => 'Fasilitas',
            'pemesanan' => 'Pemesanan',
            'pembayaran' => 'Pembayaran',
            'operasional' => 'Operasional',
        ];
    }

    /** @return array<string, array<int, array{0: string, 1: string}>> */
    private function faqData(): array
    {
        return [
            'fasilitas' => [
                ['Fasilitas apa saja yang didapat?', 'Setiap unit memiliki fasilitas yang berbeda. Rincian fasilitas—seperti AC, TV LED, USB charger, dispenser, cooling box, dan leg rest—dapat dilihat pada halaman armada yang Anda pilih.'],
            ],
            'pemesanan' => [
                ['Bagaimana cara cek ketersediaan armada?', 'Silakan hubungi admin melalui WhatsApp. Ketersediaan armada akan dikonfirmasi pada saat pemesanan agar jadwal yang Anda pilih dapat kami amankan.'],
                ['Bisa pesan mendadak?', 'Pemesanan mendadak dapat dilayani paling lambat H-7 sebelum keberangkatan. Untuk pemesanan kurang dari H-7, dikenakan biaya tambahan sebesar 50% dari harga sewa.'],
            ],
            'pembayaran' => [
                ['Apakah DP wajib?', 'Ya. DP diperlukan untuk mengunci tanggal dan armada yang Anda pilih.'],
                ['Berapa minimal DP?', 'DP minimal Rp500.000 per pemesanan.'],
                ['Kalau sudah DP lalu dibatalkan?', 'DP yang telah dibayarkan tidak dapat dikembalikan (hangus) apabila pembatalan dilakukan oleh penyewa.'],
                ['Apakah harga sudah termasuk dokumentasi?', 'Belum. Harga sewa hanya mencakup kendaraan dan layanan pengemudi; dokumentasi dapat diatur terpisah.'],
            ],
            'operasional' => [
                ['Apakah wajib mampir ke tempat oleh-oleh?', 'Tidak wajib. Kunjungan ke tempat oleh-oleh dapat disesuaikan dengan rencana perjalanan Anda.'],
                ['Sehari bisa ke berapa tujuan?', 'Dalam satu hari, perjalanan dapat mencakup 2–3 tujuan. Penambahan tujuan dapat didiskusikan selama tidak melewati batas operasional pukul 23.00.'],
                ['Sampai jam berapa operasional?', 'Layanan operasional maksimal sampai pukul 23.00.'],
                ['Tujuan bisa ke mana saja?', 'Tujuan dapat disesuaikan dengan kebutuhan Anda. Biaya sewa akan menyesuaikan rute dan jarak tempuh, dan dapat didiskusikan dengan admin.'],
            ],
        ];
    }

    /** @return array<int, string> */
    private function partnerNames(): array
    {
        return [
            'Asero Tour', 'HSA Tour & Travel', 'JK Tour', 'LDGR Tour', 'Star Wisata',
            'Alysia Tour', 'El Wisata', 'Sukses Tour', 'Zahra Tour', 'Big Java Travel Indo',
            'Birruya Tour', 'Salam Wisata', 'Pesona Wisata', 'AR Tour', 'Dika Tour',
            'Maxsam Tour', 'Nugroho Tour', 'Anugrah Outbound', 'Eka J', 'Tiara Permata Tour & Travel',
            'Mega Tiara', 'Juki Tour', 'Lawu Travelindo', 'Mandala Tour', 'Bimasena Tour',
            'Mbah Surip Tour', 'Antaran Tour', 'Pradana Arizky Tour', 'Punokawan Tour', 'Audi Holiday',
            'Marcopolo', 'Almas Wisata', 'Perkasa Holiday', 'Hands Tour', 'Valeeqa Holiday',
            'Yana Tour', 'Natura Tour', 'Mahendra Tour', 'Zoya Tour', 'Seven Tour',
            'HR Tour', 'Wafik Tour', 'Fantasia Tour',
        ];
    }

    // ------------------------------------------------------------------ services

    private function services(Site $site): void
    {
        $types = [];
        $order = 0;

        foreach ($this->serviceTypes() as $slug => $typeData) {
            $type = ServiceType::firstOrNew(['site_id' => $site->id, 'slug' => $slug]);
            $type->fill([
                'name' => $typeData['name'],
                'description' => null,
                'icon' => $typeData['icon'],
                'order' => $order++,
            ]);
            $this->save(fn () => $type->save());
            $types[$slug] = $type;
        }

        $order = 0;
        foreach ($this->serviceData() as $slug => $item) {
            $service = Service::firstOrNew(['site_id' => $site->id, 'slug' => $slug]);
            $service->fill([
                'service_type_id' => $types[$item['type']]->id,
                'name' => $item['name'],
                'short_desc' => $item['short_desc'],
                'body' => $item['short_desc'],
                'features' => $item['features'],
                'thumbnail_path' => $this->placeholder("armada/{$slug}.png", 'GANTI FOTO ' . $item['name'], 800, 600),
                'images' => null,
                'price' => null,
                'price_note' => 'Hubungi admin',
                'is_featured' => $item['is_featured'],
                'is_active' => true,
                'order' => $order++,
            ]);
            $this->save(fn () => $service->save());
        }

        $this->line('  service types: ' . count($types) . ', services: ' . count($this->serviceData()));
    }

    // ------------------------------------------------------------------ faqs

    private function faqs(Site $site): void
    {
        $categories = $this->faqCategories();
        $data = $this->faqData();
        $catOrder = 0;
        $faqCount = 0;

        foreach ($data as $catSlug => $list) {
            $cat = FaqCategory::firstOrNew(['site_id' => $site->id, 'slug' => $catSlug]);
            $cat->fill(['name' => $categories[$catSlug], 'order' => $catOrder++]);
            $this->save(fn () => $cat->save());

            foreach ($list as $order => [$question, $answer]) {
                $faq = Faq::firstOrNew(['site_id' => $site->id, 'question' => $question]);
                $faq->fill([
                    'faq_category_id' => $cat->id,
                    'answer' => $answer,
                    'order' => $order,
                    'is_active' => true,
                ]);
                $this->save(fn () => $faq->save());
                $faqCount++;
            }
        }

        $this->line('  faq categories: ' . count($categories) . ", faqs: {$faqCount}");
    }

    // ------------------------------------------------------------------ partners

    private function partners(Site $site): void
    {
        foreach ($this->partnerNames() as $order => $name) {
            $slug = Str::slug($name);
            $partner = Partner::firstOrNew(['site_id' => $site->id, 'name' => $name]);
            $partner->fill([
                'logo_path' => $this->placeholder("partner/{$slug}.png", $this->initials($name), 320, 320),
                'website_url' => null,
                'phone' => null,
                'order' => $order,
            ]);
            $this->save(fn () => $partner->save());
        }

        $this->line('  partners: ' . count($this->partnerNames()));
    }

    // ------------------------------------------------------------------ pages

    private function pages(Site $site): void
    {
        $home = $this->page($site, 'home', 'Beranda');
        $hero = $this->section($home, 'hero');
        $this->payload($hero, [
            'slides' => [
                ['title' => 'Sewa Bus Pariwisata untuk Rombongan', 'description' => 'Hiace, Elf, medium bus, hingga big bus 50–52 seat untuk keluarga, kantor, sekolah, dan travel agent.', 'badge' => 'Mitra Sejati Trans', 'image_path' => 'template/transportasi/hero-1.png'],
                ['title' => 'Armada Lengkap, Fasilitas Nyaman', 'description' => 'AC, TV LED, USB charger, dispenser, cooling box, dan leg rest sesuai unit.', 'badge' => '16 Armada', 'image_path' => 'template/transportasi/hero-2.png'],
                ['title' => 'Driver Profesional, Perjalanan Tenang', 'description' => 'Armada dirawat berkala dan operasional dikelola lewat aplikasi manajemen bus.', 'badge' => 'Sejak 2017', 'image_path' => 'template/transportasi/hero-3.png'],
            ],
            'stats' => [
                ['label' => 'Armada', 'value' => '16'],
                ['label' => 'Mitra Travel', 'value' => '43'],
                ['label' => 'Tahun Beroperasi', 'value' => '9'],
                ['label' => 'Seat per Bus', 'value' => '52'],
            ],
        ]);

        $about = $this->section($home, 'about');
        $this->payload($about, [
            'stats' => [
                ['label' => 'Armada', 'value' => '16'],
                ['label' => 'Mitra Travel', 'value' => '43'],
                ['label' => 'Tahun Beroperasi', 'value' => '9'],
            ],
        ]);

        $profil = $this->page($site, 'profil', 'Profil', 'Profil | Mitra Sejati Trans');

        $profilHero = $this->section($profil, 'hero');
        $profilHero->body = 'Unit usaha KSP Mitra Sejati Persada yang melayani sewa bus pariwisata dan travel sejak 2017.';
        $this->save(fn () => $profilHero->save());

        $sejarah = $this->section($profil, 'sejarah');
        $sejarah->body = implode("\n\n", [
            'Mitra Sejati Trans adalah unit usaha KSP Mitra Sejati Persada yang melayani sewa bus pariwisata dan travel sejak 16 Juli 2017. Berawal dari satu unit kendaraan, kini kami mengoperasikan 16 armada—mulai Hiace Commuter, Elf Coaster, medium bus, hingga big bus berkapasitas 50–52 seat—untuk kebutuhan keluarga, kantor, sekolah, komunitas, dan travel agent.',
            'Setiap perjalanan ditangani driver profesional dengan armada yang dirawat berkala. Operasional kami dikelola melalui aplikasi manajemen bus: jadwal armada, kru, dan perawatan tercatat rapi. Fasilitas seperti AC, TV LED, USB charger, dispenser, cooling box, dan leg rest tersedia sesuai unit, sehingga rombongan bisa memilih armada yang paling sesuai.',
        ]);
        $this->save(fn () => $sejarah->save());

        $visiMisi = $this->section($profil, 'visi-misi');
        $this->payload($visiMisi, [
            'visi' => 'Menjadi penyedia jasa sewa bus pariwisata dan travel yang paling dipercaya di Jawa Tengah dan sekitarnya, dengan perjalanan yang aman, nyaman, dan tepat waktu.',
            'misi' => [
                ['title' => 'Keselamatan Utama', 'desc' => 'Pemeriksaan armada berkala dan driver profesional untuk setiap perjalanan.'],
                ['title' => 'Kenyamanan Rombongan', 'desc' => 'Armada bersih dengan AC, TV LED, USB charger, dan fasilitas sesuai unit.'],
                ['title' => 'Harga Transparan', 'desc' => 'Harga sewa, DP, dan ketentuan pembatalan disepakati di awal.'],
                ['title' => 'Tepat Waktu & Fleksibel', 'desc' => 'Jadwal dan rute disusun sesuai kebutuhan rombongan.'],
                ['title' => 'Tumbuh Bersama Mitra', 'desc' => 'Pelatihan crew dan kemitraan berkelanjutan dengan travel agent.'],
            ],
        ]);

        $values = $this->section($profil, 'values');
        $this->payload($values, [
            'badge' => 'Standar Layanan',
            'items' => [
                ['title' => 'Keselamatan', 'description' => 'Armada diperiksa sebelum berangkat dan driver diatur jam istirahatnya.'],
                ['title' => 'Kebersihan', 'description' => 'Kabinet dan interior dibersihkan sebelum setiap perjalanan.'],
                ['title' => 'Ketepatan Waktu', 'description' => 'Penjemputan sesuai jadwal yang disepakati.'],
            ],
        ]);

        $karir = $this->page($site, 'karir', 'Karir', 'Karir | Mitra Sejati Trans');
        $karirValues = $this->section($karir, 'values');
        $this->payload($karirValues, ['openings' => []]);

        $produk = $this->page($site, 'produk', 'Layanan', 'Layanan | Mitra Sejati Trans');
        $produkHero = $this->section($produk, 'hero');
        $this->payload($produkHero, [
            'slides' => [
                ['title' => 'Pilih Armada Sesuai Rombongan', 'description' => 'Dari Hiace 14 seat sampai big bus 52 seat.', 'badge' => 'Armada', 'image_path' => 'template/transportasi/produk-1.png'],
                ['title' => 'Fasilitas Bisa Disesuaikan', 'description' => 'Dispenser, cooling box, dan leg rest tersedia sesuai unit.', 'badge' => 'Fasilitas', 'image_path' => 'template/transportasi/produk-2.png'],
            ],
        ]);

        $this->line('  pages: 4');
    }

    private function page(Site $site, string $slug, string $title, ?string $metaTitle = null): Page
    {
        $page = Page::firstOrNew(['site_id' => $site->id, 'slug' => $slug]);

        if (! $page->exists) {
            $page->fill([
                'title' => $title,
                'meta_title' => $metaTitle ?? "{$title} | Mitra Sejati Trans",
                'is_active' => true,
            ]);
            $this->save(fn () => $page->save());
        }

        return $page;
    }

    private function section(Page $page, string $key): PageSection
    {
        $section = $page->sections()->firstOrNew(['key' => $key]);

        if (! $section->exists) {
            $section->fill([
                'order' => ((int) $page->sections()->max('order')) + 1,
                'is_active' => true,
            ]);
        }

        return $section;
    }

    private function payload(PageSection $section, array $values): void
    {
        $payload = $section->payload ?? [];
        foreach ($values as $key => $value) {
            $payload[$key] = $value;
        }

        $section->payload = $this->withImageUrls($payload);
        $this->save(fn () => $section->save());
    }

    // ------------------------------------------------------------------ replace

    private function replace(Site $site): void
    {
        $removed = [
            'services' => Service::where('site_id', $site->id)
                ->whereNotIn('slug', array_keys($this->serviceData()))->delete(),
            'portfolios' => Portfolio::where('site_id', $site->id)->delete(),
            'testimonials' => Testimonial::where('site_id', $site->id)->delete(),
        ];

        // Layanan dihapus lebih dulu (urutan array di atas) agar FK restrictOnDelete tipe tidak menghalangi.
        $removed['service_types'] = ServiceType::where('site_id', $site->id)
            ->whereNotIn('slug', array_keys($this->serviceTypes()))
            ->whereDoesntHave('services')
            ->delete();

        $removed['faqs'] = Faq::where('site_id', $site->id)
            ->whereNotIn('question', collect($this->faqData())->flatMap(fn ($list) => array_column($list, 0))->all())
            ->delete();

        $removed['faq_categories'] = FaqCategory::where('site_id', $site->id)
            ->whereNotIn('slug', array_keys($this->faqCategories()))
            ->delete();

        $removed['partners'] = Partner::where('site_id', $site->id)
            ->whereNotIn('name', $this->partnerNames())
            ->delete();

        $this->line('  replace: ' . collect($removed)->map(fn ($n, $k) => "{$k}={$n}")->implode(', '));
    }

    // ------------------------------------------------------------------ helpers

    private function withImageUrls(array $payload): array
    {
        foreach ($payload as $key => $value) {
            if (is_array($value)) {
                $payload[$key] = $this->withImageUrls($value);
            } elseif ($key === 'image_path' && is_string($value) && $value !== '') {
                $payload['image_url'] = str_starts_with($value, 'http')
                    ? $value
                    : url('storage/' . ltrim($value, '/'));
            }
        }

        return $payload;
    }

    private function initials(string $name): string
    {
        $words = preg_split('/[^A-Za-z0-9]+/', $name, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $initials = '';
        foreach ($words as $word) {
            $initials .= strtoupper($word[0]);
            if (strlen($initials) === 2) {
                break;
            }
        }

        return $initials !== '' ? $initials : strtoupper(substr($name, 0, 2));
    }

    /** Placeholder PNG gelap berlabel supaya preview tidak kosong; dilewati bila file sudah ada. */
    private function placeholder(string $name, string $label, int $width, int $height): string
    {
        $path = "template/transportasi/{$name}";
        $disk = Storage::disk('public');

        if ($disk->exists($path)) {
            return $path;
        }

        $absolute = $disk->path($path);
        if (! is_dir(dirname($absolute))) {
            mkdir(dirname($absolute), 0775, true);
        }

        $image = imagecreatetruecolor($width, $height);
        $bg = imagecolorallocate($image, 15, 23, 42);
        $border = imagecolorallocate($image, 51, 65, 85);
        $text = imagecolorallocate($image, 255, 255, 255);
        $muted = imagecolorallocate($image, 148, 163, 184);

        imagefilledrectangle($image, 0, 0, $width, $height, $bg);
        imagerectangle($image, 0, 0, $width - 1, $height - 1, $border);

        $title = strtoupper(Str::limit($label, 34, ''));
        imagestring($image, 5, max(8, (int) (($width - imagefontwidth(5) * strlen($title)) / 2)), (int) ($height / 2) - 20, $title, $text);

        $note = 'GANTI DENGAN FOTO ASLI';
        imagestring($image, 3, max(8, (int) (($width - imagefontwidth(3) * strlen($note)) / 2)), (int) ($height / 2) + 10, $note, $muted);

        imagepng($image, $absolute);
        imagedestroy($image);

        return $path;
    }
}
