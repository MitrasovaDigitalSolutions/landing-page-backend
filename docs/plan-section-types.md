# Plan Backend - Master Section Types

Status: disetujui, dikerjakan (Fase 0).
Kontrak & tugas frontend: lihat `docs/plan-section-types-frontend.md`.
Cakupan dokumen ini: backend saja (migration, model, seeder, validator, endpoint, command, test, OpenAPI).

## 1. Tujuan

Section saat ini hanya dibedakan `key` + `payload` JSON bebas. Backend perlu master `section_types` agar:

- portal bisa merender form sesuai tipe (kontrak ada di dokumen frontend),
- backend bisa memvalidasi payload sebelum disimpan,
- landing punya daftar key kanonik.

Prinsip: **aditif & tidak merusak data lama**. `page_sections` tidak diubah, payload lama tidak dimigrasi, dan selama transisi penulisan tidak diblokir.

## 2. Skema `section_types`

Global (bukan per-site).

```php
Schema::create('section_types', function (Blueprint $table) {
    $table->id();
    $table->string('key', 50)->unique();
    $table->string('name', 100);
    $table->text('description')->nullable();
    $table->json('fields');
    $table->json('aliases')->nullable();
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});
```

`page_sections` **tidak diubah**. `key` tetap disimpan apa adanya (alias hanya untuk resolusi tipe, tidak menulis ulang key).

## 3. Format `fields`

Array definisi field:

```json
{ "name": "title", "label": "Judul", "type": "text", "required": true, "max": 150 }
```

Properti: `name`, `label`, `type`, `required` (opsional), `max` (opsional, string), `rows` (textarea), `options` (select), `fields` (group/repeater).

Tipe yang didukung validator:

| type | Nilai valid |
|---|---|
| `text` | string |
| `textarea` | string |
| `richtext` | string |
| `url` | string diawali `http://`, `https://`, `/`, atau `#` |
| `image` | string path (relatif, maks 255; boleh kosong) |
| `number` | int/float |
| `boolean` | bool |
| `select` | string yang ada di `options` |
| `list` | array of string |
| `group` | object, divalidasi terhadap `fields` |
| `repeater` | array of object, tiap item divalidasi terhadap `fields` |

Aturan: satu level nesting. Field di luar schema **diabaikan** (bukan error). Payload `null`/`[]` selalu lolos (section lama boleh kosong). `required` hanya berlaku saat field ada di payload; item `repeater` divalidasi per item.

## 4. Definisi 11 tipe (seeder)

Format ringkas: `nama: tipe(max)`, `req` = required, `[]` = repeater, `{}` = group.

**hero** — "Hero / Banner"
- `badge`: text(100)
- `slides`[]: `title` text(150) req, `description` textarea, `badge` text(100), `image_path` image, `image_label` text(100), `image_caption` text(255)
- `stats`[]: `label` text(60) req, `value` text(60) req
- `image_path`: image, `image_label`: text(100), `image_caption`: text(255)
- `cta_primary`{}: `text` text(60), `url` url
- `cta_secondary`{}: `text` text(60), `url` url

**featured** — "Produk Unggulan"
- `badge`: text(100)
- `cards`[]: `title` text(150) req, `description` textarea, `products` list, `note` text(255)
- `cta_label`: text(100)

**about** — "Tentang / Statistik"
- `badge`: text(100)
- `stats`[]: `label` text(60) req, `value` text(60) req

**office** — "Lokasi Kantor"
- `badge`: text(100)
- `maps_embed`: text

**cta** — "CTA Banner"
- `button_label`: text(100)
- `button_url`: url

**sejarah** — "Sejarah"
- (tanpa field; konten di kolom `body`)

**visi-misi** — "Visi & Misi", alias `vision_mission`
- `visi`: textarea
- `misi`[]: `title` text(150) req, `desc` textarea

**strategi** — "Strategi"
- `items`[]: `title` text(150) req, `desc1` textarea, `desc2` textarea

**program** — "Program Kerja"
- `items`[]: `title` text(150) req, `desc1` textarea, `desc2` textarea

**values** — "Nilai / Budaya"
- `badge`: text(100)
- `items`[]: `title` text(150) req, `description` textarea
- `openings`[]: `title` text(150) req, `location` text(100), `type` text(50), `description` textarea, `deadline` text(20)

**instagram** — "Feed Instagram"
- `handle`: text(100)
- `profile_url`: url
- `posts`: list

Seeder idempotent `updateOrCreate(['key' => ...], [...])`, dipanggil dari `DatabaseSeeder`.

## 5. Perilaku API

| Method | Path | Akses | Respons |
|---|---|---|---|
| `GET` | `/api/v1/section-types` | publik + `X-Site-Key` | tipe aktif, urut `key` |
| `GET` | `/api/v1/admin/section-types` | `auth:sanctum` + `tenant` + `tenant.auth` | semua tipe (termasuk nonaktif) |

Bentuk item: `id`, `key`, `name`, `description`, `fields`, `aliases`, `is_active` (admin).

`GET` publik hanya `where('is_active', true)`, format `{ success: true, data: [...] }` mengikuti controller lain.

### Validasi section (`POST/PUT /admin/pages/{id}/sections`, `/admin/sections/{id}`)

1. Validasi field section seperti sekarang (`key`, `title`, `subtitle`, `body`, `payload`, `image`, `order`, `is_active`).
2. Resolusi tipe: cocokkan `key` ke `section_types.key` atau item `aliases` (dilakukan di PHP, bukan query JSON, agar SQLite/Postgres sama).
3. Jika tipe ditemukan dan payload tidak kosong → `SectionPayloadValidator`. Gagal → 422 dengan key error `payload.<path>` (mis. `payload.slides.0.title`).
4. Jika tipe tidak ditemukan:
   - `config('cms.section_types.strict') === false` → tetap simpan, respons menambahkan `warnings: ["Tipe section '<key>' belum terdaftar; payload disimpan apa adanya."]`.
   - `true` → 422 `key: Tipe section tidak dikenal.`
5. Respons store/update selalu menyertakan `warnings` (array, boleh kosong) di top-level. Field lain tidak berubah.
6. `key` **tidak** dinormalisasi.

Config baru `config/cms.php`:

```php
return [
    'section_types' => [
        'strict' => env('SECTION_TYPES_STRICT', false),
    ],
];
```

## 6. Command checker (gate)

`php artisan cms:check-section-payloads` — read-only.

- Iterasi semua `PageSection` yang punya payload tidak kosong.
- Laporkan: `section_id`, `page`, `site`, `key`, daftar error validasi / "tipe tidak dikenal".
- Ringkasan: total dicek, lolos, gagal, key tak dikenal.
- Exit code `0` bila tidak ada masalah, `1` bila ada (agar bisa dipakai gate deploy).
- Opsi `--site=slug` untuk membatasi tenant.

## 7. File yang disentuh

- `database/migrations/2026_11_25_000001_create_section_types_table.php`
- `app/Models/SectionType.php`
- `database/seeders/SectionTypeSeeder.php` + panggilan di `database/seeders/DatabaseSeeder.php`
- `app/Support/SectionPayloadValidator.php`
- `config/cms.php`
- `app/Console/Commands/CheckSectionPayloads.php`
- `app/Http/Controllers/Api/V1/SectionTypeController.php`
- `app/Http/Controllers/Api/V1/Admin/AdminSectionTypeController.php`
- `routes/api.php` (2 route)
- `app/Http/Controllers/Api/V1/Admin/AdminPageController.php` (storeSection/updateSection)
- `public/openapi.json`
- `tests/Feature/SectionTypeTest.php`

## 8. Test wajib

- Seeder menghasilkan 11 tipe, key sesuai daftar, alias `vision_mission` ada di `visi-misi`.
- `GET /api/v1/section-types` (dengan `X-Site-Key`) → 200, memuat `fields`; tanpa header → 400 (middleware tenant).
- `GET /api/v1/admin/section-types` dengan token tenant → 200; tanpa token → 401.
- Store `visi-misi` valid → 201, `warnings` kosong.
- Store `misi` tanpa `title` → 422, error key `payload.misi.0.title`.
- Store `cta.button_url = "#kontak"` → 201 (URL longgar).
- Store `cta.button_url = "bukan url"` → 422.
- Key tak dikenal, strict `false` → 201 + `warnings` tidak kosong.
- Key tak dikenal, strict `true` → 422 pada `key`.
- Alias `vision_mission` dikenali (strict `true`) dan key tersimpan tetap `vision_mission`.
- Payload kosong untuk tipe apa pun → 201.
- Field ekstra di payload dipertahankan setelah simpan.
- `cms:check-section-payloads` exit 0 pada data seed normal.

## 9. OpenAPI

- Tambah schema `SectionType` (+ `SectionTypeField` yang mendukung `fields` rekursif) dan respons list.
- Tambah 2 path: `GET /api/v1/section-types` (param `X-Site-Key`, tanpa security) dan `GET /api/v1/admin/section-types` (bearer + `X-Site-Key`).
- Deskripsi `payload` pada `PageSectionInput`: "divalidasi terhadap section_types; field di luar schema dipertahankan".
- Jangan ubah `securitySchemes` (tetap hanya `bearerAuth`).

## 10. Gate & urutan

1. Implementasi + test hijau (`php artisan test`).
2. Deploy dengan `SECTION_TYPES_STRICT=false`.
3. Jalankan `cms:check-section-payloads` di prod → harus 0 masalah.
4. Portal menyusul (dokumen frontend).
5. `SECTION_TYPES_STRICT=true` setelah portal + landing siap.

Backup sebelum deploy: `/home/ubuntu/backups/landingpage/backup.sh`.

## 11. Risiko teknis

- **Payload kehilangan field** → backend tidak pernah menulis ulang payload; merge adalah tanggung jawab portal. Backend hanya mempertahankan apa yang dikirim.
- **Validasi memblokir edit lama** → payload kosong lolos, field ekstra diabaikan, strict default `false`.
- **SQLite vs Postgres** → resolusi alias di PHP, tidak memakai `whereJsonContains`.
- **Schema drift** → hanya boleh menambah field opsional; rename field = migrasi payload.
- **Rollback** → drop tabel `section_types` + revert kode; data section tidak tersentuh.
