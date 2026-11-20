<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\ArticleCategory;
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
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Create Superadmin User
        $admin = User::firstOrCreate(
            ['email' => 'admin@mitrasova.com'],
            [
                'name' => 'Super Administrator',
                'password' => Hash::make('password123'),
                'is_superadmin' => true,
            ]
        );

        // 2. The 6 Business Units
        $units = [
            [
                'slug' => 'ksp',
                'name' => 'KSP Mitra Sejati Persada',
                'api_key' => 'key-ksp-12345',
                'domain' => 'ksp.mitrasova.com',
                'tagline' => 'Solusi Simpan Pinjam Terpercaya untuk Kemajuan Bersama',
                'phone' => '+62 812-3456-7801',
                'email' => 'layanan@kspmitrasejati.com',
                'address' => 'Gedung Koperasi Mitra lt. 2, Jl. Sudirman Kav. 21, Jakarta Pusat',
                'service_types' => [
                    [
                        'name' => 'Simpanan Koperasi',
                        'slug' => 'simpanan',
                        'description' => 'Produk simpanan berjangka dan harian dengan bagi hasil kompetitif',
                        'services' => [
                            [
                                'name' => 'Simpanan Sukarela Sejahtera',
                                'slug' => 'simpanan-sukarela-sejahtera',
                                'short_desc' => 'Simpanan fleksibel dengan setoran awal ringan dan imbal jasa menarik.',
                                'body' => 'Simpanan Sukarela Sejahtera memberikan keleluasaan bagi anggota untuk menabung kapan saja. Dilengkapi fitur penarikan mudah dan laporan bulanan transparan.',
                                'features' => ['Setoran awal Rp 50.000', 'Bagi hasil hingga 7.5% per tahun', 'Bebas biaya administrasi bulanan', 'Bisa ditarik sewaktu-waktu'],
                                'is_featured' => true,
                            ],
                            [
                                'name' => 'Simpanan Berjangka Berkah',
                                'slug' => 'simpanan-berjangka-berkah',
                                'short_desc' => 'Investasi simpanan berjangka dengan bagi hasil optimal untuk jangka menengah.',
                                'body' => 'Simpanan berjangka dengan pilihan tenor 3, 6, dan 12 bulan. Cocok untuk merencanakan dana pendidikan atau modal pengembangan usaha.',
                                'features' => ['Pilihan tenor fleksibel', 'Imbal hasil hingga 9% per tahun', 'Sertifikat bilyet resmi', 'Dapat dijadikan jaminan pinjaman'],
                                'is_featured' => true,
                            ],
                        ],
                    ],
                    [
                        'name' => 'Pinjaman & Pembiayaan',
                        'slug' => 'pinjaman',
                        'description' => 'Fasilitas pinjaman modal usaha dan kebutuhan mendesak bagi anggota',
                        'services' => [
                            [
                                'name' => 'Pinjaman Modal Kerja UMKM',
                                'slug' => 'pinjaman-modal-kerja-umkm',
                                'short_desc' => 'Dukungan pembiayaan modal kerja cepat tanpa prosedur berbelit.',
                                'body' => 'Dukungan finansial bagi pelaku usaha mikro dan kecil untuk menambah persediaan barang dagangan, pembelian alat operasional, atau sewa tempat usaha.',
                                'features' => ['Plafond hingga Rp 100.000.000', 'Bunga kompetitif flat bulanan', 'Proses persetujuan 1-2 hari kerja', 'Syarat dokumen mudah'],
                                'is_featured' => true,
                            ],
                            [
                                'name' => 'Pinjaman Multiguna Sahabat',
                                'slug' => 'pinjaman-multiguna-sahabat',
                                'short_desc' => 'Solusi cepat untuk kebutuhan darurat, renovasi rumah, atau biaya sekolah.',
                                'body' => 'Fasilitas pinjaman multiguna dengan cicilan terjangkau dan jadwal pembayaran yang disesuaikan dengan kemampuan keuangan keluarga.',
                                'features' => ['Plafond hingga Rp 30.000.000', 'Tenor cicilan sampai 24 bulan', 'Tanpa potongan tersembunyi', 'Asuransi jiwa perlindungan peminjam'],
                                'is_featured' => false,
                            ],
                        ],
                    ],
                ],
                'faqs' => [
                    'Keanggotaan' => [
                        ['q' => 'Bagaimana syarat mendaftar anggota KSP Mitra Sejati Persada?', 'a' => 'Cukup membawa fotokopi KTP, KK, serta mengisi formulir pendaftaran dan membayar simpanan pokok serta wajib.'],
                        ['q' => 'Berapa besaran simpanan pokok dan wajib?', 'a' => 'Simpanan pokok sebesar Rp 100.000 (hanya sekali saat mendaftar) dan simpanan wajib Rp 25.000 setiap bulan.'],
                    ],
                    'Pinjaman' => [
                        ['q' => 'Berapa lama proses pencairan pinjaman?', 'a' => 'Setelah dokumen lengkap diverifikasi oleh tim analis, proses pencairan rata-rata memakan waktu 24 hingga 48 jam kerja.'],
                    ],
                ],
            ],
            [
                'slug' => 'konveksi',
                'name' => 'Mitrasova Konveksi & Garment',
                'api_key' => 'key-konveksi-12345',
                'domain' => 'konveksi.mitrasova.com',
                'tagline' => 'Jasa Jahit & Produksi Seragam, Kaos, dan Kemeja Skala Besar Berkualitas',
                'phone' => '+62 812-3456-7802',
                'email' => 'sales@mitrasovakonveksi.com',
                'address' => 'Kawasan Industri Tekstil No. 45, Bandung, Jawa Barat',
                'service_types' => [
                    [
                        'name' => 'Seragam Kerja & Perusahaan',
                        'slug' => 'seragam-kerja',
                        'description' => 'Produksi kemeja kerja kantor, seragam lapangan (wearpack), dan jaket korporat',
                        'services' => [
                            [
                                'name' => 'Kemeja PDH / PDL Kantor',
                                'slug' => 'kemeja-pdh-pdl-kantor',
                                'short_desc' => 'Kemeja kantor berstandar industri dengan bahan premium seperti American/Japan Drill.',
                                'body' => 'Jahit rapi dengan jahitan stik ganda, bordir komputer presisi tinggi, dan daya tahan warna kain yang optimal.',
                                'features' => ['Pilihan bahan American Drill, Tropical, Nagata', 'Bordir komputer resolusi tinggi', 'Custom ukuran dan fitting size', 'Minimal pemesanan 24 pcs'],
                                'is_featured' => true,
                            ],
                        ],
                    ],
                    [
                        'name' => 'Kaos & Polo Event',
                        'slug' => 'kaos-polo',
                        'description' => 'Produksi kaos katun combed dan polo shirt untuk seminar, gathering, dan komunitas',
                        'services' => [
                            [
                                'name' => 'Custom Kaos Cotton Combed',
                                'slug' => 'custom-kaos-cotton-combed',
                                'short_desc' => 'Kaos berkualitas tinggi berbahan 100% Cotton Combed 24s/30s adem dan nyaman.',
                                'body' => 'Didukung teknik sablon modern (plastisol, DTF, discharge) dengan hasil cetak warna tajam dan tidak mudah retak.',
                                'features' => ['Bahan 100% Cotton Combed 30s/24s', 'Sablon DTF / Plastisol tahan cuci', 'Pilihan 30+ varian warna', 'Kapasitas produksi hingga 10.000 pcs/bulan'],
                                'is_featured' => true,
                            ],
                        ],
                    ],
                ],
                'faqs' => [
                    'Pemesanan' => [
                        ['q' => 'Berapa minimal order quantity (MOQ)?', 'a' => 'Minimal pemesanan untuk kaos adalah 24 pcs, sedangkan seragam/kemeja adalah 20 pcs per desain.'],
                        ['q' => 'Apakah bisa membuat sampel (sample proofing) terlebih dahulu?', 'a' => 'Ya, kami menyediakan layanan pembuatan sampel sebelum produksi massal untuk order tertentu.'],
                    ],
                ],
            ],
            [
                'slug' => 'teknologi',
                'name' => 'Mitrasova Digital Solutions',
                'api_key' => 'key-teknologi-12345',
                'domain' => 'teknologi.mitrasova.com',
                'tagline' => 'Transformasi Digital Cerdas: Web, Mobile Apps, dan Sistem Enterprise',
                'phone' => '+62 812-3456-7803',
                'email' => 'hello@mitrasovatech.com',
                'address' => 'Cyber Park Tower Suite 808, TB Simatupang, Jakarta Selatan',
                'service_types' => [
                    [
                        'name' => 'Web & Mobile Development',
                        'slug' => 'web-mobile-development',
                        'description' => 'Pengembangan aplikasi berbasis web modern dan mobile Android/iOS',
                        'services' => [
                            [
                                'name' => 'Custom Web App & SaaS',
                                'slug' => 'custom-web-app-saas',
                                'short_desc' => 'Aplikasi web scalable dengan stack Next.js, Vue, Laravel, dan cloud-native architecture.',
                                'body' => 'Kami membangun aplikasi web berkinerja tinggi yang dirancang aman, teruji beban trafik tinggi, dan mudah dikembangkan di masa mendatang.',
                                'features' => ['Frontend Next.js / React / Vue', 'Backend Laravel / Node.js API', 'Cloud Deployment AWS / DigitalOcean', 'Automated CI/CD & Testing'],
                                'is_featured' => true,
                            ],
                            [
                                'name' => 'Mobile App Android & iOS',
                                'slug' => 'mobile-app-android-ios',
                                'short_desc' => 'Aplikasi seluler cross-platform Flutter/React Native dengan UX intuitif.',
                                'body' => 'Dari perancangan wireframe hingga publish ke Google Play Store dan Apple App Store, kami kelola siklus pengembangan secara menyeluruh.',
                                'features' => ['Cross-platform Flutter / React Native', 'Offline-first & Local Caching', 'Push Notifications & Payment Gateway', 'Support maintenance berkala'],
                                'is_featured' => true,
                            ],
                        ],
                    ],
                    [
                        'name' => 'ERP & CRM Solution',
                        'slug' => 'erp-crm',
                        'description' => 'Solusi sistem informasi manajemen rantai pasok, inventori, dan akuntansi perusahaan',
                        'services' => [
                            [
                                'name' => 'Enterprise Resource Planning (ERP)',
                                'slug' => 'enterprise-resource-planning',
                                'short_desc' => 'Integrasi modul keuangan, persediaan, pembelian, dan SDM dalam satu dasbor.',
                                'body' => 'Membantu pemilik bisnis dan manajemen memantau operasional perusahaan secara real-time dari satu sistem terintegrasi.',
                                'features' => ['Modul Accounting & Ledger', 'Inventory & Warehouse Barcode', 'Multi-cabang & Multi-mata uang', 'Role-based Access Control ketat'],
                                'is_featured' => true,
                            ],
                        ],
                    ],
                ],
                'faqs' => [
                    'Pengembangan' => [
                        ['q' => 'Berapa lama estimasi pengerjaan website atau aplikasi?', 'a' => 'Tergantung kompleksitas, landing page biasanya 1-2 minggu, aplikasi web custom 4-8 minggu, dan mobile app 8-16 minggu.'],
                        ['q' => 'Apakah source code dan database menjadi hak milik klien?', 'a' => 'Ya, seluruh source code, hak kekayaan intelektual (IP), dan basis data diserahkan 100% kepada klien.'],
                    ],
                ],
            ],
            [
                'slug' => 'percetakan',
                'name' => 'Mitrasova Printing',
                'api_key' => 'key-percetakan-12345',
                'domain' => 'percetakan.mitrasova.com',
                'tagline' => 'Percetakan Offset & Digital Printing Cepat, Tajam, dan Berkualitas Tinggi',
                'phone' => '+62 812-3456-7804',
                'email' => 'order@mitrasovaprint.com',
                'address' => 'Jl. Grafika No. 12, Pulo Gadung, Jakarta Timur',
                'service_types' => [
                    [
                        'name' => 'Digital Printing & Packaging',
                        'slug' => 'digital-packaging',
                        'description' => 'Cetak kemasan produk UMKM, box makanan, label stiker tahan air',
                        'services' => [
                            [
                                'name' => 'Custom Box Packaging & Dus Makanan',
                                'slug' => 'custom-box-packaging',
                                'short_desc' => 'Cetak kotak kemasan corrugated, duplex, atau ivory dengan finishing mewah.',
                                'body' => 'Tingkatkan nilai jual produk dengan kemasan khusus dilengkapi laminasi doff/glossy, spot UV, atau hot print foil emas.',
                                'features' => ['Bahan Ivory, Duplex, Kraft food grade', 'Finishing Laminasi Doff/Glossy & Emboss', 'Bisa cetak kuantiti kecil maupun besar', 'Food-safe certified inks'],
                                'is_featured' => true,
                            ],
                        ],
                    ],
                    [
                        'name' => 'Promosi & Corporate Stationery',
                        'slug' => 'promosi-stationery',
                        'description' => 'Brosur, katalog produk, kalender dinding/meja, dan kartu nama',
                        'services' => [
                            [
                                'name' => 'Brosur & Katalog Produk Eksklusif',
                                'slug' => 'brosur-katalog-produk',
                                'short_desc' => 'Cetak offset presisi tinggi dengan warna tajam dan pilihan binding berkualitas.',
                                'body' => 'Cocok untuk presentasi bisnis, pameran produk, dan kampanye pemasaran langsung ke pelanggan korporat.',
                                'features' => ['Kertas Art Paper / Art Carton 150-260 gsm', 'Jilid staples tengah atau lem panas', 'Akurasi warna profil CMYK presisi', 'Pengerjaan kilat ekspres'],
                                'is_featured' => true,
                            ],
                        ],
                    ],
                ],
                'faqs' => [
                    'Layanan Cetak' => [
                        ['q' => 'Format file apa yang paling direkomendasikan untuk dicetak?', 'a' => 'Kami merekomendasikan file PDF berformat warna CMYK dengan resolusi minimal 300 DPI dan outline font.'],
                    ],
                ],
            ],
            [
                'slug' => 'transportasi',
                'name' => 'Mitrasova Trans & Logistics',
                'api_key' => 'key-trans-12345',
                'domain' => 'trans.mitrasova.com',
                'tagline' => 'Armada Terpercaya untuk Sewa Kendaraan Operasional & Ekspedisi Antar Kota',
                'phone' => '+62 812-3456-7805',
                'email' => 'dispatch@mitrasovatrans.com',
                'address' => 'Jl. Raya Logistik No. 88, Cakung, Jakarta Timur',
                'service_types' => [
                    [
                        'name' => 'Logistik & Pengiriman Kargo',
                        'slug' => 'logistik-kargo',
                        'description' => 'Sewa armada truk CDD, CDE, Wingbox untuk angkutan barang antar kota',
                        'services' => [
                            [
                                'name' => 'Sewa Truk Kargo CDD & Wingbox',
                                'slug' => 'sewa-truk-kargo',
                                'short_desc' => 'Armada truk terawat dengan pengemudi berpengalaman dan GPS tracking real-time.',
                                'body' => 'Melayani pengiriman barang logistik reguler maupun sewa borongan (charter) lintas Jawa, Bali, dan Sumatera dengan jaminan keamanan muatan.',
                                'features' => ['Armada CDE, CDD Box, dan Wingbox', 'Pelacakan armada via GPS real-time', 'Asuransi pengangkutan barang', 'Pengemudi bersertifikat dan berpengalaman'],
                                'is_featured' => true,
                            ],
                        ],
                    ],
                    [
                        'name' => 'Rental Kendaraan Korporat',
                        'slug' => 'rental-korporat',
                        'description' => 'Sewa mobil harian, bulanan, dan tahunan untuk staf & direksi perusahaan',
                        'services' => [
                            [
                                'name' => 'Sewa Mobil Operasional Jangka Panjang',
                                'slug' => 'sewa-mobil-operasional',
                                'short_desc' => 'Solusi hemat biaya operasional perusahaan tanpa beban depresiasi aset kendaraan.',
                                'body' => 'Layanan rental mobil korporat lengkap termasuk pemeliharaan rutin berkala, penggantian kendaraan saat servis, dan asuransi all-risk komprehensif.',
                                'features' => ['Pilihan unit Avanza, Innova Zenix, Fortuner', 'Termasuk servis berkala dan ban baru', 'Kendaraan pengganti 1x24 jam', 'Layanan darurat towing 24 jam'],
                                'is_featured' => true,
                            ],
                        ],
                    ],
                ],
                'faqs' => [
                    'Operasional' => [
                        ['q' => 'Apakah layanan sewa truk sudah termasuk bahan bakar dan tol?', 'a' => 'Kami menyediakan paket all-in (armada, supir, BBM, tol) maupun sewa lepas unit (hanya truk dan supir).'],
                    ],
                ],
            ],
            [
                'slug' => 'bengkel-las',
                'name' => 'Mitrasova Las & Interior Estetika',
                'api_key' => 'key-las-12345',
                'domain' => 'las.mitrasova.com',
                'tagline' => 'Fabrikasi Konstruksi Besi, Kanopi Minimalis, Pagar, dan Interior Custom',
                'phone' => '+62 812-3456-7806',
                'email' => 'proyek@mitrasovawelding.com',
                'address' => 'Workshop Fabrikasi Jl. Penggilingan No. 102, Bekasi Barat',
                'service_types' => [
                    [
                        'name' => 'Kanopi & Konstruksi Baja Ringan / Berat',
                        'slug' => 'kanopi-konstruksi',
                        'description' => 'Pemasangan kanopi alderon, solarflat, tempered glass, dan konstruksi mezzanine',
                        'services' => [
                            [
                                'name' => 'Kanopi Modern Besi Hollow & Alderon',
                                'slug' => 'kanopi-modern-besi-alderon',
                                'short_desc' => 'Kanopi estetis peredam panas dan suara hujan dengan rangka besi galvanis tahan karat.',
                                'body' => 'Dikerjakan dengan pengelasan rapi, finishing cat epoxy tahan cuaca ekstrem, dan garansi kebocoran hingga 1 tahun.',
                                'features' => ['Besi hollow galvanis anti-karat', 'Atap Alderon twinwall tebal', 'Garansi konstruksi 1 tahun', 'Survey dan konsultasi desain gratis'],
                                'is_featured' => true,
                            ],
                        ],
                    ],
                    [
                        'name' => 'Pagar, Tralis, & Railing Tangga',
                        'slug' => 'pagar-tralis-railing',
                        'description' => 'Pembuatan pagar minimalis, pintu sliding, tralis pengaman, dan railing balkon',
                        'services' => [
                            [
                                'name' => 'Pagar Minimalis Plat Cutting Laser',
                                'slug' => 'pagar-minimalis-cutting-laser',
                                'short_desc' => 'Pagar berdesain kontemporer dengan motif laser cutting presisi dan kombinasi kayu/WPC.',
                                'body' => 'Memberikan kesan mewah dan modern pada fasad hunian atau bangunan komersial Anda. Tahan benturan dan tahan cuaca.',
                                'features' => ['Plat baja cutting laser motif custom', 'Kombinasi panel WPC tahan rayap', 'Sistem roda dan rel bearing heavy duty', 'Cat powder coating tahan gores'],
                                'is_featured' => true,
                            ],
                        ],
                    ],
                ],
                'faqs' => [
                    'Konsultasi' => [
                        ['q' => 'Apakah dikenakan biaya untuk survey lokasi dan pengukuran?', 'a' => 'Tidak, layanan survey lokasi dan konsultasi desain awal gratis untuk area Jabodetabek.'],
                    ],
                ],
            ],
        ];

        foreach ($units as $unitData) {
            $site = Site::firstOrCreate(
                ['slug' => $unitData['slug']],
                [
                    'name' => $unitData['name'],
                    'api_key' => $unitData['api_key'],
                    'domain' => $unitData['domain'],
                    'is_active' => true,
                ]
            );

            // Attach superadmin to this site
            $site->users()->syncWithoutDetaching([
                $admin->id => ['role' => 'owner'],
            ]);

            // Site Settings
            $settings = [
                'general' => [
                    'site_title' => $unitData['name'],
                    'tagline' => $unitData['tagline'],
                    'brand_color' => '#1E40AF',
                    'meta_keywords' => 'mitrasova, ' . $unitData['slug'] . ', layanan profesional',
                ],
                'modules' => [
                    // nama flag seragam: is_active (per modul, false = menu & route disembunyikan)
                    'is_active' => [
                        'services' => true,
                        'portfolios' => true,
                        'business_units' => true,
                        'news' => true,
                        'contact' => true,
                    ],
                ],
                'contact' => [
                    'phone' => $unitData['phone'],
                    'email' => $unitData['email'],
                    'address' => $unitData['address'],
                    'whatsapp' => preg_replace('/[^0-9]/', '', $unitData['phone']),
                ],
                'social' => [
                    'instagram' => 'https://instagram.com/mitrasova_' . $unitData['slug'],
                    'facebook' => 'https://facebook.com/mitrasova.' . $unitData['slug'],
                    'linkedin' => 'https://linkedin.com/company/mitrasova',
                ],
            ];

            foreach ($settings as $group => $items) {
                foreach ($items as $key => $val) {
                    SiteSetting::updateOrCreate(
                        ['site_id' => $site->id, 'key' => $key],
                        ['group' => $group, 'value' => $val]
                    );
                }
            }

            // Service Types & Services
            $orderIndex = 1;
            $createdServiceTypes = [];
            foreach ($unitData['service_types'] as $typeData) {
                $serviceType = ServiceType::updateOrCreate(
                    ['site_id' => $site->id, 'slug' => $typeData['slug']],
                    [
                        'name' => $typeData['name'],
                        'description' => $typeData['description'],
                        'icon' => 'folder',
                        'order' => $orderIndex++,
                    ]
                );
                $createdServiceTypes[] = $serviceType;

                $srvOrder = 1;
                foreach ($typeData['services'] as $srv) {
                    Service::updateOrCreate(
                        ['site_id' => $site->id, 'slug' => $srv['slug']],
                        [
                            'service_type_id' => $serviceType->id,
                            'name' => $srv['name'],
                            'short_desc' => $srv['short_desc'],
                            'body' => $srv['body'],
                            'features' => $srv['features'],
                            'is_featured' => $srv['is_featured'],
                            'is_active' => true,
                            'order' => $srvOrder++,
                        ]
                    );
                }
            }

            // FAQs
            $faqOrder = 1;
            foreach ($unitData['faqs'] as $catName => $qaList) {
                $faqCat = FaqCategory::updateOrCreate(
                    ['site_id' => $site->id, 'slug' => \Illuminate\Support\Str::slug($catName)],
                    ['name' => $catName, 'order' => $faqOrder++]
                );

                $qOrder = 1;
                foreach ($qaList as $qa) {
                    Faq::updateOrCreate(
                        ['site_id' => $site->id, 'question' => $qa['q']],
                        [
                            'faq_category_id' => $faqCat->id,
                            'answer' => $qa['a'],
                            'order' => $qOrder++,
                            'is_active' => true,
                        ]
                    );
                }
            }

            // Testimonials
            $testimonials = [
                [
                    'name' => 'Bambang Sudarsono',
                    'title' => 'Direktur Operasional PT Sumber Berkah',
                    'message' => 'Layanan dari ' . $unitData['name'] . ' sangat profesional, tepat waktu, dan sangat memuaskan.',
                    'rating' => 5,
                    'order' => 1,
                ],
                [
                    'name' => 'Siti Nurhaliza',
                    'title' => 'Pengusaha Ritel & UMKM Mandiri',
                    'message' => 'Komunikasi tim sangat ramah dan responsif. Menjawab setiap kebutuhan kami dengan solusi yang tepat.',
                    'rating' => 5,
                    'order' => 2,
                ],
            ];

            foreach ($testimonials as $t) {
                Testimonial::updateOrCreate(
                    ['site_id' => $site->id, 'name' => $t['name']],
                    [
                        'title' => $t['title'],
                        'message' => $t['message'],
                        'rating' => $t['rating'],
                        'order' => $t['order'],
                        'is_active' => true,
                    ]
                );
            }

            // Partners
            $partners = [
                ['name' => 'Mitra Perbankan Nasional', 'logo_path' => 'partners/bank.png', 'website_url' => 'https://example.com/bank', 'order' => 1],
                ['name' => 'Asosiasi Bisnis Indonesia', 'logo_path' => 'partners/asosiasi.png', 'website_url' => 'https://example.com/asosiasi', 'order' => 2],
            ];
            foreach ($partners as $p) {
                Partner::updateOrCreate(
                    ['site_id' => $site->id, 'name' => $p['name']],
                    [
                        'logo_path' => $p['logo_path'],
                        'website_url' => $p['website_url'],
                        'order' => $p['order'],
                    ]
                );
            }

            // Article Category & Sample Article
            $artCat = ArticleCategory::updateOrCreate(
                ['site_id' => $site->id, 'slug' => 'informasi-terkini'],
                [
                    'name' => 'Informasi & Tips',
                    'description' => 'Kumpulan artikel dan wawasan bermanfaat seputar industri kami.',
                    'order' => 1,
                ]
            );

            Article::updateOrCreate(
                ['site_id' => $site->id, 'slug' => 'tips-memilih-layanan-terbaik-untuk-usaha'],
                [
                    'article_category_id' => $artCat->id,
                    'author_id' => $admin->id,
                    'title' => 'Tips Memilih Layanan Terbaik untuk Menunjang Produktivitas Usaha',
                    'excerpt' => 'Panduan praktis bagi pengusaha dan konsumen dalam menentukan partner bisnis yang kredibel dan berkualitas tinggi.',
                    'body' => 'Memilih mitra bisnis yang tepat merupakan langkah fundamental bagi keberlangsungan usaha. Di era modern, faktor kecepatan, transparansi harga, serta rekam jejak integritas menjadi indikator utama. Pastikan Anda selalu meneliti portofolio dan legalitas sebelum bermitra.',
                    'status' => 'published',
                    'published_at' => now(),
                ]
            );

            // Portfolio
            if (! empty($createdServiceTypes)) {
                Portfolio::updateOrCreate(
                    ['site_id' => $site->id, 'slug' => 'proyek-unggulan-' . $unitData['slug']],
                    [
                        'service_type_id' => $createdServiceTypes[0]->id,
                        'title' => 'Proyek Percontohan Unggulan ' . $unitData['name'],
                        'client_name' => 'Klien Korporat Nasional',
                        'completion_date' => now()->subMonths(2)->toDateString(),
                        'description' => 'Implementasi solusi menyeluruh yang berhasil meningkatkan efisiensi klien hingga 35%.',
                        'order' => 1,
                        'is_featured' => true,
                    ]
                );
            }

            // Home Page with Sections (specifically for KSP and other units)
            $homePage = Page::updateOrCreate(
                ['site_id' => $site->id, 'slug' => 'home'],
                [
                    'title' => 'Beranda',
                    'meta_title' => $unitData['name'] . ' - ' . $unitData['tagline'],
                    'meta_desc' => 'Layanan profesional dan terpercaya dari ' . $unitData['name'],
                    'is_active' => true,
                ]
            );

            $sections = [
                [
                    'key' => 'hero',
                    'title' => $unitData['tagline'],
                    'subtitle' => $unitData['name'],
                    'body' => 'Kami berkomitmen memberikan layanan unggulan dengan integritas tinggi dan inovasi berkelanjutan.',
                    'payload' => [
                        'cta_primary' => ['text' => 'Hubungi Kami', 'url' => '#contact'],
                        'cta_secondary' => ['text' => 'Lihat Layanan', 'url' => '#services'],
                    ],
                    'image_path' => 'banners/hero-' . $unitData['slug'] . '.jpg',
                    'order' => 1,
                ],
                [
                    'key' => 'about',
                    'title' => 'Tentang Kami',
                    'subtitle' => 'Mengenal ' . $unitData['name'],
                    'body' => $unitData['name'] . ' merupakan salah satu pilar bisnis Mitrasova yang bergerak dalam penyediaan layanan berkualitas tinggi dan berorientasi pada kepuasan pelanggan.',
                    'payload' => [
                        'stats' => [
                            ['label' => 'Klien Puas', 'value' => '1,500+'],
                            ['label' => 'Tahun Pengalaman', 'value' => '10+'],
                            ['label' => 'Tim Ahli', 'value' => '50+'],
                        ],
                    ],
                    'image_path' => 'banners/about-' . $unitData['slug'] . '.jpg',
                    'order' => 2,
                ],
                [
                    'key' => 'cta',
                    'title' => 'Siap Bekerja Sama dengan Kami?',
                    'subtitle' => 'Dapatkan Konsultasi dan Penawaran Terbaik',
                    'body' => 'Hubungi kami sekarang untuk mendiskusikan kebutuhan Anda bersama tim konsultan kami.',
                    'payload' => [
                        'button_text' => 'Kirim Pesan',
                        'button_link' => '#contact',
                    ],
                    'image_path' => null,
                    'order' => 3,
                ],
            ];

            foreach ($sections as $sec) {
                PageSection::updateOrCreate(
                    ['page_id' => $homePage->id, 'key' => $sec['key']],
                    [
                        'title' => $sec['title'],
                        'subtitle' => $sec['subtitle'],
                        'body' => $sec['body'],
                        'payload' => $sec['payload'],
                        'image_path' => $sec['image_path'],
                        'order' => $sec['order'],
                    ]
                );
            }
        }
    }
}
