# Plan Frontend - Master Section Types (Portal & Landing)

Status: menunggu backend Fase 0 selesai.
Backend: lihat `docs/plan-section-types.md`.
Dokumen ini untuk `portal-landing-page` dan `landing-page`.

## 1. Ringkasan

Backend menambahkan master `section_types` (baca-saja) berisi definisi field tiap tipe section. Portal memakainya untuk merender form sesuai tipe, sehingga editor tidak perlu menulis JSON. Landing memakainya sebagai daftar key kanonik.

Yang berubah untuk frontend:

- Ada 2 endpoint baru (daftar tipe).
- Portal: form section berubah dari "key bebas + JSON" menjadi "pilih tipe + field dinamis".
- Landing: key `vision_mission` harus ikut key kanonik `visi-misi` (lihat bagian 7).

Tidak ada perubahan pada `GET /pages/{slug}`; bentuk `sections[]` tetap sama.

## 2. Endpoint

| Method | Path | Header | Akses |
|---|---|---|---|
| `GET` | `/api/v1/section-types` | `X-Site-Key` wajib | publik |
| `GET` | `/api/v1/admin/section-types` | `X-Site-Key` + Bearer | tenant admin |

Respons:

```json
{
  "success": true,
  "data": [
    {
      "id": 7,
      "key": "visi-misi",
      "name": "Visi & Misi",
      "description": null,
      "fields": [
        { "name": "visi", "label": "Visi", "type": "textarea" },
        { "name": "misi", "label": "Misi", "type": "repeater", "fields": [
          { "name": "title", "label": "Judul", "type": "text", "required": true, "max": 150 },
          { "name": "desc", "label": "Penjelasan", "type": "textarea" }
        ]}
      ],
      "aliases": ["vision_mission"],
      "is_active": true
    }
  ]
}
```

`GET` publik hanya mengembalikan tipe `is_active = true`. Portal memakai endpoint admin (data + `is_active`).

## 3. Format `fields`

| Properti | Arti |
|---|---|
| `name` | key di payload |
| `label` | label form |
| `type` | lihat tabel di bawah |
| `required` | wajib diisi (hanya saat field ada) |
| `max` | panjang maksimum string |
| `rows` | tinggi textarea |
| `options` | pilihan untuk `select` |
| `fields` | anak untuk `group`/`repeater` |

| type | Nilai | Komponen portal |
|---|---|---|
| `text` | string | `FormInput` |
| `textarea` | string | `FormTextarea` |
| `richtext` | HTML | `FormRichText` |
| `url` | string `http(s)://`, `/...`, atau `#...` | `FormInput` |
| `image` | path storage | `MediaPicker` |
| `number` | number | `FormInputNumber` |
| `boolean` | bool | `FormSwitch` |
| `select` | string dari `options` | `FormInputOption` |
| `list` | array string | `FormTextList` (baru) |
| `group` | object | `FormGroup` (baru) |
| `repeater` | array object | `FormRepeater` (baru) |

Satu level nesting saja. Kolom section (`title`, `subtitle`, `body`, `image_path`, `order`, `is_active`) tetap form biasa di luar schema.

## 4. Tipe yang tersedia

| key | nama | field payload |
|---|---|---|
| `hero` | Hero / Banner | `badge`, `slides[]`, `stats[]`, `image_path`, `image_label`, `image_caption`, `cta_primary{}`, `cta_secondary{}` |
| `featured` | Produk Unggulan | `badge`, `cards[]` (`title`, `description`, `products[]`, `note`), `cta_label` |
| `about` | Tentang / Statistik | `badge`, `stats[]` (`label`, `value`) |
| `office` | Lokasi Kantor | `badge`, `maps_embed` |
| `cta` | CTA Banner | `button_label`, `button_url` |
| `sejarah` | Sejarah | tanpa payload (isi di `body`) |
| `visi-misi` | Visi & Misi | `visi`, `misi[]` (`title`, `desc`) |
| `strategi` | Strategi | `items[]` (`title`, `desc1`, `desc2`) |
| `program` | Program Kerja | `items[]` (`title`, `desc1`, `desc2`) |
| `values` | Nilai / Budaya | `badge`, `items[]` (`title`, `description`), `openings[]` (`title`, `location`, `type`, `description`, `deadline`) |
| `instagram` | Feed Instagram | `handle`, `profile_url`, `posts[]` |

Landing saat ini merender `hero`, `cta`, dan `values` secara khusus; sisanya lewat renderer generik. Tipe yang belum punya komponen render jangan ditampilkan di portal (lihat bagian 7).

## 5. Aturan simpan (WAJIB)

1. **Merge payload, jangan ganti.** Payload lama bisa berisi field di luar schema (mis. `image_url` hasil importer). Saat submit:
   ```ts
   const nextPayload = { ...(section?.payload ?? {}), ...valuesFromForm };
   ```
   Field di luar schema harus tetap terkirim. Tampilkan sisanya sebagai read-only di panel Advanced.
2. **Key tidak berubah.** Portal mengirim `key` dari tipe yang dipilih. Backend tidak menormalkan alias; `vision_mission` tetap tersimpan seperti itu.
3. **Payload kosong boleh.** Section lama tanpa payload tetap bisa disimpan.
4. **Error validasi** memakai format Laravel `errors` yang sudah dipetakan `useServerErrors`; key error berbentuk `payload.slides.0.title`, `payload.misi.0.title`, dst. Petakan ke field repeater yang tepat.
5. **Warnings.** Respons store/update berisi `warnings: string[]`. Tampilkan sebagai toast, jangan dianggap gagal.
6. **Strict mode.** Selama `SECTION_TYPES_STRICT=false`, key tak dikenal tetap tersimpan. Jangan mengandalkan ini: portal hanya menawarkan tipe dari endpoint.

Contoh payload `visi-misi`:

```json
{
  "visi": "Menjadi ekosistem keuangan berbasis komunitas yang inovatif...",
  "misi": [
    { "title": "Melayani Semua Lapisan", "desc": "Menyediakan layanan simpan pinjam yang mudah diakses..." }
  ]
}
```

Contoh payload `hero`:

```json
{
  "badge": "KSP Mitra Sejati Persada",
  "slides": [
    { "title": "Solusi Kemitraan Anda", "description": "...", "badge": "Berikut", "image_path": "hero/ksp/slide-1.webp" }
  ],
  "stats": [{ "label": "Tahun Berdiri", "value": "2006" }],
  "cta_primary": { "text": "Hubungi Kami", "url": "#contact" },
  "cta_secondary": { "text": "Lihat Layanan", "url": "#services" }
}
```

## 6. Tugas portal (`portal-landing-page`)

1. **API & hook**
   - `features/section-types/api/section-types.api.ts`: `listSectionTypes()` memakai `apiClient` yang ada (Bearer + `X-Site-Key` otomatis).
   - `useSectionTypes()` dengan query key per tenant; cache stabil.

2. **Form section** (`features/pages/components/section-form-dialog.tsx`)
   - Ganti input key + preset chip dengan **Select tipe** (label `name`, value `key`, tampilkan key kecil).
   - Saat create: payload awal = `{}`. Saat edit: `section.payload`.
   - Tipe berubah → reset field form ke payload kosong **kecuali** sedang edit tipe yang sama.
   - Section dengan key yang tidak ada di daftar → tampilkan peringatan "Tipe tidak dikenal" + JSON editor (tetap bisa simpan).

3. **Renderer field**
   - `DynamicSectionFields` membaca `fields[]` dan merender komponen sesuai tabel di bagian 3.
   - Tambah komponen: `FormTextList` (tambah/hapus baris), `FormRepeater` (`useFieldArray`: tambah, hapus, naik/turun; judul item dari field pertama), `FormGroup` (grid 2–3 kolom).
   - Validasi client mengikuti `required` + `max`; server tetap sumber kebenaran.

4. **JSON advanced**
   - Panel `collapsible` "Advanced (JSON)" berisi payload lengkap.
   - Tetap dua arah: edit form → JSON ikut berubah; edit JSON → form mengikuti bila valid.
   - Jangan jadikan JSON jalur utama.

5. **MediaPicker** (field `image`)
   - Modal grid dari `GET /admin/media` (sudah ada) + tombol upload.
   - Upload butuh endpoint baru `POST /api/v1/admin/media` (`file`, `dir` opsional, maks 10 MB) → simpan ke `sections/<slug>/`, respons item media (minimal `path` + `url`).
   - Nilai yang disimpan ke payload: `path` (bukan URL absolut). Jangan menyimpan `*_url`.

6. **Setelah simpan** invalidasi query page detail + media.

## 7. Tugas landing (`landing-page`)

1. **Key kanonik**: ganti `vision_mission` → `visi-misi` di `section-renderer.tsx` / `allowedKeys`.
2. **Cakupan render**: `DynamicPageRenderer` sudah merender hero/cta/values khusus dan sisanya generik. Perlu komponen/varian untuk `sejarah` (body), `strategi`/`program` (`items[]`), `instagram` (`posts[]`), `featured`/`about` (`stats[]`).
3. **Jangan render key tak dikenal sebagai kosong**: minimal tampilkan title/body, atau sembunyikan.
4. **Sinkron dengan portal**: tipe yang belum punya komponen render jangan diaktifkan di pilihan portal. Koordinasi sebelum `SECTION_TYPES_STRICT=true`.

## 8. Media upload (endpoint baru)

| Method | Path | Body | Respons |
|---|---|---|---|
| `POST` | `/api/v1/admin/media` | multipart `file` (wajib), `dir` (opsional, default `sections/<slug>`) | `data: { path, url, size, modified_at }` |

Aturan: hanya gambar (`jpg/jpeg/png/webp/gif/svg`), maks 10 MB, path aman (tanpa `..`). Setelah upload, file muncul di `GET /admin/media` dan dikenali sebagai milik tenant.

## 9. Checklist terima

- [ ] Portal menampilkan dropdown 11 tipe; tidak ada input key bebas saat create.
- [ ] Edit `visi-misi`: visi + misi (tambah/hapus/urutkan) tanpa membuka JSON.
- [ ] Edit `hero.slides[]`: tambah slide + pilih gambar dari MediaPicker, tanpa JSON.
- [ ] Simpan section lama tidak menghilangkan field di luar schema (`image_url` dsb.).
- [ ] Error `payload.misi.0.title` muncul di field yang benar.
- [ ] `warnings` tampil sebagai toast dan simpan tetap dianggap sukses.
- [ ] Landing `/profil` merender `visi-misi` dari key kanonik.
- [ ] Request/response tidak menyentuh `X-Site-Key` berbeda per tenant.
