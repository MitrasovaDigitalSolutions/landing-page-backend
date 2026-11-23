<?php

namespace App\Console\Commands;

use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\Branch;
use App\Models\Faq;
use App\Models\FaqCategory;
use App\Models\Page;
use App\Models\Portfolio;
use App\Models\Service;
use App\Models\ServiceType;
use App\Models\Site;
use App\Models\SiteSetting;
use App\Models\Testimonial;
use Illuminate\Console\Command;

/**
 * Konten DEMO untuk tenant Miset (slug: konveksi). Semua teks di sini placeholder
 * dan akan diganti lewat dashboard admin setelah desain disetujui.
 */
class SeedMisetDemo extends Command
{
    protected $signature = 'cms:seed-demo-miset';

    protected $description = 'Isi konten demo untuk tenant Miset (konveksi)';

    public function handle(): int
    {
        $site = Site::where('slug', 'konveksi')->firstOrFail();
        $site->update(['name' => 'Miset']);

        $this->settings($site);
        $types = $this->services($site);
        $this->pages($site);
        $this->articles($site);
        $this->faqs($site);
        $this->testimonials($site);
        $this->portfolios($site, $types);
        $this->branches($site);
        $this->prune($site);

        $this->info('Selesai. Konten demo Miset (slug: konveksi) siap.');
        $this->line('  service types: ' . ServiceType::where('site_id', $site->id)->count());
        $this->line('  services: ' . Service::where('site_id', $site->id)->count());
        $this->line('  pages: ' . Page::where('site_id', $site->id)->count());
        $this->line('  articles: ' . Article::where('site_id', $site->id)->count());
        $this->line('  faqs: ' . Faq::where('site_id', $site->id)->count());
        $this->line('  portfolios: ' . Portfolio::where('site_id', $site->id)->count());
        $this->line('  branches: ' . Branch::where('site_id', $site->id)->count());

        return self::SUCCESS;
    }

    /**
     * Buang konten dummy bawaan seeder yang tidak termasuk set demo ini.
     */
    private function prune(Site $site): void
    {
        Service::where('site_id', $site->id)->whereNotIn('slug', [
            'seragam-sekolah', 'seragam-kantor', 'seragam-instansi',
            'kaos-komunitas', 'kaos-event', 'jaket-hoodie',
            'totebag-apron', 'almamater-jas',
        ])->delete();

        ServiceType::where('site_id', $site->id)->whereNotIn('slug', ['seragam', 'kaos-apparel', 'merchandise'])->delete();

        Article::where('site_id', $site->id)->whereNotIn('slug', [
            'memilih-bahan-kaos-komunitas', 'cek-ukuran-seragam', 'workshop-miset-mulai-produksi-jaket',
        ])->delete();

        ArticleCategory::where('site_id', $site->id)->whereNotIn('slug', ['tips', 'kabar'])->delete();

        Faq::where('site_id', $site->id)->whereNotIn('question', [
            'Berapa minimal order?', 'Berapa lama produksinya?', 'Apakah bisa minta contoh bahan?',
            'Bagaimana sistem pembayaran?', 'Apakah bisa kirim ke luar kota?', 'Apakah ada garansi?',
        ])->delete();

        FaqCategory::where('site_id', $site->id)->whereNotIn('slug', ['pemesanan', 'pembayaran'])->delete();

        Portfolio::where('site_id', $site->id)->whereNotIn('slug', [
            'seragam-smp-nusantara', 'kaos-komunitas-gowes-karanganyar',
            'jaket-hoodie-tim-produksi', 'totebag-kafe-jambangan',
        ])->delete();

        Testimonial::where('site_id', $site->id)->whereNotIn('name', ['Ibu Rina', 'Bagas', 'Pak Yusuf', 'Dewi'])->delete();

        Branch::where('site_id', $site->id)->whereNotIn('name', ['Workshop Jambangan', 'Showroom Karanganyar'])->delete();

        // Miset tidak punya unit usaha
        \App\Models\BusinessUnit::where('site_id', $site->id)->delete();

        Page::where('site_id', $site->id)->whereNotIn('slug', [
            'home', 'profil', 'produk', 'portofolio', 'karir', 'berita', 'kontak',
        ])->delete();
    }

    private function settings(Site $site): void
    {
        $maps = 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3954.3!2d110.98!3d-7.55!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2sKaranganyar!5e0!3m2!1sid!2sid!4v1700000000000';

        $settings = [
            ['general', 'site_title', 'Miset'],
            ['general', 'tagline', 'Konveksi & Garment untuk Kebutuhan Brand Anda'],
            ['general', 'brand_color', '#0F766E'],
            ['general', 'meta_keywords', 'miset, konveksi karanganyar, seragam sekolah, kaos komunitas, jaket, sablon'],
            ['contact', 'phone', '(0271)-6493247'],
            ['contact', 'whatsapp', '6282247170756'],
            ['contact', 'email', 'halo@miset.id'],
            ['contact', 'address', 'Jl. Jambangan - Grompol Km. 1, Jambangan, Pereng, Mojogedang, Karanganyar'],
            ['contact', 'maps_embed', $maps],
            ['social', 'instagram', 'https://instagram.com/miset.id'],
            ['social', 'facebook', 'https://facebook.com/miset.id'],
            ['legal', 'copyright', '© 2026 Miset. Seluruh hak cipta dilindungi.'],
            ['modules', 'is_active', [
                'services' => true,
                'portfolios' => true,
                'business_units' => false,
                'careers' => true,
                'news' => true,
                'contact' => true,
            ]],
        ];

        foreach ($settings as [$group, $key, $value]) {
            SiteSetting::updateOrCreate(
                ['site_id' => $site->id, 'key' => $key],
                ['group' => $group, 'value' => $value]
            );
        }
    }

    /**
     * @return array<string, ServiceType>
     */
    private function services(Site $site): array
    {
        $types = [
            'seragam' => [
                'name' => 'Seragam',
                'icon' => 'shirt',
                'description' => 'Pembuatan seragam sekolah, kantor, dan instansi dengan bahan nyaman dan jahitan rapi.',
                'services' => [
                    [
                        'name' => 'Seragam Sekolah',
                        'short_desc' => 'Seragam SD, SMP, dan SMA sesuai ketentuan sekolah.',
                        'body' => 'Kami memproduksi seragam sekolah dengan pola standar, bahan adem, dan ukuran yang bisa disesuaikan per siswa. Cocok untuk kebutuhan awal tahun ajaran maupun tambahan di tengah semester.',
                        'features' => ['Bahan katun/viscose pilihan', 'Ukuran per siswa', 'Bordir logo sekolah', 'Minimal 12 pcs per model'],
                        'is_featured' => true,
                    ],
                    [
                        'name' => 'Seragam Kantor',
                        'short_desc' => 'Seragam kerja kantor, pabrik, dan instansi dengan identitas perusahaan.',
                        'body' => 'Seragam kerja dirancang mengikuti warna dan logo perusahaan. Tersedia model kemeja, polo, maupun wearpack ringan dengan pilihan bahan yang menyesuaikan lingkungan kerja.',
                        'features' => ['Konsultasi desain & warna', 'Bordir atau sablon logo', 'Ukuran tim', 'Produksi 2–3 minggu'],
                        'is_featured' => true,
                    ],
                    [
                        'name' => 'Seragam Instansi',
                        'short_desc' => 'Seragam dinas dan komunitas dengan standar jahitan rapi.',
                        'body' => 'Melayani pesanan seragam dinas, organisasi, dan komunitas dengan jumlah besar. Kami menyediakan contoh bahan dan pola sebelum produksi agar hasil sesuai kesepakatan.',
                        'features' => ['Contoh bahan sebelum produksi', 'Jahitan rantai & obras rapi', 'Label nama opsional', 'Bisa cicil produksi'],
                        'is_featured' => false,
                    ],
                ],
            ],
            'kaos-apparel' => [
                'name' => 'Kaos & Apparel',
                'icon' => 'tshirt',
                'description' => 'Kaos komunitas, kaos event, jaket, dan hoodie dengan sablon atau bordir.',
                'services' => [
                    [
                        'name' => 'Kaos Komunitas',
                        'short_desc' => 'Kaos komunitas dan tim dengan sablon tahan lama.',
                        'body' => 'Kaos komunitas diproduksi dengan bahan cotton combed 24s atau 30s dan sablon plastisol. Cocok untuk komunitas, tim olahraga, dan reuni.',
                        'features' => ['Cotton combed 24s/30s', 'Sablon plastisol/rubber', 'Size S–XXL', 'Minimal 6 pcs'],
                        'is_featured' => true,
                    ],
                    [
                        'name' => 'Kaos Event',
                        'short_desc' => 'Kaos acara, seminar, dan gathering dengan waktu produksi cepat.',
                        'body' => 'Untuk kebutuhan acara dengan tenggat ketat, kami menyediakan jalur produksi cepat. Desain final dikunci H-7 agar kualitas sablon tetap terjaga.',
                        'features' => ['Produksi cepat 5–7 hari', 'Sablon 1–3 warna', 'Opsional nama peserta', 'Harga grosir'],
                        'is_featured' => false,
                    ],
                    [
                        'name' => 'Jaket & Hoodie',
                        'short_desc' => 'Jaket dan hoodie custom untuk komunitas maupun perusahaan.',
                        'body' => 'Jaket dan hoodie dibuat dengan bahan fleece atau taslan, dilengkapi bordir logo dan pilihan aksesori seperti resleting dan hoodie string.',
                        'features' => ['Bahan fleece/taslan', 'Bordir logo', 'Pilihan lining', 'Produksi 2–4 minggu'],
                        'is_featured' => true,
                    ],
                ],
            ],
            'merchandise' => [
                'name' => 'Merchandise',
                'icon' => 'gift',
                'description' => 'Totebag, apron, almamater, dan merchandise lain untuk brand Anda.',
                'services' => [
                    [
                        'name' => 'Totebag & Apron',
                        'short_desc' => 'Totebag kanvas dan apron custom untuk brand dan kafe.',
                        'body' => 'Totebag kanvas dan apron diproduksi dengan sablon atau bordir sesuai identitas brand. Pilihan bahan blacu, kanvas, dan drill.',
                        'features' => ['Bahan blacu/kanvas/drill', 'Sablon atau bordir', 'Model dan ukuran custom', 'Minimal 12 pcs'],
                        'is_featured' => false,
                    ],
                    [
                        'name' => 'Almamater & Jas',
                        'short_desc' => 'Almamater sekolah dan jas wisuda dengan ukuran pas.',
                        'body' => 'Almamater dan jas dibuat dengan ukuran per siswa maupun ukuran standar. Tersedia opsi sewa dan beli untuk kebutuhan wisuda.',
                        'features' => ['Ukuran per siswa', 'Bahan hitam doff premium', 'Bordir nama & logo', 'Opsi sewa'],
                        'is_featured' => true,
                    ],
                ],
            ],
        ];

        $result = [];

        foreach ($types as $typeSlug => $data) {
            $type = ServiceType::updateOrCreate(
                ['site_id' => $site->id, 'slug' => $typeSlug],
                [
                    'name' => $data['name'],
                    'description' => $data['description'],
                    'icon' => $data['icon'],
                    'order' => array_search($typeSlug, array_keys($types), true),
                ]
            );

            $result[$typeSlug] = $type;

            foreach ($data['services'] as $index => $service) {
                Service::updateOrCreate(
                    ['site_id' => $site->id, 'slug' => str($service['name'])->slug()],
                    [
                        'service_type_id' => $type->id,
                        'name' => $service['name'],
                        'short_desc' => $service['short_desc'],
                        'body' => $service['body'],
                        'features' => $service['features'],
                        'is_featured' => $service['is_featured'],
                        'is_active' => true,
                        'order' => $index,
                    ]
                );
            }
        }

        return $result;
    }

    private function pages(Site $site): void
    {
        $hero = [
            [
                'title' => 'Konveksi & Garment untuk Brand Anda',
                'description' => 'Miset memproduksi seragam, kaos, jaket, dan merchandise dengan kualitas jahitan dan sablon yang bisa diandalkan.',
                'badge' => 'Miset',
            ],
            [
                'title' => 'Dari Desain sampai Kirim',
                'description' => 'Satu tim menangani konsultasi desain, contoh bahan, produksi, dan pengiriman ke lokasi Anda.',
                'badge' => 'Satu Pintu',
            ],
            [
                'title' => 'Order Kecil pun Dilayani',
                'description' => 'Mulai dari 6 pcs untuk kaos komunitas hingga produksi seragam rutin tahunan.',
                'badge' => 'Mulai 6 pcs',
            ],
        ];

        $pages = [
            'home' => [
                'title' => 'Beranda',
                'meta_title' => 'Miset | Konveksi & Garment Karanganyar',
                'sections' => [
                    ['hero', 'Konveksi & Garment', 'Seragam, kaos, jaket, dan merchandise untuk komunitas maupun perusahaan', [
                        'slides' => $hero,
                        'stats' => [
                            ['label' => 'Berdiri', 'value' => '2014'],
                            ['label' => 'Klien', 'value' => '120+'],
                            ['label' => 'Kapasitas', 'value' => '2.000 pcs/bln'],
                            ['label' => 'Workshop', 'value' => 'Karanganyar'],
                        ],
                    ]],
                    ['featured', 'Layanan Kami', 'Pilih jenis produksi sesuai kebutuhan Anda', [
                        'badge' => 'Layanan Kami',
                        'cards' => [
                            ['title' => 'Seragam', 'description' => 'Seragam sekolah, kantor, dan instansi dengan bordir logo.', 'products' => ['Seragam Sekolah', 'Seragam Kantor', 'Seragam Instansi']],
                            ['title' => 'Kaos & Apparel', 'description' => 'Kaos komunitas, kaos event, jaket, dan hoodie.', 'products' => ['Kaos Komunitas', 'Kaos Event', 'Jaket & Hoodie']],
                        ],
                        'cta_label' => 'Lihat Semua Layanan',
                    ]],
                    ['cta', 'Butuh Contoh Bahan?', 'Datang ke workshop kami atau minta sample bahan dan estimasi harga via WhatsApp.', [
                        'button_label' => 'Chat WhatsApp',
                        'button_url' => 'https://wa.me/6282247170756?text=Halo%20Miset,%20saya%20ingin%20tanya%20harga%20konveksi',
                    ]],
                    ['office', 'Alamat Workshop', 'Jl. Jambangan - Grompol Km. 1, Jambangan, Pereng, Mojogedang, Karanganyar', [
                        'badge' => 'Lokasi',
                        'maps_embed' => 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3954.3!2d110.98!3d-7.55!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2sKaranganyar!5e0!3m2!1sid!2sid!4v1700000000000',
                    ]],
                ],
            ],
            'profil' => [
                'title' => 'Tentang Kami',
                'meta_title' => 'Tentang Miset',
                'sections' => [
                    ['hero', 'Tentang Miset', 'Konveksi yang tumbuh dari kebutuhan seragam sekolah di Karanganyar', [
                        'badge' => 'Tentang Kami',
                    ]],
                    ['about', 'Cerita Kami', 'Miset dimulai pada 2014 sebagai konveksi kecil yang melayani seragam sekolah di Karanganyar. Dari satu mesin jahit dan dua penjahit, kini kami memiliki workshop sendiri dengan tim cutting, jahit, sablon, dan finishing. Fokus kami sederhana: hasil rapi, bahan nyaman, dan pesanan selesai sesuai janji.', null],
                    ['visi-misi', 'Visi & Misi', null, [
                        'visi' => 'Menjadi mitra konveksi terpercaya bagi sekolah, komunitas, dan perusahaan di Jawa Tengah.',
                        'misi' => [
                            ['title' => 'Kualitas Terjaga', 'desc' => 'Menjaga standar jahitan dan sablon di setiap pesanan, berapa pun jumlahnya.'],
                            ['title' => 'Transparan', 'desc' => 'Harga, bahan, dan waktu produksi disepakati di awal tanpa biaya tersembunyi.'],
                            ['title' => 'Tepat Waktu', 'desc' => 'Jadwal produksi disusun realistis dan dipantau harian.'],
                            ['title' => 'Tumbuh Bersama', 'desc' => 'Melibatkan penjahit lokal dan terus meningkatkan keterampilan tim.'],
                        ],
                    ]],
                    ['values', 'Kenapa Memilih Miset?', null, [
                        'items' => [
                            ['title' => 'Bahan Bisa Dicek', 'description' => 'Anda bisa melihat dan merasakan bahan sebelum produksi dimulai.'],
                            ['title' => 'Ukuran Menyesuaikan', 'description' => 'Untuk seragam, kami menerima daftar ukuran per orang.'],
                            ['title' => 'Garansi Jahitan', 'description' => 'Kerusakan jahitan akibat produksi kami perbaiki tanpa biaya.'],
                        ],
                    ]],
                ],
            ],
            'produk' => [
                'title' => 'Produk',
                'meta_title' => 'Produk & Layanan Miset',
                'sections' => [
                    ['hero', 'Produk & Layanan', 'Seragam, kaos, jaket, dan merchandise yang bisa disesuaikan dengan kebutuhan Anda', [
                        'slides' => [],
                        'cta_label' => 'Konsultasi via WhatsApp',
                    ]],
                    ['cta', 'Belum Yakin Modelnya?', 'Kirim referensi desain Anda, tim kami bantu pilih bahan dan model yang cocok.', [
                        'button_label' => 'Kirim Referensi',
                        'button_url' => 'https://wa.me/6282247170756?text=Halo%20Miset,%20saya%20punya%20referensi%20desain',
                    ]],
                ],
            ],
            'portofolio' => [
                'title' => 'Portofolio',
                'meta_title' => 'Portofolio Miset',
                'sections' => [
                    ['hero', 'Portofolio', 'Sebagian pesanan yang sudah kami kerjakan', [
                        'badge' => 'Karya Kami',
                    ]],
                ],
            ],
            'karir' => [
                'title' => 'Karir',
                'meta_title' => 'Karir di Miset',
                'sections' => [
                    ['hero', 'Karir di Miset', 'Bergabung dengan tim produksi dan layanan kami', [
                        'badge' => 'Karir',
                    ]],
                    ['values', 'Mengapa Bergabung?', null, [
                        'items' => [
                            ['title' => 'Belajar Langsung', 'description' => 'Terlibat dari cutting sampai finishing, bukan hanya satu bagian.'],
                            ['title' => 'Tim Kecil, Peran Jelas', 'description' => 'Setiap orang punya tanggung jawab yang jelas dan dihargai.'],
                            ['title' => 'Lowongan Terbuka', 'description' => 'Update lowongan diumumkan di halaman ini dan Instagram kami.'],
                        ],
                        'openings' => [],
                    ]],
                ],
            ],
            'berita' => [
                'title' => 'Berita',
                'meta_title' => 'Berita & Artikel Miset',
                'sections' => [
                    ['hero', 'Berita & Artikel', 'Tips memilih bahan dan kabar terbaru dari workshop kami', [
                        'badge' => 'Berita',
                    ]],
                ],
            ],
            'kontak' => [
                'title' => 'Kontak',
                'meta_title' => 'Hubungi Miset',
                'sections' => [
                    ['hero', 'Hubungi Kami', 'Sampaikan kebutuhan Anda, kami balas pada jam kerja', [
                        'badge' => 'Kontak',
                    ]],
                ],
            ],
        ];

        foreach ($pages as $slug => $data) {
            $page = Page::updateOrCreate(
                ['site_id' => $site->id, 'slug' => $slug],
                [
                    'title' => $data['title'],
                    'meta_title' => $data['meta_title'],
                    'meta_desc' => str($data['sections'][0][2] ?? '')->limit(155)->toString(),
                    'is_active' => true,
                ]
            );

            foreach ($data['sections'] as $index => [$key, $title, $body, $payload]) {
                $page->sections()->updateOrCreate(
                    ['key' => $key],
                    ['title' => $title, 'body' => $body, 'payload' => $payload, 'order' => $index, 'is_active' => true]
                );
            }

            $page->sections()->whereNotIn('key', array_column($data['sections'], 0))->delete();
        }
    }

    private function articles(Site $site): void
    {
        $categories = [
            'tips' => ['name' => 'Tips', 'description' => 'Panduan memilih bahan dan model.'],
            'kabar' => ['name' => 'Kabar', 'description' => 'Update kegiatan workshop.'],
        ];

        $catIds = [];
        $i = 0;
        foreach ($categories as $slug => $data) {
            $catIds[$slug] = ArticleCategory::updateOrCreate(
                ['site_id' => $site->id, 'slug' => $slug],
                ['name' => $data['name'], 'description' => $data['description'], 'order' => $i++]
            )->id;
        }

        $articles = [
            [
                'slug' => 'memilih-bahan-kaos-komunitas',
                'category' => 'tips',
                'title' => 'Memilih Bahan Kaos untuk Komunitas',
                'excerpt' => 'Cotton combed 24s, 30s, atau CVC? Ini perbedaannya.',
                'body' => "Bahan menentukan kenyamanan kaos, apalagi kalau dipakai untuk kegiatan rutin.\n\nCotton combed 30s lebih ringan dan adem, cocok untuk iklim panas. Cotton combed 24s sedikit lebih tebal sehingga tidak mudah menerawang. CVC cocok untuk sablon warna terang dan harga lebih ekonomis.\n\nUntuk kaos komunitas dengan desain dominan, kami biasanya menyarankan combed 24s agar sablon lebih tajam.",
            ],
            [
                'slug' => 'cek-ukuran-seragam',
                'category' => 'tips',
                'title' => 'Cara Kirim Daftar Ukuran Seragam',
                'excerpt' => 'Format sederhana agar produksi tidak salah ukuran.',
                'body' => "Kesalahan ukuran adalah penyebab paling umum pesanan seragam diulang.\n\nKirim daftar dalam format: nama, ukuran baju, ukuran celana/rok, dan catatan khusus. Untuk siswa yang bertumbuh cepat, tambahkan 1 ukuran cadangan.\n\nKami akan kirim contoh size chart sebelum produksi dimulai.",
            ],
            [
                'slug' => 'workshop-miset-mulai-produksi-jaket',
                'category' => 'kabar',
                'title' => 'Workshop Kami Mulai Melayani Produksi Jaket',
                'excerpt' => 'Kapasitas tambahan untuk bordir dan jaket custom.',
                'body' => "Mulai bulan ini workshop kami menerima pesanan jaket dan hoodie dengan bordir logo.\n\nPenambahan mesin bordir membuat waktu produksi lebih singkat dan hasil lebih konsisten. Untuk pesanan perdana, tersedia contoh bahan fleece dan taslan di workshop.",
            ],
        ];

        foreach ($articles as $index => $data) {
            Article::updateOrCreate(
                ['site_id' => $site->id, 'slug' => $data['slug']],
                [
                    'article_category_id' => $catIds[$data['category']],
                    'title' => $data['title'],
                    'excerpt' => $data['excerpt'],
                    'body' => $data['body'],
                    'status' => 'published',
                    'published_at' => now()->subDays(3 * ($index + 1)),
                ]
            );
        }
    }

    private function faqs(Site $site): void
    {
        $categories = [
            'pemesanan' => 'Pemesanan',
            'pembayaran' => 'Pembayaran & Pengiriman',
        ];

        $catIds = [];
        $i = 0;
        foreach ($categories as $slug => $name) {
            $catIds[$slug] = FaqCategory::updateOrCreate(
                ['site_id' => $site->id, 'slug' => $slug],
                ['name' => $name, 'order' => $i++]
            )->id;
        }

        $faqs = [
            ['pemesanan', 'Berapa minimal order?', 'Untuk kaos minimal 6 pcs. Seragam dan jaket minimal 12 pcs. Jumlah lebih sedikit bisa didiskusikan.'],
            ['pemesanan', 'Berapa lama produksinya?', 'Kaos 5–7 hari kerja, seragam 2–3 minggu, jaket 2–4 minggu setelah desain dan ukuran dikunci.'],
            ['pemesanan', 'Apakah bisa minta contoh bahan?', 'Bisa. Datang ke workshop atau minta sample dikirim (biaya kirim ditanggung pemesan).'],
            ['pembayaran', 'Bagaimana sistem pembayaran?', 'DP 50% saat pesanan masuk, pelunasan sebelum pengiriman.'],
            ['pembayaran', 'Apakah bisa kirim ke luar kota?', 'Bisa. Pengiriman menggunakan ekspedisi pilihan Anda, ongkir dihitung terpisah.'],
            ['pembayaran', 'Apakah ada garansi?', 'Ada. Kerusakan jahitan atau sablon akibat produksi kami diperbaiki tanpa biaya.'],
        ];

        foreach ($faqs as $index => [$cat, $question, $answer]) {
            Faq::updateOrCreate(
                ['site_id' => $site->id, 'question' => $question],
                [
                    'faq_category_id' => $catIds[$cat],
                    'answer' => $answer,
                    'order' => $index,
                    'is_active' => true,
                ]
            );
        }
    }

    private function testimonials(Site $site): void
    {
        $items = [
            ['name' => 'Ibu Rina', 'title' => 'Guru SMK Karanganyar', 'message' => 'Seragam siswa rapi dan selesai sebelum tahun ajaran dimulai. Ukuran sesuai daftar, tidak ada yang meleset.', 'rating' => 5],
            ['name' => 'Bagas', 'title' => 'Ketua Komunitas Sepeda', 'message' => 'Kaos komunitas kami sablonnya tajam dan tidak luntur setelah beberapa kali cuci.', 'rating' => 5],
            ['name' => 'Pak Yusuf', 'title' => 'HRD Perusahaan Manufaktur', 'message' => 'Jahitan seragam kerja tim produksi kuat. Komunikasi enak, revisi logo cepat.', 'rating' => 4],
            ['name' => 'Dewi', 'title' => 'Panitia Reuni Angkatan', 'message' => 'Pesanan mendadak H-10 tetap selesai tepat waktu. Terima kasih Miset.', 'rating' => 5],
        ];

        foreach ($items as $index => $data) {
            Testimonial::updateOrCreate(
                ['site_id' => $site->id, 'name' => $data['name']],
                [
                    'title' => $data['title'],
                    'message' => $data['message'],
                    'rating' => $data['rating'],
                    'order' => $index,
                    'is_active' => true,
                ]
            );
        }
    }

    /**
     * @param  array<string, ServiceType>  $types
     */
    private function portfolios(Site $site, array $types): void
    {
        $items = [
            ['title' => 'Seragam SMP Nusantara', 'type' => 'seragam', 'client' => 'SMP Nusantara', 'description' => '240 set seragam sekolah dengan bordir logo dan label nama.'],
            ['title' => 'Kaos Komunitas Gowes Karanganyar', 'type' => 'kaos-apparel', 'client' => 'Komunitas Gowes', 'description' => '120 pcs kaos cotton combed 24s dengan sablon dua sisi.'],
            ['title' => 'Jaket Hoodie Tim Produksi', 'type' => 'kaos-apparel', 'client' => 'PT Sumber Makmur', 'description' => '80 pcs hoodie fleece dengan bordir logo perusahaan.'],
            ['title' => 'Totebag Kafe Jambangan', 'type' => 'merchandise', 'client' => 'Kafe Jambangan', 'description' => '150 pcs totebag kanvas dengan sablon satu warna.'],
        ];

        foreach ($items as $index => $data) {
            Portfolio::updateOrCreate(
                ['site_id' => $site->id, 'slug' => str($data['title'])->slug()],
                [
                    'service_type_id' => $types[$data['type']]->id,
                    'title' => $data['title'],
                    'client_name' => $data['client'],
                    'completion_date' => now()->subMonths($index + 1)->toDateString(),
                    'description' => $data['description'],
                    'order' => $index,
                    'is_featured' => $index < 2,
                ]
            );
        }
    }

    private function branches(Site $site): void
    {
        $items = [
            ['name' => 'Workshop Jambangan', 'address' => 'Jl. Jambangan - Grompol Km. 1, Pereng, Mojogedang, Karanganyar', 'phone' => '0812-3456-7890', 'is_primary' => true],
            ['name' => 'Showroom Karanganyar', 'address' => 'Jl. Lawu No. 12, Karanganyar', 'phone' => '0812-3456-7891', 'is_primary' => false],
        ];

        foreach ($items as $index => $data) {
            Branch::updateOrCreate(
                ['site_id' => $site->id, 'name' => $data['name']],
                [
                    'address' => $data['address'],
                    'phone' => $data['phone'],
                    'is_primary' => $data['is_primary'],
                    'order' => $index,
                ]
            );
        }
    }
}
