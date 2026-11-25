<?php

namespace App\Console\Commands;

use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\Branch;
use App\Models\Faq;
use App\Models\FaqCategory;
use App\Models\Page;
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
 * Mengisi konten template tenant "transportasi" (Mitra Sejati Trans) sebagai groundwork landing page.
 * Hanya menulis untuk site transportasi; tidak pernah menghapus baris apa pun.
 * Jalankan dengan --dry-run untuk melihat rencana tanpa menyimpan.
 */
class SeedTransportTemplate extends Command
{
    protected $signature = 'cms:seed-transport-template {--dry-run : Tampilkan rencana tanpa menulis ke database/storage} {--purge-demo : Hapus konten demo bawaan seeder untuk tenant transportasi sebelum mengisi template}';

    protected $description = 'Isi konten template lengkap untuk tenant transportasi (idempotent, tanpa hapus data)';

    private bool $dryRun = false;

    private int $writes = 0;

    private array $placeholders = [];

    public function handle(): int
    {
        $this->dryRun = (bool) $this->option('dry-run');

        $site = Site::where('slug', 'transportasi')->first();
        if (! $site) {
            $this->error('Site "transportasi" tidak ditemukan.');

            return self::FAILURE;
        }

        $this->info("Site: {$site->name} ({$site->slug})" . ($this->dryRun ? ' [DRY RUN]' : ''));

        $this->purgeDemo($site);
        $this->settings($site);
        $types = $this->services($site);
        $this->portfolios($site, $types);
        $this->branches($site);
        $this->articles($site);
        $this->faqs($site);
        $this->testimonials($site);
        $this->partners($site);
        $this->pages($site);

        $this->newLine();
        $this->info($this->dryRun
            ? "Dry run selesai. {$this->writes} baris akan dibuat/diperbarui, tidak ada perubahan disimpan."
            : "Selesai. {$this->writes} baris dibuat/diperbarui" . ($this->option('purge-demo') ? ' (konten demo transportasi dibersihkan lebih dulu).' : ' (tidak ada yang dihapus).'));

        return self::SUCCESS;
    }

    private function save(callable $callback): void
    {
        if (! $this->dryRun) {
            $callback();
        }
        $this->writes++;
    }

    private function setting(Site $site, string $group, string $key, mixed $value): void
    {
        $this->save(fn () => SiteSetting::updateOrCreate(
            ['site_id' => $site->id, 'key' => $key],
            ['group' => $group, 'value' => $value]
        ));
    }

    // ------------------------------------------------------------------ purge demo

    /**
     * Hapus konten demo bawaan seeder (whitelist slug/nama) khusus tenant transportasi.
     * Hanya berjalan bila flag --purge-demo dikirim.
     */
    private function purgeDemo(Site $site): void
    {
        if (! $this->option('purge-demo')) {
            return;
        }

        $demoServiceSlugs = ['sewa-truk-kargo', 'sewa-mobil-operasional'];
        $demoTypeSlugs = ['logistik-kargo', 'rental-korporat'];
        $demoPortfolioSlugs = ['proyek-unggulan-transportasi'];
        $demoArticleSlugs = ['tips-memilih-layanan-terbaik-untuk-usaha'];
        $demoArticleCategories = ['informasi-terkini'];
        $demoFaqCategories = ['operasional'];
        $demoTestimonials = ['Bambang Sudarsono', 'Siti Nurhaliza'];
        $demoPartners = ['Mitra Perbankan Nasional', 'Asosiasi Bisnis Indonesia'];

        $plan = [
            'services' => Service::where('site_id', $site->id)->whereIn('slug', $demoServiceSlugs)->count(),
            'service_types' => ServiceType::where('site_id', $site->id)->whereIn('slug', $demoTypeSlugs)->count(),
            'portfolios' => Portfolio::where('site_id', $site->id)->whereIn('slug', $demoPortfolioSlugs)->count(),
            'articles' => Article::where('site_id', $site->id)->whereIn('slug', $demoArticleSlugs)->count(),
            'article_categories' => ArticleCategory::where('site_id', $site->id)->whereIn('slug', $demoArticleCategories)->count(),
            'faqs' => Faq::where('site_id', $site->id)->whereHas('category', fn ($q) => $q->whereIn('slug', $demoFaqCategories))->count(),
            'faq_categories' => FaqCategory::where('site_id', $site->id)->whereIn('slug', $demoFaqCategories)->count(),
            'testimonials' => Testimonial::where('site_id', $site->id)->whereIn('name', $demoTestimonials)->count(),
            'partners' => Partner::where('site_id', $site->id)->whereIn('name', $demoPartners)->count(),
        ];

        $summary = collect($plan)->filter()->map(fn ($n, $k) => "{$k}={$n}")->implode(', ');

        if ($this->dryRun) {
            $this->line('  purge-demo (dry): ' . ($summary ?: 'tidak ada baris demo'));

            return;
        }

        Service::where('site_id', $site->id)->whereIn('slug', $demoServiceSlugs)->delete();
        ServiceType::where('site_id', $site->id)->whereIn('slug', $demoTypeSlugs)
            ->whereDoesntHave('services')->delete();
        Portfolio::where('site_id', $site->id)->whereIn('slug', $demoPortfolioSlugs)->delete();
        Article::where('site_id', $site->id)->whereIn('slug', $demoArticleSlugs)->delete();
        ArticleCategory::where('site_id', $site->id)->whereIn('slug', $demoArticleCategories)->delete();

        $faqCategoryIds = FaqCategory::where('site_id', $site->id)->whereIn('slug', $demoFaqCategories)->pluck('id');
        Faq::where('site_id', $site->id)->whereIn('faq_category_id', $faqCategoryIds)->delete();
        FaqCategory::where('site_id', $site->id)->whereIn('slug', $demoFaqCategories)->delete();

        Testimonial::where('site_id', $site->id)->whereIn('name', $demoTestimonials)->delete();
        Partner::where('site_id', $site->id)->whereIn('name', $demoPartners)->delete();

        $this->line('  purge-demo: ' . ($summary ?: 'tidak ada baris demo'));
    }

    // ------------------------------------------------------------------ settings

    private function settings(Site $site): void
    {
        $maps = 'https://maps.google.com/maps?q=' . rawurlencode('Kedawung, Sragen, Jawa Tengah') . '&t=&z=13&ie=UTF8&iwloc=&output=embed';

        $this->setting($site, 'general', 'site_title', 'Mitra Sejati Trans');
        $this->setting($site, 'general', 'tagline', 'Sewa Kendaraan & Ekspedisi untuk Operasional dan Distribusi Anda');
        $this->setting($site, 'general', 'meta_keywords', 'sewa kendaraan sragen, ekspedisi kargo, rental hiace, logistik proyek, tronton, wingbox');
        $this->setting($site, 'contact', 'phone', '081329703060');
        $this->setting($site, 'contact', 'whatsapp', '6281329703060');
        $this->setting($site, 'contact', 'email', 'trans@mitrasejatipersada.co.id');
        $this->setting($site, 'contact', 'address', 'Jl. Batujamus - Jambangan Km. 2, Gempol, Mojodoyong, Kedawung, Sragen');
        $this->setting($site, 'contact', 'maps_embed', $maps);
        $this->setting($site, 'legal', 'copyright', '© 2026 Mitra Sejati Trans. Seluruh hak cipta dilindungi.');
        $this->setting($site, 'services', 'layout', 'carousel');
        $this->setting($site, 'modules', 'is_active', [
            'services' => true,
            'portfolios' => true,
            'business_units' => false,
            'careers' => true,
            'news' => true,
            'contact' => true,
        ]);

        $this->line('  settings: 10');
    }

    // ------------------------------------------------------------------ services

    /**
     * @return array<string, ServiceType>
     */
    private function services(Site $site): array
    {
        $data = [
            'rental-kendaraan' => [
                'name' => 'Rental Kendaraan',
                'icon' => 'car',
                'description' => 'Sewa mobil, hiace, elf, dan bus dengan atau tanpa driver untuk kebutuhan operasional maupun perjalanan.',
                'items' => [
                    [
                        'name' => 'Sewa Avanza / Innova',
                        'short_desc' => 'Kendaraan keluarga dan operasional harian, lepas kunci atau dengan driver.',
                        'price' => 550000, 'price_note' => '/hari', 'is_featured' => true,
                        'features' => ['Unit tahun 2020 ke atas', 'Lepas kunci atau dengan driver', 'BBM dan tol terpisah', 'Antar-jemput unit area Sragen–Solo'],
                        'body' => 'Pilihan paling praktis untuk perjalanan dinas, kunjungan klien, maupun kebutuhan keluarga. Semua unit melewati pemeriksaan 21 titik sebelum diserahkan ke penyewa.',
                    ],
                    [
                        'name' => 'Sewa Hiace Premio',
                        'short_desc' => 'Rombongan 14 orang dengan kabin lega dan pendingin udara merata.',
                        'price' => 1300000, 'price_note' => '/hari', 'is_featured' => true,
                        'features' => ['Kapasitas 14 kursi', 'Driver berpengalaman rute Jawa', 'Full AC dan audio', 'Bagasi belakang luas'],
                        'body' => 'Cocok untuk kunjungan kerja, wisata keluarga, dan penjemputan rombongan. Tersedia paket harian, mingguan, dan luar kota.',
                    ],
                    [
                        'name' => 'Sewa Elf Long',
                        'short_desc' => 'Elf long 19 kursi untuk rombongan menengah dengan bagasi memadai.',
                        'price' => 1500000, 'price_note' => '/hari', 'is_featured' => false,
                        'features' => ['Kapasitas 19 kursi', 'Bagasi luas', 'Driver dan bahan bakar termasuk', 'Cocok untuk travel dan ziarah'],
                        'body' => 'Armada favorit untuk perjalanan antar kota dengan rombongan menengah. Kabin tinggi sehingga penumpang lebih leluasa.',
                    ],
                    [
                        'name' => 'Sewa Bus Pariwisata',
                        'short_desc' => 'Bus 45 kursi untuk wisata, kunjungan industri, dan acara perusahaan.',
                        'price' => 3500000, 'price_note' => '/hari', 'is_featured' => false,
                        'features' => ['Kapasitas 45 kursi', 'Audio visual dan karaoke', 'Driver dan kernet', 'Asuransi penumpang'],
                        'body' => 'Armada bus dilengkapi fasilitas hiburan dan pendingin udara. Termasuk koordinasi izin perjalanan dan penginapan driver untuk rute luar kota.',
                    ],
                ],
            ],
            'ekspedisi-kargo' => [
                'name' => 'Ekspedisi & Kargo',
                'icon' => 'truck',
                'description' => 'Pengiriman barang antar kota dan antar pulau dengan armada box, wingbox, dan tronton.',
                'items' => [
                    [
                        'name' => 'CDD Box 4 Ton',
                        'short_desc' => 'Distribusi barang retail dan material ringan dalam kota maupun antar kota.',
                        'price' => 900000, 'price_note' => '/rit', 'is_featured' => true,
                        'features' => ['Kapasitas 4 ton / 12 kubik', 'Bongkar muat opsional', 'Asuransi barang', 'Tracking via WhatsApp'],
                        'body' => 'Pilihan ekonomis untuk pengiriman rutin. Tersedia layanan bongkar muat dan penjadwalan berulang untuk kebutuhan distribusi harian.',
                    ],
                    [
                        'name' => 'Fuso Wingbox 8 Ton',
                        'short_desc' => 'Pengiriman barang palet dan karton dengan pintu samping untuk bongkar cepat.',
                        'price' => 2100000, 'price_note' => '/rit', 'is_featured' => true,
                        'features' => ['Kapasitas 8 ton / 24 kubik', 'Pintu samping wingbox', 'GPS tracking', 'Asuransi all risk opsional'],
                        'body' => 'Cocok untuk barang yang membutuhkan bongkar muat cepat di gudang tanpa forklift. Dilengkapi terpal dan penali barang.',
                    ],
                    [
                        'name' => 'Tronton 20 ft',
                        'short_desc' => 'Pengiriman volume besar dan container 20 kaki antar kota.',
                        'price' => 3200000, 'price_note' => '/rit', 'is_featured' => false,
                        'features' => ['Kapasitas hingga 20 ton', 'Container 20 ft', 'GPS tracking', 'Pengawalan opsional'],
                        'body' => 'Untuk material proyek, mesin industri, dan pengiriman container. Tim kami mengatur rute, izin, dan jadwal bongkar di lokasi tujuan.',
                    ],
                    [
                        'name' => 'Ekspedisi Antar Pulau',
                        'short_desc' => 'Pengiriman door-to-door ke luar Jawa melalui jalur laut.',
                        'price' => 6500000, 'price_note' => '/kirim', 'is_featured' => false,
                        'features' => ['Layanan door-to-door', 'Packing kayu dan wrapping', 'Asuransi barang', 'Update status berkala'],
                        'body' => 'Barang dikonsolidasikan di gudang mitra pelabuhan, lalu dikirim ke kota tujuan. Estimasi 7–14 hari kerja tergantung jadwal kapal.',
                    ],
                ],
            ],
            'logistik-proyek' => [
                'name' => 'Logistik Proyek',
                'icon' => 'package',
                'description' => 'Dukungan armada untuk proyek konstruksi, event, dan relokasi kantor.',
                'items' => [
                    [
                        'name' => 'Carter Harian Proyek',
                        'short_desc' => 'Unit dan driver standby di lokasi proyek sesuai jam kerja.',
                        'price' => 1800000, 'price_note' => '/hari', 'is_featured' => true,
                        'features' => ['Unit standby di lokasi', 'Driver dan BBM termasuk', 'Minimal 5 hari kerja', 'Laporan pemakaian harian'],
                        'body' => 'Untuk proyek yang butuh mobilitas material tidak terjadwal. Unit siaga penuh selama jam kerja sehingga koordinasi lebih cepat.',
                    ],
                ],
            ],
        ];

        $types = [];
        $order = 0;

        foreach ($data as $slug => $typeData) {
            $type = ServiceType::firstOrNew(['site_id' => $site->id, 'slug' => $slug]);
            $type->fill([
                'name' => $typeData['name'],
                'description' => $typeData['description'],
                'icon' => $typeData['icon'],
                'order' => $order++,
            ]);

            $this->save(fn () => $type->save());

            $types[$slug] = $type;

            foreach ($typeData['items'] as $index => $item) {
                $slugItem = Str::slug($item['name']);
                $service = Service::firstOrNew(['site_id' => $site->id, 'slug' => $slugItem]);

                $payload = [
                    'service_type_id' => $type->id,
                    'name' => $item['name'],
                    'short_desc' => $item['short_desc'],
                    'body' => $item['body'],
                    'features' => $item['features'],
                    'thumbnail_path' => $this->placeholder("layanan-{$slugItem}", 'FOTO ' . $item['name'], 1200, 675),
                    'price' => $item['price'],
                    'price_note' => $item['price_note'],
                    'is_featured' => $item['is_featured'],
                    'is_active' => true,
                    'order' => $index,
                ];

                // service_type_id bisa null saat dry-run (tipe belum punya id)
                if ($type->id === null) {
                    unset($payload['service_type_id']);
                }

                $service->fill($payload);
                $this->save(fn () => $service->save());
            }
        }

        $this->line('  service types: ' . count($data) . ', services: ' . collect($data)->sum(fn ($t) => count($t['items'])));

        return $types;
    }

    // ------------------------------------------------------------------ portfolios

    /**
     * @param  array<string, ServiceType>  $types
     */
    private function portfolios(Site $site, array $types): void
    {
        $items = [
            ['Distribusi Retail Nasional', 'PT Sumber Pangan Nusantara', 'ekspedisi-kargo', '2026-08-18', 'Pengiriman 1.200 karton produk makanan ke 14 kota di Jawa dengan 3 unit CDD box dan jadwal berulang dua kali seminggu.'],
            ['Relokasi Kantor Pusat', 'PT Graha Karya Abadi', 'logistik-proyek', '2026-07-05', 'Pemindahan furnitur, arsip, dan peralatan IT dari Jakarta ke Semarang dalam 3 hari kerja tanpa menghentikan operasional klien.'],
            ['Transportasi Event & Konferensi', 'PT Cipta Event Indonesia', 'rental-kendaraan', '2026-06-22', 'Menyediakan 4 Hiace dan 1 bus untuk transportasi peserta konferensi selama 3 hari, termasuk penjemputan bandara.'],
            ['Ekspedisi Antarpulau Material', 'CV Baja Mandiri', 'ekspedisi-kargo', '2026-05-14', 'Pengiriman 18 ton material konstruksi ke Kalimantan melalui jalur laut dengan packing dan asuransi barang.'],
            ['Kunjungan Industri Sekolah', 'SMK Negeri 2 Surakarta', 'rental-kendaraan', '2026-04-09', 'Charter 2 bus untuk 90 siswa dan guru ke kawasan industri di Jawa Barat selama 2 hari.'],
        ];

        foreach ($items as $order => [$title, $client, $typeSlug, $date, $description]) {
            $slug = Str::slug($title);
            $portfolio = Portfolio::firstOrNew(['site_id' => $site->id, 'slug' => $slug]);

            $payload = [
                'title' => $title,
                'client_name' => $client,
                'completion_date' => $date,
                'description' => $description,
                'thumbnail_path' => $this->placeholder("portofolio-{$slug}", 'FOTO ' . $title, 1200, 675),
                'gallery_images' => [
                    $this->placeholder("portofolio-{$slug}-1", 'DOKUMENTASI 1', 1200, 675),
                    $this->placeholder("portofolio-{$slug}-2", 'DOKUMENTASI 2', 1200, 675),
                ],
                'is_featured' => $order < 3,
                'order' => $order,
            ];

            if (isset($types[$typeSlug]) && $types[$typeSlug]->id !== null) {
                $payload['service_type_id'] = $types[$typeSlug]->id;
            }

            $portfolio->fill($payload);
            $this->save(fn () => $portfolio->save());
        }

        $this->line('  portfolios: ' . count($items));
    }

    // ------------------------------------------------------------------ branches

    private function branches(Site $site): void
    {
        $items = [
            ['Mitra Sejati Trans — Kantor Pusat', 'Jl. Batujamus - Jambangan Km. 2, Gempol, Mojodoyong, Kedawung, Sragen', '081329703060'],
            ['Mitra Sejati Trans — Cabang Surabaya', 'Jl. Rungkut Industri III No. 21, Rungkut, Surabaya', '081329703061'],
            ['Mitra Sejati Trans — Cabang Semarang', 'Jl. Majapahit No. 145, Gayamsari, Semarang', '081329703062'],
        ];

        foreach ($items as $order => [$name, $address, $phone]) {
            $branch = Branch::firstOrNew(['site_id' => $site->id, 'name' => $name]);
            $branch->fill([
                'address' => $address,
                'phone' => $phone,
                'order' => $order,
            ]);
            $this->save(fn () => $branch->save());
        }

        $this->line('  branches: ' . count($items));
    }

    // ------------------------------------------------------------------ articles

    private function articles(Site $site): void
    {
        $categories = ['Tips & Panduan', 'Operasional', 'Kabar Perusahaan'];

        $catIds = [];
        foreach ($categories as $order => $name) {
            $cat = ArticleCategory::firstOrNew(['site_id' => $site->id, 'slug' => Str::slug($name)]);
            $cat->fill(['name' => $name, 'order' => $order]);
            $this->save(fn () => $cat->save());
            $catIds[$name] = $cat->id;
        }

        $items = [
            ['Cara Menghitung Kubikasi Barang untuk Ekspedisi', 'Tips & Panduan', 'Rumus panjang x lebar x tinggi dibagi 1.000.000, plus tips memilih armada berdasarkan kubikasi dan berat barang.'],
            ['Memilih Armada untuk Distribusi Harian', 'Tips & Panduan', 'Perbandingan CDD box, fuso wingbox, dan tronton untuk kebutuhan distribusi rutin beserta perkiraan biaya per rit.'],
            ['Perawatan Armada: 7 Titik Cek Sebelum Perjalanan', 'Operasional', 'Daftar pemeriksaan singkat sebelum unit berangkat: oli, tekanan ban, rem, lampu, air radiator, terpal, dan dokumen.'],
            ['Mitra Sejati Trans Tambah 10 Unit Truk Baru', 'Kabar Perusahaan', 'Penambahan armada untuk mempercepat layanan ekspedisi rute Jawa bagian tengah dan timur mulai kuartal ini.'],
        ];

        foreach ($items as $index => [$title, $category, $excerpt]) {
            $slug = Str::slug($title);
            $article = Article::firstOrNew(['site_id' => $site->id, 'slug' => $slug]);

            $payload = [
                'title' => $title,
                'excerpt' => $excerpt,
                'body' => $excerpt . "\n\n" . 'Tim Mitra Sejati Trans menyusun panduan ini dari pengalaman operasional harian. Untuk kebutuhan spesifik, hubungi tim kami agar dapat dihitungkan sesuai rute, berat, dan jadwal pengiriman.',
                'thumbnail_path' => $this->placeholder("artikel-{$slug}", 'FOTO ARTIKEL', 1200, 675),
                'status' => 'published',
                'published_at' => now()->subDays(30 - ($index * 5)),
            ];

            if ($catIds[$category] !== null) {
                $payload['article_category_id'] = $catIds[$category];
            }

            $article->fill($payload);
            $this->save(fn () => $article->save());
        }

        $this->line('  article categories: ' . count($categories) . ', articles: ' . count($items));
    }

    // ------------------------------------------------------------------ faqs

    private function faqs(Site $site): void
    {
        $groups = [
            'Pemesanan' => [
                ['Berapa lama sebelumnya saya harus memesan armada?', 'Untuk rental kendaraan sebaiknya 3–7 hari sebelumnya, terutama akhir pekan. Untuk ekspedisi rutin, jadwal bisa ditetapkan bulanan.'],
                ['Apakah bisa sewa lepas kunci?', 'Bisa untuk area tertentu dengan verifikasi dokumen (KTP, SIM, dan jaminan). Untuk luar kota, kami sarankan menggunakan driver.'],
                ['Apakah menerima pengiriman satuan?', 'Untuk ekspedisi antar pulau, minimal 1 kubik atau 50 kg. Untuk dalam kota, minimal 1 rit armada.'],
            ],
            'Pembayaran' => [
                ['Bagaimana skema pembayaran?', 'DP 50% saat pemesanan dikonfirmasi, pelunasan setelah layanan selesai. Untuk klien korporat dengan kontrak, tersedia termin 14 hari.'],
                ['Apakah harga sudah termasuk tol dan BBM?', 'Untuk sewa dengan driver, BBM dan tol dihitung terpisah kecuali disebutkan lain di penawaran. Rincian selalu ditulis di surat penawaran.'],
            ],
            'Armada & Asuransi' => [
                ['Apakah barang pengiriman diasuransikan?', 'Ya, tersedia asuransi barang. Nilai pertanggungan mengikuti nilai yang dideklarasikan saat pemesanan.'],
                ['Bagaimana jika armada mengalami kendala di jalan?', 'Kami menyiagakan unit pengganti dan tim teknis. Kejadian dilaporkan ke PIC klien maksimal 2 jam setelah terdeteksi.'],
                ['Apakah ada tracking pengiriman?', 'Untuk armada wingbox dan tronton, tersedia GPS tracking. Untuk armada lain, update status dikirim via WhatsApp secara berkala.'],
            ],
        ];

        $order = 0;
        $faqCount = 0;
        $catCount = 0;
        $faqOrder = 0;

        foreach ($groups as $category => $list) {
            $cat = FaqCategory::firstOrNew(['site_id' => $site->id, 'slug' => Str::slug($category)]);
            $cat->fill(['name' => $category, 'order' => $catCount++]);
            $this->save(fn () => $cat->save());

            $faqOrder = 0;
            foreach ($list as [$question, $answer]) {
                $faq = Faq::firstOrNew(['site_id' => $site->id, 'question' => $question]);
                $payload = [
                    'answer' => $answer,
                    'order' => $faqOrder++,
                    'is_active' => true,
                ];
                if ($cat->id !== null) {
                    $payload['faq_category_id'] = $cat->id;
                }
                $faq->fill($payload);
                $this->save(fn () => $faq->save());
                $faqCount++;
            }
            $order++;
        }

        $this->line("  faq categories: {$catCount}, faqs: {$faqCount}");
    }

    // ------------------------------------------------------------------ testimonials

    private function testimonials(Site $site): void
    {
        $items = [
            ['Rina Kusumawati', 'Supply Chain Manager, PT Sumber Pangan Nusantara', 'Pengiriman rutin kami selalu tepat jadwal. Koordinasi dengan tim operasional juga cepat, termasuk saat ada perubahan rute mendadak.', 5],
            ['Andi Prabowo', 'General Affairs, PT Graha Karya Abadi', 'Relokasi kantor selesai lebih cepat dari rencana. Barang sensitif seperti server dan arsip tertata rapi.', 5],
            ['Siti Rahmawati', 'Event Organizer, Cipta Event Indonesia', 'Armada dan driver datang lebih awal dan sangat kooperatif terhadap perubahan jadwal acara.', 4],
            ['Bambang Setiawan', 'Direktur, CV Baja Mandiri', 'Pengiriman material ke luar pulau berjalan lancar. Pelaporan status dan dokumennya lengkap.', 5],
        ];

        foreach ($items as $order => [$name, $title, $message, $rating]) {
            $testimonial = Testimonial::firstOrNew(['site_id' => $site->id, 'name' => $name]);
            $testimonial->fill([
                'title' => $title,
                'message' => $message,
                'rating' => $rating,
                'order' => $order,
                'is_active' => true,
            ]);
            $this->save(fn () => $testimonial->save());
        }

        $this->line('  testimonials: ' . count($items));
    }

    // ------------------------------------------------------------------ partners

    private function partners(Site $site): void
    {
        $items = [
            ['Sumber Pangan Nusantara', 'https://example.com'],
            ['Graha Karya Abadi', 'https://example.com'],
            ['Cipta Event Indonesia', 'https://example.com'],
            ['Baja Mandiri', 'https://example.com'],
            ['Retail Sejahtera Group', 'https://example.com'],
        ];

        foreach ($items as $order => [$name, $url]) {
            $slug = Str::slug($name);
            $partner = Partner::firstOrNew(['site_id' => $site->id, 'name' => $name]);
            $partner->fill([
                'logo_path' => $this->placeholder("partner-{$slug}", $name, 600, 600),
                'website_url' => $url,
                'order' => $order,
            ]);
            $this->save(fn () => $partner->save());
        }

        $this->line('  partners: ' . count($items));
    }

    // ------------------------------------------------------------------ pages

    private function pages(Site $site): void
    {
        $wa = 'https://wa.me/6281329703060?text=' . rawurlencode('Halo Mitra Sejati Trans, saya ingin menanyakan ketersediaan armada.');
        $address = 'Jl. Batujamus - Jambangan Km. 2, Gempol, Mojodoyong, Kedawung, Sragen';
        $maps = 'https://maps.google.com/maps?q=' . rawurlencode('Kedawung, Sragen, Jawa Tengah') . '&t=&z=13&ie=UTF8&iwloc=&output=embed';

        $heroSlides = [
            ['Armada Siap Operasional', 'Sewa kendaraan, hiace, dan bus dengan driver berpengalaman untuk kebutuhan dinas maupun keluarga.', 'Sewa Kendaraan', $this->placeholder('hero-1', 'FOTO ARMADA', 1600, 900)],
            ['Ekspedisi Tepat Waktu', 'Pengiriman barang antar kota dan antar pulau dengan armada box, wingbox, dan tronton.', 'Ekspedisi & Kargo', $this->placeholder('hero-2', 'FOTO EKSPEDISI', 1600, 900)],
            ['Logistik untuk Proyek', 'Dukungan armada dan driver standby untuk proyek konstruksi, event, dan relokasi kantor.', 'Logistik Proyek', $this->placeholder('hero-3', 'FOTO PROYEK', 1600, 900)],
        ];

        $pages = [
            'home' => [
                'title' => 'Beranda',
                'meta_title' => 'Mitra Sejati Trans | Sewa Kendaraan & Ekspedisi',
                'sections' => [
                    ['hero', 'Mitra Sejati Trans', 'Sewa Kendaraan & Ekspedisi', 'Melayani kebutuhan transportasi dan distribusi bisnis Anda dengan armada terawat dan tim operasional yang responsif.', [
                        'slides' => array_map(fn ($s) => ['title' => $s[0], 'description' => $s[1], 'badge' => $s[2], 'image_path' => $s[3]], $heroSlides),
                        'stats' => [
                            ['label' => 'Armada', 'value' => '120+'],
                            ['label' => 'Rute Terlayani', 'value' => '35+'],
                            ['label' => 'Klien Aktif', 'value' => '250+'],
                            ['label' => 'Pengalaman', 'value' => '12 th'],
                        ],
                        'cta_primary' => ['text' => 'Lihat Layanan', 'url' => '#featured'],
                        'cta_secondary' => ['text' => 'Minta Penawaran', 'url' => '/kontak'],
                    ]],
                    ['about', 'Mitra Logistik untuk Bisnis Anda', 'Tentang Kami', 'Mitra Sejati Trans mengelola armada dan pengiriman untuk kebutuhan distribusi, proyek, dan perjalanan dinas. Semua unit dirawat berkala dan setiap perjalanan dicatat agar Anda bisa melacak status layanan.', [
                        'stats' => [
                            ['label' => 'Unit Siap Jalan', 'value' => '120+'],
                            ['label' => 'Kota Tujuan', 'value' => '35+'],
                            ['label' => 'Ketepatan Waktu', 'value' => '98%'],
                        ],
                    ]],
                    ['cta', 'Butuh Armada untuk Kebutuhan Mendesak?', 'Tim kami siap mengecek ketersediaan unit dan menghitung estimasi biaya hari ini juga.', [
                        'button_label' => 'Hubungi via WhatsApp',
                        'button_url' => $wa,
                    ]],
                    ['office', 'Kantor Pusat', 'Kunjungi kantor operasional kami untuk konsultasi rute dan penawaran.', [
                        'badge' => 'Lokasi',
                        'maps_embed' => $maps,
                    ]],
                ],
            ],
            'profil' => [
                'title' => 'Profil',
                'meta_title' => 'Profil | Mitra Sejati Trans',
                'sections' => [
                    ['hero', 'Profil Mitra Sejati Trans', 'Unit usaha transportasi dan logistik yang tumbuh bersama kebutuhan pelanggan', [
                        'badge' => 'Tentang Kami',
                        'image_path' => $this->placeholder('profil', 'FOTO KANTOR', 1200, 900),
                        'image_label' => 'Kantor Pusat',
                        'image_caption' => $address,
                    ]],
                    ['sejarah', 'Perjalanan Kami', null, implode("\n\n", [
                        'Mitra Sejati Trans berawal dari kebutuhan internal koperasi akan kendaraan operasional yang andal. Dari satu unit yang dipakai bergiliran, layanan ini kemudian dibuka untuk anggota dan masyarakat sekitar.',
                        'Seiring permintaan pengiriman yang meningkat, kami menambah armada box dan wingbox serta membuka layanan ekspedisi antar kota. Setiap penambahan unit diikuti pelatihan driver dan standar pemeriksaan kendaraan sebelum berangkat.',
                        'Saat ini Mitra Sejati Trans melayani distribusi retail, logistik proyek, dan transportasi rombongan dengan kantor operasional di Sragen, Surabaya, dan Semarang.',
                    ]), null],
                    ['visi-misi', 'Visi & Misi', null, [
                        'visi' => 'Menjadi mitra transportasi dan logistik yang paling dapat diandalkan bagi pelaku usaha di Jawa.',
                        'misi' => [
                            ['title' => 'Tepat Waktu', 'desc' => 'Menjaga komitmen jadwal pengiriman dan penjemputan melalui perencanaan rute yang realistis.'],
                            ['title' => 'Armada Terawat', 'desc' => 'Melakukan pemeriksaan berkala dan perawatan preventif agar perjalanan aman dan barang sampai utuh.'],
                            ['title' => 'Harga Transparan', 'desc' => 'Menuliskan seluruh komponen biaya di penawaran sehingga tidak ada biaya tambahan di luar kesepakatan.'],
                            ['title' => 'Tim yang Responsif', 'desc' => 'Menempatkan PIC yang mudah dihubungi untuk setiap klien dan setiap perjalanan.'],
                        ],
                    ]],
                    ['values', 'Nilai yang Kami Pegang', null, [
                        'badge' => 'Budaya Kerja',
                        'items' => [
                            ['title' => 'Keselamatan Utama', 'description' => 'Pemeriksaan kendaraan dan istirahat driver diatur ketat untuk perjalanan jarak jauh.'],
                            ['title' => 'Komunikasi Jelas', 'description' => 'Setiap perubahan jadwal atau kendala disampaikan ke klien maksimal 2 jam sejak diketahui.'],
                            ['title' => 'Dokumentasi Rapi', 'description' => 'Surat jalan, timbangan, dan foto kondisi barang disimpan untuk kebutuhan klaim.'],
                        ],
                    ]],
                ],
            ],
            'produk' => [
                'title' => 'Layanan',
                'meta_title' => 'Layanan | Mitra Sejati Trans',
                'sections' => [
                    ['hero', 'Layanan Transportasi & Logistik', 'Pilih armada sesuai kebutuhan: harian, per rit, atau kontrak bulanan', [
                        'slides' => [
                            ['title' => 'Sewa Harian', 'description' => 'Kendaraan dan driver siap pakai untuk perjalanan dinas maupun wisata.', 'badge' => 'Rental', 'image_path' => $this->placeholder('produk-1', 'FOTO RENTAL', 1600, 900)],
                            ['title' => 'Pengiriman per Rit', 'description' => 'Armada box, wingbox, dan tronton dengan tarif per rit sesuai rute.', 'badge' => 'Ekspedisi', 'image_path' => $this->placeholder('produk-2', 'FOTO EKSPEDISI', 1600, 900)],
                        ],
                        'cta_label' => 'Minta Penawaran via WhatsApp',
                        'cta_url' => $wa,
                    ]],
                    ['cta', 'Belum Menentukan Armada?', 'Ceritakan jenis barang atau jumlah penumpang, tim kami bantu pilih unit yang paling efisien.', [
                        'button_label' => 'Konsultasi Sekarang',
                        'button_url' => $wa,
                    ]],
                ],
            ],
            'portofolio' => [
                'title' => 'Portofolio',
                'meta_title' => 'Portofolio | Mitra Sejati Trans',
                'sections' => [
                    ['hero', 'Pengalaman Kami', 'Beberapa penugasan distribusi, relokasi, dan transportasi rombongan yang telah kami tangani', null],
                ],
            ],
            'cabang' => [
                'title' => 'Cabang',
                'meta_title' => 'Cabang Kami | Mitra Sejati Trans',
                'sections' => [
                    ['hero', 'Cabang & Titik Operasional', 'Kantor operasional kami di Jawa Tengah dan Jawa Timur siap melayani penjemputan unit', [
                        'stats' => [
                            ['label' => 'Kantor', 'value' => '3'],
                            ['label' => 'Kantor Pusat', 'value' => '1'],
                            ['label' => 'Cabang', 'value' => '2'],
                        ],
                    ]],
                ],
            ],
            'karir' => [
                'title' => 'Karir',
                'meta_title' => 'Karir | Mitra Sejati Trans',
                'sections' => [
                    ['hero', 'Karir di Mitra Sejati Trans', 'Bergabung dengan tim operasional yang tumbuh bersama layanan transportasi dan logistik', [
                        'image_path' => $this->placeholder('loker', 'FOTO KARIR', 1200, 900),
                    ]],
                    ['values', 'Mengapa Bergabung Bersama Kami?', null, [
                        'badge' => 'Karier',
                        'items' => [
                            ['title' => 'Pelatihan Berkala', 'description' => 'Pelatihan keselamatan berkendara dan penanganan barang untuk seluruh driver dan staf gudang.'],
                            ['title' => 'Jenjang Jelas', 'description' => 'Evaluasi kinerja dua kali setahun dengan jalur promosi ke posisi koordinator.'],
                            ['title' => 'Fasilitas Kerja', 'description' => 'Armada terawat, perlengkapan keselamatan lengkap, dan tunjangan perjalanan.'],
                        ],
                        'openings' => [
                            ['title' => 'Driver Truk Antar Kota', 'location' => 'Sragen', 'type' => 'Full-time', 'description' => 'SIM B2 umum, pengalaman minimal 2 tahun, memahami rute Jawa.', 'deadline' => '2026-12-31'],
                            ['title' => 'Admin Operasional', 'location' => 'Surabaya', 'type' => 'Full-time', 'description' => 'Mengelola jadwal armada, surat jalan, dan komunikasi dengan klien.', 'deadline' => '2026-12-20'],
                            ['title' => 'Sales Korporat', 'location' => 'Semarang', 'type' => 'Full-time', 'description' => 'Menangani penawaran dan hubungan klien korporat di wilayah Jawa Tengah.', 'deadline' => '2026-12-15'],
                        ],
                    ]],
                ],
            ],
            'berita' => [
                'title' => 'Berita',
                'meta_title' => 'Artikel & Berita | Mitra Sejati Trans',
                'sections' => [
                    ['hero', 'Artikel & Berita', 'Panduan operasional, tips logistik, dan kabar terbaru dari Mitra Sejati Trans', null],
                ],
            ],
            'kontak' => [
                'title' => 'Kontak',
                'meta_title' => 'Kontak | Mitra Sejati Trans',
                'sections' => [
                    ['hero', 'Hubungi Kami', 'Tim kami siap membantu perhitungan rute, ketersediaan armada, dan penawaran harga', [
                        'cta_primary' => ['text' => 'Chat WhatsApp', 'url' => $wa],
                        'cta_secondary' => ['text' => 'Kirim Penawaran', 'url' => '#inquiry'],
                    ]],
                ],
            ],
        ];

        foreach ($pages as $slug => $pageData) {
            $page = Page::firstOrNew(['site_id' => $site->id, 'slug' => $slug]);
            $page->fill([
                'title' => $pageData['title'],
                'meta_title' => $pageData['meta_title'],
                'is_active' => true,
            ]);
            $this->save(fn () => $page->save());

            $sectionOrder = 0;
            foreach ($pageData['sections'] as $sectionData) {
                $key = $sectionData[0];
                $title = $sectionData[1] ?? null;
                $subtitle = $sectionData[2] ?? null;
                $body = null;
                $payload = null;

                if (count($sectionData) >= 5) {
                    $body = $sectionData[3];
                    $payload = $sectionData[4];
                } elseif (count($sectionData) === 4) {
                    $payload = $sectionData[3];
                }

                if ($page->id === null) {
                    $sectionOrder++;
                    continue;
                }

                $section = $page->sections()->firstOrNew(['key' => $key]);
                $section->fill([
                    'title' => $title,
                    'subtitle' => $subtitle,
                    'body' => $body,
                    'payload' => $payload === null ? null : $this->withImageUrls($payload),
                    'order' => $sectionOrder++,
                    'is_active' => true,
                ]);
                $this->save(fn () => $section->save());
            }
        }

        $this->line('  pages: ' . count($pages) . ', sections: ' . collect($pages)->sum(fn ($p) => count($p['sections'])));
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

    /** Placeholder PNG berlabel supaya preview template tidak kosong; PIC tinggal mengganti fotonya. */
    private function placeholder(string $name, string $label, int $width, int $height): string
    {
        $path = "template/transportasi/{$name}.png";

        if (isset($this->placeholders[$path])) {
            return $path;
        }
        $this->placeholders[$path] = true;

        if ($this->dryRun) {
            return $path;
        }

        $disk = Storage::disk('public');
        if ($disk->exists($path)) {
            return $path;
        }

        $absolute = $disk->path($path);
        if (! is_dir(dirname($absolute))) {
            mkdir(dirname($absolute), 0775, true);
        }

        $image = imagecreatetruecolor($width, $height);
        $bg = imagecolorallocate($image, 226, 232, 240);
        $grid = imagecolorallocate($image, 203, 213, 225);
        $text = imagecolorallocate($image, 100, 116, 139);

        imagefilledrectangle($image, 0, 0, $width, $height, $bg);
        for ($x = -$height; $x < $width; $x += 56) {
            imageline($image, $x, 0, $x + $height, $height, $grid);
        }
        imagerectangle($image, 0, 0, $width - 1, $height - 1, $grid);

        $title = strtoupper(Str::limit($label, 34, ''));
        $titleWidth = imagefontwidth(5) * strlen($title);
        imagestring($image, 5, max(8, (int) (($width - $titleWidth) / 2)), (int) ($height / 2) - 16, $title, $text);

        $note = 'GANTI DENGAN FOTO ASLI';
        $noteWidth = imagefontwidth(3) * strlen($note);
        imagestring($image, 3, max(8, (int) (($width - $noteWidth) / 2)), (int) ($height / 2) + 12, $note, $text);

        imagepng($image, $absolute);
        imagedestroy($image);

        return $path;
    }
}
