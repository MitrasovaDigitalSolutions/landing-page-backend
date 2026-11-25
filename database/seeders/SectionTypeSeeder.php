<?php

namespace Database\Seeders;

use App\Models\SectionType;
use Illuminate\Database\Seeder;

class SectionTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            [
                'key' => 'hero',
                'name' => 'Hero / Banner',
                'description' => 'Banner utama dengan slide, statistik, dan CTA.',
                'aliases' => null,
                'fields' => [
                    $this->field('badge', 'Badge', 'text', ['max' => 100]),
                    $this->field('slides', 'Slides', 'repeater', ['fields' => [
                        $this->field('title', 'Judul', 'text', ['required' => true, 'max' => 150]),
                        $this->field('description', 'Deskripsi', 'textarea'),
                        $this->field('badge', 'Badge', 'text', ['max' => 100]),
                        $this->field('image_path', 'Gambar', 'image'),
                        $this->field('image_label', 'Label Gambar', 'text', ['max' => 100]),
                        $this->field('image_caption', 'Caption Gambar', 'text', ['max' => 255]),
                    ]]),
                    $this->field('stats', 'Statistik', 'repeater', ['fields' => [
                        $this->field('label', 'Label', 'text', ['required' => true, 'max' => 60]),
                        $this->field('value', 'Nilai', 'text', ['required' => true, 'max' => 60]),
                    ]]),
                    $this->field('image_path', 'Gambar', 'image'),
                    $this->field('image_label', 'Label Gambar', 'text', ['max' => 100]),
                    $this->field('image_caption', 'Caption Gambar', 'text', ['max' => 255]),
                    $this->field('cta_primary', 'CTA Utama', 'group', ['fields' => [
                        $this->field('text', 'Teks', 'text', ['max' => 60]),
                        $this->field('url', 'URL', 'url'),
                    ]]),
                    $this->field('cta_secondary', 'CTA Sekunder', 'group', ['fields' => [
                        $this->field('text', 'Teks', 'text', ['max' => 60]),
                        $this->field('url', 'URL', 'url'),
                    ]]),
                ],
            ],
            [
                'key' => 'featured',
                'name' => 'Produk Unggulan',
                'description' => 'Kartu produk unggulan.',
                'aliases' => null,
                'fields' => [
                    $this->field('badge', 'Badge', 'text', ['max' => 100]),
                    $this->field('cards', 'Kartu', 'repeater', ['fields' => [
                        $this->field('title', 'Judul', 'text', ['required' => true, 'max' => 150]),
                        $this->field('description', 'Deskripsi', 'textarea'),
                        $this->field('products', 'Produk', 'list'),
                        $this->field('note', 'Catatan', 'text', ['max' => 255]),
                    ]]),
                    $this->field('cta_label', 'Label CTA', 'text', ['max' => 100]),
                ],
            ],
            [
                'key' => 'about',
                'name' => 'Tentang / Statistik',
                'description' => 'Ringkasan tentang dengan statistik.',
                'aliases' => null,
                'fields' => [
                    $this->field('badge', 'Badge', 'text', ['max' => 100]),
                    $this->field('stats', 'Statistik', 'repeater', ['fields' => [
                        $this->field('label', 'Label', 'text', ['required' => true, 'max' => 60]),
                        $this->field('value', 'Nilai', 'text', ['required' => true, 'max' => 60]),
                    ]]),
                ],
            ],
            [
                'key' => 'office',
                'name' => 'Lokasi Kantor',
                'description' => 'Peta dan info lokasi kantor.',
                'aliases' => null,
                'fields' => [
                    $this->field('badge', 'Badge', 'text', ['max' => 100]),
                    $this->field('maps_embed', 'Embed Maps', 'text'),
                ],
            ],
            [
                'key' => 'cta',
                'name' => 'CTA Banner',
                'description' => 'Banner ajakan dengan tombol.',
                'aliases' => null,
                'fields' => [
                    $this->field('button_label', 'Label Tombol', 'text', ['max' => 100]),
                    $this->field('button_url', 'URL Tombol', 'url'),
                ],
            ],
            [
                'key' => 'sejarah',
                'name' => 'Sejarah',
                'description' => 'Konten sejarah (isi di kolom body).',
                'aliases' => null,
                'fields' => [],
            ],
            [
                'key' => 'visi-misi',
                'name' => 'Visi & Misi',
                'description' => 'Visi dan daftar misi.',
                'aliases' => ['vision_mission'],
                'fields' => [
                    $this->field('visi', 'Visi', 'textarea'),
                    $this->field('misi', 'Misi', 'repeater', ['fields' => [
                        $this->field('title', 'Judul', 'text', ['required' => true, 'max' => 150]),
                        $this->field('desc', 'Penjelasan', 'textarea'),
                    ]]),
                ],
            ],
            [
                'key' => 'strategi',
                'name' => 'Strategi',
                'description' => 'Daftar strategi.',
                'aliases' => null,
                'fields' => [
                    $this->field('items', 'Item', 'repeater', ['fields' => [
                        $this->field('title', 'Judul', 'text', ['required' => true, 'max' => 150]),
                        $this->field('desc1', 'Deskripsi 1', 'textarea'),
                        $this->field('desc2', 'Deskripsi 2', 'textarea'),
                    ]]),
                ],
            ],
            [
                'key' => 'program',
                'name' => 'Program Kerja',
                'description' => 'Daftar program kerja.',
                'aliases' => null,
                'fields' => [
                    $this->field('items', 'Item', 'repeater', ['fields' => [
                        $this->field('title', 'Judul', 'text', ['required' => true, 'max' => 150]),
                        $this->field('desc1', 'Deskripsi 1', 'textarea'),
                        $this->field('desc2', 'Deskripsi 2', 'textarea'),
                    ]]),
                ],
            ],
            [
                'key' => 'values',
                'name' => 'Nilai / Budaya',
                'description' => 'Nilai budaya dan lowongan.',
                'aliases' => null,
                'fields' => [
                    $this->field('badge', 'Badge', 'text', ['max' => 100]),
                    $this->field('items', 'Item', 'repeater', ['fields' => [
                        $this->field('title', 'Judul', 'text', ['required' => true, 'max' => 150]),
                        $this->field('description', 'Deskripsi', 'textarea'),
                    ]]),
                    $this->field('openings', 'Lowongan', 'repeater', ['fields' => [
                        $this->field('title', 'Judul', 'text', ['required' => true, 'max' => 150]),
                        $this->field('location', 'Lokasi', 'text', ['max' => 100]),
                        $this->field('type', 'Tipe', 'text', ['max' => 50]),
                        $this->field('description', 'Deskripsi', 'textarea'),
                        $this->field('deadline', 'Deadline', 'text', ['max' => 20]),
                    ]]),
                ],
            ],
            [
                'key' => 'instagram',
                'name' => 'Feed Instagram',
                'description' => 'Feed Instagram.',
                'aliases' => null,
                'fields' => [
                    $this->field('handle', 'Handle', 'text', ['max' => 100]),
                    $this->field('profile_url', 'URL Profil', 'url'),
                    $this->field('posts', 'Post', 'list'),
                ],
            ],
        ];

        foreach ($types as $type) {
            SectionType::updateOrCreate(['key' => $type['key']], $type);
        }
    }

    private function field(string $name, string $label, string $type, array $extra = []): array
    {
        return array_merge(['name' => $name, 'label' => $label, 'type' => $type], $extra);
    }
}
