<?php

namespace App\Console\Commands;

use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\Branch;
use App\Models\BusinessUnit;
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
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Imports the real KSP Mitra Sejati Persada content from the legacy
 * kspmitrasejatipersada.my.id Vite bundle into the ksp tenant.
 *
 * shortcut: content is hardcoded (scraped once from the legacy bundle), upgrade
 * to a real 2-way sync only if the legacy site keeps being maintained.
 */
class ImportKspContent extends Command
{
    protected $signature = 'cms:import-ksp {--keep-extra : Jangan hapus konten dummy KSP (portofolio, testimoni, partner, artikel, FAQ)}';

    protected $description = 'Impor konten asli KSP Mitra Sejati Persada (dari situs lama) ke tenant ksp';

    private const SOURCE = 'https://kspmitrasejatipersada.my.id/assets/';

    public function handle(): int
    {
        $site = Site::where('slug', 'ksp')->first();

        if (! $site) {
            $this->error('Site "ksp" tidak ditemukan. Jalankan seeder dulu.');

            return self::FAILURE;
        }

        $this->importSettings($site);
        $this->importServices($site);
        $this->importBranches($site);
        $this->importBusinessUnits($site);
        $this->importPages($site);

        if (! $this->option('keep-extra')) {
            $this->purgeDummy($site);
        }

        $this->info('Selesai. Konten KSP sudah memakai data situs lama.');

        return self::SUCCESS;
    }

    private function importSettings(Site $site): void
    {
        $logo = $this->download('logoksp-ZGon1IKw.webp', 'branding/ksp-logo.webp');

        $settings = [
            ['general', 'site_title', 'KSP Mitra Sejati Persada'],
            ['general', 'tagline', 'Solusi Kemitraan Anda'],
            ['general', 'brand_color', '#1B7543'],
            ['general', 'meta_keywords', 'ksp mitra sejati persada, simpan pinjam, koperasi karanganyar, simpanan, pinjaman'],
            ['general', 'logo_path', $logo],
            ['contact', 'phone', '(0271)-6493247'],
            ['contact', 'whatsapp', '6282247170756'],
            ['contact', 'email', 'ksumitrasejati2005@gmail.com'],
            ['contact', 'address', 'Jl. Batujamus-Karanganyar Km.01, Plosorejo RT.02 RW.02, Kuto, Kerjo, Karanganyar'],
            ['contact', 'maps_embed', 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d1045.89633758876!2d111.04970945900324!3d-7.544823098299865!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e7a1e59b0e59853%3A0x89fcb614126ddb31!2sKSU%20Mitra%20Sejati!5e0!3m2!1sid!2sid!4v1780455411004!5m2!1sid!2sid'],
            ['social', 'instagram', 'https://instagram.com/kspmspersada'],
            ['legal', 'badan_hukum', 'AHU-0003640.AH.38.TAHUN 2025'],
            ['legal', 'copyright', '© 2026 KSP Mitra Sejati Persada. Seluruh hak cipta dilindungi.'],
        ];

        foreach ($settings as [$group, $key, $value]) {
            SiteSetting::updateOrCreate(
                ['site_id' => $site->id, 'key' => $key],
                ['group' => $group, 'value' => $value]
            );
        }

        $this->line('  settings: ' . count($settings));
    }

    private function importServices(Site $site): void
    {
        $types = [
            [
                'name' => 'Simpanan',
                'slug' => 'simpanan',
                'icon' => 'wallet',
                'description' => 'Beragam produk simpanan dengan bunga menarik, bebas administrasi, dan undian berhadiah.',
                'services' => [
                    [
                        'name' => 'Simpanan Pendidikan',
                        'slug' => 'simpanan-pendidikan',
                        'short_desc' => 'Simpanan untuk biaya pendidikan putra/putri anggota.',
                        'features' => [
                            'Simpanan dilokasikan untuk pendidikan putra / putri anda dan mempermudah memanage biaya pendidikan',
                            'Pembukaan rekening dengan setoran awal minimal Rp. 10.000,- dan selanjutnya minimal Rp. 5.000',
                            'Pengambilan simpanan saldo minimal yang harus disisihkan Rp. 10.000',
                            'Pengambilan atas rekomendasi Orang tua / Wali',
                        ],
                    ],
                    [
                        'name' => 'Simpanan Pendidikan Berjangka',
                        'slug' => 'simpanan-pendidikan-berjangka',
                        'short_desc' => 'Simpanan pendidikan yang hanya dapat diambil pada jangka waktu tertentu.',
                        'features' => [
                            'Simpanan dilokasikan untuk biaya pendidikan dan hanya dapat diambil pada jangka waktu tertentu',
                            'Pembukaan rekening dengan setoran awal minimal Rp 50.000,- dan setoran selanjutnya tetap',
                            'Setoran setiap bulan sekali',
                            'Jangka waktu bisa memilih & bunga menarik',
                        ],
                    ],
                    [
                        'name' => 'Simpanan Terpimpin',
                        'slug' => 'simpanan-terpimpin',
                        'short_desc' => 'Simpanan yang dapat disetor dan diambil sewaktu-waktu.',
                        'features' => [
                            'Simpanan yang dapat disetor/diambil sewaktu-waktu',
                            'Pembukaan rekening dengan setoran awal minimal Rp. 10.000,- dan selanjutnya minimal Rp. 5.000',
                            'Bunga/Jasa menarik, bebas pajak & administrasi',
                            'Bisa diambil/diantar staf marketing kami',
                            'Undian berhadiah menarik disetiap tahunnya',
                        ],
                    ],
                    [
                        'name' => 'Simpanan Mitra',
                        'slug' => 'simpanan-mitra',
                        'short_desc' => 'Simpanan dengan sistem poin dan undian berhadiah setiap 4 bulan.',
                        'features' => [
                            'Simpanan yang dapat disetor/diambil sewaktu-waktu',
                            'Pembukaan rekening dengan setoran awal minimal Rp 50.000,- dan selanjutnya minimal Rp 50.000,-',
                            'Sistem point untuk saldo tertentu semakin besar saldo semakin besar point yang didapat',
                            'Undian point berhadiah menarik setiap 4 bulan',
                            'Pengambilan simpanan saldo minimal yang harus disisihkan Rp. 50.000,-',
                        ],
                    ],
                    [
                        'name' => 'Simpanan Berjangka',
                        'slug' => 'simpanan-berjangka',
                        'short_desc' => 'Simpanan dengan jangka waktu tertentu, minimal Rp 1.000.000,-.',
                        'features' => [
                            'Simpanan dengan jangka waktu tertentu',
                            'Jumlah minimal Rp. 1.000.000,- (Satu Juta Rupiah)',
                            'Suku bunga/jasa dapat berubah sewaktu-waktu',
                        ],
                    ],
                ],
            ],
            [
                'name' => 'Pinjaman (Pembiayaan)',
                'slug' => 'pinjaman',
                'icon' => 'hand-coins',
                'description' => 'Kami melayani pinjaman (pembiayaan) dengan proses mudah dan aman. Nasabah mengajukan permohonan pinjaman dengan membawa berkas: foto copy KTP suami istri, Kartu Keluarga, surat/akta nikah, dan foto copy jaminan.',
                'services' => [
                    [
                        'name' => 'Pinjaman Flat (Tetap)',
                        'slug' => 'pinjaman-flat',
                        'short_desc' => 'Pinjaman dengan angsuran tetap, proses mudah dan aman.',
                        'features' => [
                            'Kami melayani pinjaman (pembiayaan) dengan proses mudah dan aman',
                            'Syarat: foto copy KTP suami istri, Kartu Keluarga, Surat/akta nikah, dan foto copy Jaminan',
                        ],
                    ],
                    [
                        'name' => 'Pinjaman Menurun',
                        'slug' => 'pinjaman-menurun',
                        'short_desc' => 'Pinjaman dengan sistem angsuran yang semakin menurun.',
                        'features' => [
                            'Pinjaman dengan sistem angsuran yang semakin menurun',
                            'Syarat: foto copy KTP suami istri, Kartu Keluarga, Surat/akta nikah, dan foto copy Jaminan',
                        ],
                    ],
                ],
            ],
        ];

        $serviceOrder = 0;
        $importedSlugs = [];

        foreach ($types as $typeIndex => $typeData) {
            $type = ServiceType::updateOrCreate(
                ['site_id' => $site->id, 'slug' => $typeData['slug']],
                [
                    'name' => $typeData['name'],
                    'description' => $typeData['description'],
                    'icon' => $typeData['icon'],
                    'order' => $typeIndex,
                ]
            );

            foreach ($typeData['services'] as $serviceData) {
                Service::updateOrCreate(
                    ['site_id' => $site->id, 'slug' => $serviceData['slug']],
                    [
                        'service_type_id' => $type->id,
                        'name' => $serviceData['name'],
                        'short_desc' => $serviceData['short_desc'],
                        'body' => implode("\n", $serviceData['features']),
                        'features' => $serviceData['features'],
                        'is_featured' => true,
                        'is_active' => true,
                        'order' => $serviceOrder++,
                    ]
                );

                $importedSlugs[] = $serviceData['slug'];
            }
        }

        // buang layanan & tipe layanan lama yang bukan bagian dari data asli KSP
        Service::where('site_id', $site->id)->whereNotIn('slug', $importedSlugs)->delete();
        ServiceType::where('site_id', $site->id)
            ->whereNotIn('slug', array_map(fn ($t) => $t['slug'], $types))
            ->delete();

        $this->line('  service types: ' . count($types) . ', services: ' . $serviceOrder);
    }

    private function importBranches(Site $site): void
    {
        $branches = [
            ['Kantor Pusat', 'Jl. Batujamus-Karanganyar Km.01, Plosorejo RT.02 RW.02, Kuto, Kerjo, Karanganyar', '(0271)-6493247', true, ['kantorpusat-C3OBD9Ew.webp', 'kaspusat-BljTTr5Y.webp']],
            ['Kantor Cabang Kerjo', 'Jl. Batujamus-Karanganyar Km.01, Bangunsari Rt.07 Rw.01, Kuto, Kerjo, Karanganyar (Depan Pasar Kwadungan)', '(0271)-6493168', false, ['kantorkerjo-CuWzVVyu.webp']],
            ['Kantor Cabang Mojogedang', 'Jl. Mojogedang-Karangpandan Km.01, Mojogedang Rt.01 Rw.01, Mojogedang, Karanganyar (Timur Pasar Mojogedang)', '(0271)-4990402', false, ['kantormjgd-JPHXHYmm.webp', 'kasmjgd-BHzvLA09.webp']],
            ['Kantor Cabang Jambangan', 'Jl. Jambangan-Grompol Km.01, Jambangan Rt.13 Rw.01, Pereng, Mojogedang, Karanganyar (Barat Pasar Jambangan ±500m)', '(0271)-7881239', false, ['kantorjmbg-D7Ht9g-H.webp', 'kasjmbg-CbIc4fd9.webp']],
            ['Kantor Cabang Tasikmadu', 'Jl. Tasikmadu-Kebakkramat Km.01, Nlano Rt.01 Rw.01, Pandeyan, Tasikmadu, Karanganyar (Utara PG Tasikmadu)', '(0271)-6496833', false, ['kantortskmd-DvTMh8cM.webp']],
            ['Kantor Cabang Matesih', 'Jl. Matesih-Karanganyar Km.01 Sidodadi Rt.01 Rw.08, Matesih, Karanganyar', '(0271)-6494005', false, ['kantormtsh-C3L6F-0-.webp', 'kasmtsh-C9TIkUri.webp']],
            ['Kantor Cabang Jumapolo', 'Jl. Raya Jumapolo-Karanganyar Km.01, Jumapolo, Karanganyar (Depan Pasar/Terminal Jumapolo)', '(0271)-494883', false, ['kantorjmpl-CjW4fN1X.webp', 'kasjmpl-ChBYo56e.webp']],
            // shortcut: address & phone Jatipuro sama dengan Jumapolo di sumber (duplikat), perbaiki dari dashboard admin
            ['Kantor Cabang Jatipuro', 'Jl. Raya Jumapolo-Karanganyar Km.01, Jumapolo, Karanganyar (Depan Pasar/Terminal Jumapolo)', '(0271)-494883', false, ['kantorjtpr-u5xZB4Ui.webp', 'kasjtpr-BV0bywxe.webp']],
        ];

        foreach ($branches as $index => [$name, $address, $phone, $isPrimary, $files]) {
            $images = array_values(array_filter(array_map(
                fn ($file) => $this->download($file, "branches/ksp/{$file}"),
                $files
            )));

            Branch::updateOrCreate(
                ['site_id' => $site->id, 'name' => $name],
                [
                    'address' => $address,
                    'phone' => $phone,
                    'is_primary' => $isPrimary,
                    'images' => $images,
                    'order' => $index,
                ]
            );
        }

        $this->line('  branches: ' . count($branches));
    }

    private function importBusinessUnits(Site $site): void
    {
        // nama unit, alamat, telepon, gambar (cover + galeri), slug site tujuan redirect
        $units = [
            ['Mitra Sejati Trans', 'Jl. Batujamus - Jambangan Km. 2, Gempol, Mojodoyong, Kedawung, Sragen', '081329703060', ['mst-Cpk5JyTC.jpeg', 'mst1-DoVY92gx.jpg', 'mst2-CSxZoRYj.jpg'], 'transportasi'],
            ['Mitra Sejati Grosir (MSG)', 'Jl. Batujamus - Jambangan Km. 1 Kutho, Kerjo, Karanganyar', null, ['msg-CLmEP2zB.jpeg', 'msg1-Dxxn3zbu.jpeg', 'msg2-v2CY4C35.jpeg'], null],
            ['Mitra Sejati Percetakan', 'Jl. Jambangan - Grompol Km. 1, Jambangan, Pereng, Mojogedang, Karanganyar', '081235102885', ['mspercetakan-BXKGIbc0.jpeg', 'mspercetakan1-DwkpTzA7.jpeg', 'mspercetakan2-DyF_q54j.jpeg'], 'percetakan'],
            ['Mitra Estetika', 'Jl. Jambangan-Grompol Km.01, Jambangan Rt.13 Rw.01, Pereng, Mojogedang, Karanganyar (Barat Pasar Jambangan ±500m)', '087252750028', ['estetika-BBgZl801.jpeg'], 'bengkel-las'],
            ['Mitra Sejati Digital Solutions', 'Jl. Batujamus-Karanganyar Km.01, Plosorejo RT.02 RW.02, Kuto, Kerjo, Karanganyar', null, ['msds-D9TogTl0.jpeg', 'msds1-DNHoXhYM.png', 'msds2-BifHIE69.png'], 'teknologi'],
            ['Mitra Sejati Konveksi (MISET)', 'Jl. Jambangan - Grompol Km. 1, Jambangan, Pereng, Mojogedang, Karanganyar', null, ['miset-xANgJkb2.jpeg', 'miset1-CNsmju2x.jpeg', 'miset2-DEsX7yJ1.jpeg'], 'konveksi'],
        ];

        foreach ($units as $index => [$name, $address, $phone, $files, $targetSlug]) {
            // tautan redirect ke landing page unit terkait (null kalau unit belum punya site)
            $targetSite = $targetSlug ? Site::where('slug', $targetSlug)->first() : null;
            $url = $targetSite && $targetSite->domain ? 'https://' . $targetSite->domain : null;

            $images = array_values(array_filter(array_map(
                fn ($file) => $this->download($file, "business-units/ksp/{$file}"),
                $files
            )));

            BusinessUnit::updateOrCreate(
                ['site_id' => $site->id, 'name' => $name],
                [
                    'address' => $address . ($phone ? ' · ' . $phone : ''),
                    'url' => $url,
                    'image_path' => $images[0] ?? null,
                    'images' => $images,
                    'order' => $index,
                ]
            );
        }

        $this->line('  business units: ' . count($units));
    }

    private function importPages(Site $site): void
    {
        $hero = [
            [
                'title' => 'Solusi Kemitraan Anda',
                'description' => 'Menjadi ekosistem keuangan berbasis komunitas yang inovatif, inklusif, dan berkelanjutan untuk membentuk masa depan ekonomi anggota yang mandiri dan bermartabat.',
                'badge' => 'KSP Mitra Sejati Persada',
                'image_path' => $this->download('kantorpusat-C3OBD9Ew.webp', 'hero/ksp/slide-1.webp'),
            ],
            [
                'title' => 'Melayani Semua Lapisan, Menyatukan Perbedaan',
                'description' => 'Menyediakan layanan simpan pinjam yang mudah diakses, adil, dan disesuaikan dengan kebutuhan nyata setiap anggota dari berbagai latar belakang.',
                'badge' => 'Simpan & Pinjam',
                'image_path' => $this->download('umkm1-Cq7GjU3C.webp', 'hero/ksp/slide-2.webp'),
            ],
            [
                'title' => 'Menjaga Warisan, Mendorong Inovasi',
                'description' => 'Menggabungkan semangat gotong royong dan integritas dengan pendekatan modern berbasis teknologi agar tetap relevan dan efisien.',
                'badge' => 'Berdiri Sejak 2006',
                'image_path' => $this->download('umkm2-Cer6z59F.webp', 'hero/ksp/slide-3.webp'),
            ],
        ];

        $pages = [
            'home' => [
                'title' => 'Beranda',
                'sections' => [
                    ['hero', 'Solusi Kemitraan Anda', null, [
                        'slides' => $hero,
                        'stats' => [
                            ['label' => 'Tahun Berdiri', 'value' => '2006'],
                            ['label' => 'Kantor Cabang', 'value' => '8+'],
                            ['label' => 'Produk Simpanan', 'value' => '5'],
                            ['label' => 'Wilayah', 'value' => 'Karanganyar'],
                        ],
                    ]],
                    ['featured', 'Produk Unggulan', 'Solusi simpan pinjam terpercaya untuk memenuhi kebutuhan finansial Anda dan keluarga', [
                        'badge' => 'Layanan Kami',
                        'cards' => [
                            [
                                'title' => 'Simpanan',
                                'description' => 'Beragam produk simpanan dengan bunga menarik, bebas administrasi, dan undian berhadiah.',
                                'products' => ['Simpanan Pendidikan', 'Simpanan Pendidikan Berjangka', 'Simpanan Terpimpin', 'Simpanan Mitra', 'Simpanan Berjangka'],
                            ],
                            [
                                'title' => 'Pinjaman',
                                'description' => 'Layanan pinjaman mudah, aman, dan transparan dengan syarat yang terjangkau untuk semua.',
                                'products' => ['Pinjaman Flat (Tetap)', 'Pinjaman Menurun'],
                                'note' => 'Koperasi Simpan Pinjam terpercaya untuk kebutuhan anggota.',
                            ],
                        ],
                        'cta_label' => 'Lihat Detail Semua Produk',
                    ]],
                    ['office', 'Alamat Kantor Pusat', 'Jl. Batujamus-Karanganyar Km.01, Plosorejo RT.02 RW.02, Kuto, Kerjo, Karanganyar', [
                        'badge' => 'Lokasi',
                        'maps_embed' => 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d1045.89633758876!2d111.04970945900324!3d-7.544823098299865!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e7a1e59b0e59853%3A0x89fcb614126ddb31!2sKSU%20Mitra%20Sejati!5e0!3m2!1sid!2sid!4v1780455411004!5m2!1sid!2sid',
                    ]],
                ],
            ],
            'profil' => [
                'title' => 'Profil',
                'sections' => [
                    ['hero', 'Profil KSP Mitra Sejati Persada', 'Mengenal lebih dekat Koperasi Simpan Pinjam yang melayani masyarakat Karanganyar sejak 2006', [
                        'badge' => 'Tentang Kami',
                        'image_path' => $this->download('kantorpusat-C3OBD9Ew.webp', 'hero/ksp/profil.webp'),
                        'image_label' => 'Kantor Pusat',
                        'image_caption' => 'Jl. Batujamus-Karanganyar Km.01, Plosorejo, Kuto, Kerjo, Karanganyar',
                    ]],
                    ['sejarah', 'Sejarah Kami', implode("\n\n", [
                        'KSP Mitra Sejati Persada lahir dari semangat untuk bangkit dari masa sulit. Berawal dari sisa puing-puing keputusasaan kelompok lama, lima kelompok melebur menjadi satu dengan tekad yang sama memperbaiki keadaan dan membangun lembaga yang lebih kuat, sehat, serta bermanfaat bagi anggota dan masyarakat.',
                        'Tanggal 21 Maret 2005 menjadi awal perjalanan KSU Mitra Sejati. Dengan modal awal sebesar Rp18.000.000, sebagian dana digunakan untuk kebutuhan operasional seperti sewa kantor, peralatan kantor, dan seragam karyawan. Dari sisa kas sebesar Rp3.000.000, koperasi ini mulai membuka pelayanan pertamanya. Kantor pertama berdiri di depan Pasar Batujamus, yang kini dikenal sebagai Kantor Kas Kerjo. Dari tempat sederhana inilah kepercayaan mulai dibangun dan pelayanan terus berkembang.',
                        'Pada tahun 2006, KSU Mitra Sejati memperoleh badan hukum awal dengan Nomor 180/518/44 Tahun 2006. Seiring perkembangan kelembagaan, KSU Mitra Sejati kemudian bertransformasi menjadi KSP Mitra Sejati Persada dengan nomor badan hukum AHU-0003640.AH.38.TAHUN 2025. Dengan slogan Solusi Kemitraan Anda, KSP Mitra Sejati Persada terus berkomitmen menjadi mitra terpercaya yang tumbuh bersama anggota, karyawan, dan masyarakat dalam semangat kemitraan sejati.',
                    ]), null],
                    ['visi-misi', 'Visi & Misi', null, [
                        'visi' => 'Menjadi ekosistem keuangan berbasis komunitas yang inovatif, inklusif, dan berkelanjutan untuk membentuk masa depan ekonomi anggota yang mandiri dan bermartabat.',
                        'misi' => [
                            ['title' => 'Melayani Semua Lapisan, Menyatukan Perbedaan', 'desc' => 'Menyediakan layanan simpan pinjam yang mudah diakses, adil, dan disesuaikan dengan kebutuhan nyata setiap anggota dari berbagai latar belakang.'],
                            ['title' => 'Tumbuh Bersama, Bukan Sendiri', 'desc' => 'Menjadikan koperasi bukan hanya tempat transaksi, tetapi ruang kolaborasi ekonomi, saling bantu, dan menginspirasi antar anggota.'],
                            ['title' => 'Menjaga Warisan, Mendorong Inovasi', 'desc' => 'Menggabungkan semangat gotong royong dan integritas dengan pendekatan modern berbasis teknologi agar tetap relevan dan efisien.'],
                            ['title' => 'Transparansi adalah Pondasi', 'desc' => 'Menumbuhkan budaya koperasi yang terbuka, akuntabel, dan bisa dipercaya oleh anggota maupun mitra luar.'],
                            ['title' => 'Koperasi Sebagai Rumah Ekonomi Rakyat', 'desc' => 'Menguatkan peran koperasi sebagai pusat pengembangan ekonomi lokal, pendidikan keuangan, dan wadah pemberdayaan berkelanjutan.'],
                        ],
                    ]],
                    ['strategi', 'Strategi', null, [
                        'items' => [
                            ['title' => 'Melayani Semua Lapisan, Menyatukan Perbedaan', 'desc1' => 'Mengembangkan produk simpan pinjam yang fleksibel dan sesuai segmen (petani, pedagang, ASN, UMKM, dll).', 'desc2' => 'Menyediakan layanan jemput bola atau digital bagi anggota yang sulit mengakses kantor.'],
                            ['title' => 'Tumbuh Bersama, Bukan Sendiri', 'desc1' => 'Mendorong kolaborasi usaha antar anggota.', 'desc2' => 'Membuka forum diskusi atau pelatihan berkala antar anggota.'],
                            ['title' => 'Menjaga Warisan, Mendorong Inovasi', 'desc1' => 'Menjaga nilai-nilai koperasi seperti kejujuran, gotong royong, dan tanggung jawab.', 'desc2' => 'Meningkatkan sistem digital dan manajemen koperasi.'],
                            ['title' => 'Transparansi adalah Pondasi', 'desc1' => 'Menyampaikan laporan secara rutin dan terbuka kepada anggota.', 'desc2' => 'Meningkatkan peran pengawasan internal koperasi.'],
                            ['title' => 'Koperasi Sebagai Rumah Ekonomi Rakyat', 'desc1' => 'Membina dan mendampingi anggota dalam pengembangan usaha.', 'desc2' => 'Menjadi penghubung koperasi dengan lembaga pelatihan, pemerintah, dan komunitas.'],
                        ],
                    ]],
                    ['program', 'Program Kerja', null, [
                        'items' => [
                            ['title' => 'Melayani Semua Lapisan, Menyatukan Perbedaan', 'desc1' => "Program 'Pinjaman Tepat Guna' untuk anggota usaha mikro dan rumah tangga.", 'desc2' => 'Sosialisasi koperasi di komunitas lokal seperti RT/RW, pasar, dan kelompok tani.'],
                            ['title' => 'Tumbuh Bersama, Bukan Sendiri', 'desc1' => "Kelas bulanan 'Ngopi Bareng Mitra' untuk berbagi pengetahuan usaha dan motivasi.", 'desc2' => "Program 'Pasar Mitra' untuk promosi usaha anggota melalui media koperasi atau event bersama."],
                            ['title' => 'Menjaga Warisan, Mendorong Inovasi', 'desc1' => 'Digitalisasi pencatatan keuangan dan layanan melalui aplikasi koperasi.', 'desc2' => 'Pelatihan nilai-nilai koperasi bagi anggota baru dan pengurus.'],
                            ['title' => 'Transparansi adalah Pondasi', 'desc1' => 'Rapat Anggota Tahunan (RAT) interaktif dengan visualisasi data keuangan.', 'desc2' => 'Laporan bulanan melalui WhatsApp Group atau papan informasi koperasi.'],
                            ['title' => 'Koperasi Sebagai Rumah Ekonomi Rakyat', 'desc1' => "Program 'Sahabat Usaha' untuk pendampingan usaha kecil anggota.", 'desc2' => 'Kerja sama dengan dinas koperasi, UMKM, dan lembaga pelatihan lokal.'],
                        ],
                    ]],
                ],
            ],
            'produk' => [
                'title' => 'Produk',
                'sections' => [
                    ['hero', 'Produk Simpanan & Pinjaman', 'Beragam produk simpanan dan pinjaman untuk memenuhi kebutuhan finansial Anda dan keluarga', [
                        'slides' => [
                            [
                                'title' => 'Tumbuh Bersama, Bukan Sendiri',
                                'description' => 'Menjadikan koperasi bukan hanya tempat transaksi, tetapi ruang kolaborasi ekonomi, saling bantu, dan menginspirasi antar anggota.',
                                'image_path' => $this->download('umkm3-BZwoJ7br.webp', 'hero/ksp/produk-1.webp'),
                            ],
                            [
                                'title' => 'Transparansi adalah Pondasi',
                                'description' => 'Menumbuhkan budaya koperasi yang terbuka, akuntabel, dan bisa dipercaya oleh anggota maupun mitra luar.',
                                'image_path' => $this->download('umkm4-CLTTzE4p.webp', 'hero/ksp/produk-2.webp'),
                            ],
                            [
                                'title' => 'Koperasi Sebagai Rumah Ekonomi Rakyat',
                                'description' => 'Menguatkan peran koperasi sebagai pusat pengembangan ekonomi lokal, pendidikan keuangan, dan wadah pemberdayaan berkelanjutan.',
                                'image_path' => $this->download('umkm5-De2JDTPB.webp', 'hero/ksp/produk-3.webp'),
                            ],
                        ],
                        'cta_label' => 'Hubungi Admin via WhatsApp',
                    ]],
                    ['cta', 'Butuh Informasi Lebih Lanjut?', 'Tim kami siap membantu Anda memilih produk yang paling sesuai dengan kebutuhan', [
                        'button_label' => 'Hubungi Admin via WhatsApp',
                        'button_url' => 'https://wa.me/6282247170756?text=Halo%20Admin%20KSP%20Mitra%20Sejati%20Persada,%20saya%20ingin%20mendapatkan%20informasi%20lebih%20lanjut%20tentang%20produk',
                    ]],
                ],
            ],
            'cabang' => [
                'title' => 'Cabang',
                'sections' => [
                    ['hero', 'Cabang Kami', 'Temukan kantor cabang KSP Mitra Sejati Persada yang terdekat dengan Anda di seluruh Karanganyar', [
                        'stats' => [
                            ['label' => 'Kantor', 'value' => '8'],
                            ['label' => 'Kantor Pusat', 'value' => '1'],
                            ['label' => 'Kantor Cabang', 'value' => '7'],
                        ],
                    ]],
                ],
            ],
            'unit-usaha' => [
                'title' => 'Unit Usaha',
                'sections' => [
                    ['hero', 'Unit Usaha Kami', 'Unit usaha KSP Mitra Sejati Persada yang melayani kebutuhan anggota dan masyarakat', null],
                ],
            ],
            'karir' => [
                'title' => 'Karir',
                'sections' => [
                    ['hero', 'Karir di KSP Mitra Sejati Persada', 'Jadilah bagian dari keluarga besar KSP Mitra Sejati Persada dan tumbuh bersama kami', [
                        'image_path' => $this->download('loker2-7Q6hCG2d.webp', 'career/ksp/loker.webp'),
                    ]],
                    ['values', 'Mengapa Bergabung Bersama Kami?', null, [
                        'items' => [
                            ['title' => 'Tim yang Solid', 'description' => 'Bergabung dengan tim profesional yang berdedikasi dan penuh semangat gotong royong.'],
                            ['title' => 'Lingkungan Berkembang', 'description' => 'Kami mendorong setiap karyawan untuk terus tumbuh dan meningkatkan kualitas diri.'],
                            ['title' => 'Notifikasi Lowongan', 'description' => 'Pantau terus halaman ini atau ikuti Instagram kami untuk mendapatkan info lowongan terbaru.'],
                        ],
                        'openings' => [],
                    ]],
                ],
            ],
            'berita' => [
                'title' => 'Berita',
                'sections' => [
                    ['hero', 'Artikel & Berita', 'Informasi dan kegiatan terbaru KSP Mitra Sejati Persada melalui Instagram resmi kami', null],
                    ['instagram', 'Konten dari Instagram resmi kami', 'Menampilkan 6 postingan terbaru dari akun @kspmspersada', [
                        'handle' => 'kspmspersada',
                        'profile_url' => 'https://www.instagram.com/kspmspersada',
                        'posts' => [
                            'https://www.instagram.com/reel/DM6qmQPS9zB/',
                            'https://www.instagram.com/reel/DNNDS46SumO/',
                            'https://www.instagram.com/reel/DIvCs97yNOZ/',
                            'https://www.instagram.com/p/DIabEjlSsUO/',
                            'https://www.instagram.com/reel/DISqojFyDRy/',
                            'https://www.instagram.com/p/DHcqmmPyBeK/',
                        ],
                    ]],
                ],
            ],
            'kontak' => [
                'title' => 'Kontak',
                'sections' => [
                    ['hero', 'Hubungi Kami', 'Sampaikan kebutuhan Anda, tim kami akan menghubungi kembali', null],
                ],
            ],
        ];

        $metaTitles = ['cabang' => 'Cabang Kami', 'unit-usaha' => 'Unit Usaha', 'berita' => 'Artikel & Berita'];

        foreach ($pages as $slug => $pageData) {
            $page = Page::updateOrCreate(
                ['site_id' => $site->id, 'slug' => $slug],
                [
                    'title' => $pageData['title'],
                    'meta_title' => ($metaTitles[$slug] ?? $pageData['title']) . ' | KSP Mitra Sejati Persada',
                    'meta_desc' => Str::limit(strip_tags($pageData['sections'][0][2] ?? $pageData['sections'][0][1]), 155),
                    'is_published' => true,
                ]
            );

            foreach ($pageData['sections'] as $index => [$key, $title, $body, $payload]) {
                $page->sections()->updateOrCreate(
                    ['key' => $key],
                    [
                        'title' => $title,
                        'body' => $body,
                        'payload' => $this->withImageUrls($payload),
                        'order' => $index,
                    ]
                );
            }

            // buang section lama yang sudah tidak ada di sumber
            $page->sections()->whereNotIn('key', array_column($pageData['sections'], 0))->delete();
        }

        $this->line('  pages: ' . count($pages));
    }

    /**
     * payload gambar disimpan sebagai path relatif; tambahkan image_url absolut agar frontend tidak menyusun URL sendiri.
     */
    private function withImageUrls(?array $payload): ?array
    {
        if (!is_array($payload)) {
            return $payload;
        }

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

    /**
     * Removes the generic demo content seeded for KSP (the real site has none of these).
     */    private function purgeDummy(Site $site): void
    {
        $counts = [
            'portfolios' => Portfolio::where('site_id', $site->id)->delete(),
            'testimonials' => Testimonial::where('site_id', $site->id)->delete(),
            'partners' => Partner::where('site_id', $site->id)->delete(),
            'articles' => Article::where('site_id', $site->id)->delete(),
            'article_categories' => ArticleCategory::where('site_id', $site->id)->delete(),
            'faqs' => Faq::where('site_id', $site->id)->delete(),
            'faq_categories' => FaqCategory::where('site_id', $site->id)->delete(),
        ];

        $this->line('  dummy dihapus: ' . collect($counts)->filter()->map(fn ($n, $k) => "$k=$n")->implode(', '));
    }

    private function download(string $file, string $targetPath): ?string
    {
        if (Storage::disk('public')->exists($targetPath)) {
            return $targetPath;
        }

        try {
            $response = Http::timeout(30)->get(self::SOURCE . $file);

            if (! $response->successful()) {
                $this->warn("  gagal unduh {$file} ({$response->status()})");

                return null;
            }

            Storage::disk('public')->put($targetPath, $response->body());

            return $targetPath;
        } catch (\Throwable $e) {
            $this->warn("  gagal unduh {$file}: {$e->getMessage()}");

            return null;
        }
    }
}
