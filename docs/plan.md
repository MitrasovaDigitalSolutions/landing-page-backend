# Plan Frontend — Produk Bergambar Miset + Mode Tampilan Layanan

Dokumen kerja untuk `landing-page` (publik) dan `portal-landing-page` (CMS).
Backend `landing-page-backend` menyediakan kontrak di bawah; perubahan backend dikerjakan terpisah dan akan di-deploy lebih dulu.

Status: **kontrak final**. Frontend tidak perlu menunggu OpenAPI.

---

## 1. Konteks

KSP sudah live dengan halaman layanan berbentuk **teks** (accordion). Miset (unit usaha konveksi) menjual produk fisik, jadi butuh **kartu bergambar + harga** dalam bentuk carousel di beranda dan grid di halaman katalog.

Solusi: **tidak ada tabel/modul/halaman baru**. Produk memakai `services` yang sudah ada, ditambah dua kolom harga. Perbedaan tampilan antar tenant diatur satu setting: `services.layout`.

---

## 2. Setting `services.layout`

Disimpan di settings group `services`, key `layout`. Dibaca dari `GET /settings` → `data.services.layout`.

| Nilai | Beranda | Halaman `/produk` |
|---|---|---|
| `accordion` (default, KSP) | accordion teks per tipe | accordion teks per tipe |
| `carousel` (Miset) | carousel kartu produk unggulan (`is_featured`) | **grid** semua produk |
| `grid` | grid kartu unggulan | grid semua produk |

Aturan:
- Nilai kosong / tidak dikenal → `accordion`.
   (Semua tenant di DB prod sudah punya nilai eksplisit: `ksp`/`percetakan`/`teknologi`/`transportasi`/`bengkel-las` = `accordion`, `konveksi` = `carousel`.)
- Carousel hanya di beranda (teaser). Katalog tetap grid supaya semua produk terbaca.
- Produk tanpa foto tetap dirender sebagai **kartu dengan placeholder**, jangan fallback ke baris teks.
- Harga hanya dirender bila `price !== null`.

---

## 3. Kontrak data publik

### `GET /api/v1/settings`
Header `X-Site-Key: key-konveksi-12345` (slug tenant Miset masih `konveksi`).

```json
{
  "success": true,
  "data": {
    "general": { "site_title": "Miset", "brand_color": "#0F766E" },
    "services": { "layout": "carousel" },
    "modules": { "is_active": { "services": true, "business_units": false } },
    "contact": { "whatsapp": "6282247170756" }
  }
}
```

Setting ini sudah ikut ter-cache di layout landing → tidak perlu request tambahan.

### `GET /api/v1/services?featured=1&per_page=20`

```json
{
  "success": true,
  "data": [
    {
      "id": 41,
      "service_type_id": 7,
      "name": "Kaos Komunitas",
      "slug": "kaos-komunitas",
      "short_desc": "Kaos komunitas dan tim dengan sablon tahan lama.",
      "body": null,
      "thumbnail_path": "services/konveksi/abc.jpg",
      "thumbnail_url": "http://43.157.197.42:8088/storage/services/konveksi/abc.jpg",
      "features": ["Cotton combed 24s/30s", "Minimal 6 pcs"],
      "price": 75000,
      "price_note": "/pcs",
      "is_featured": true,
      "is_active": true,
      "order": 0,
      "service_type": { "id": 7, "name": "Kaos & Apparel", "slug": "kaos-apparel" }
    }
  ],
  "meta": { "current_page": 1, "last_page": 1, "per_page": 20, "total": 8 }
}
```

Field baru / berubah:

| Field | Tipe | Catatan |
|---|---|---|
| `price` | `number \| null` | Rupiah, integer tanpa desimal/titik. `null` = tanpa harga. |
| `price_note` | `string \| null` | Teks pendek: `"/pcs"`, `"per lusin"`, `"mulai dari"`. |
| `body` | `string \| null` | **Sekarang boleh null** (sebelumnya wajib). |

Filter yang sudah ada: `?service_type=<slug|id>`, `?featured=1`, `?per_page` (max 100).

Format harga:

```ts
new Intl.NumberFormat("id-ID", {
  style: "currency", currency: "IDR", maximumFractionDigits: 0,
}).format(price) // "Rp 75.000"
```

---

## 4. Kontrak admin (untuk portal)

`POST /api/v1/admin/services` — multipart/form-data, `Authorization: Bearer`, `X-Site-Key`.
`PUT /api/v1/admin/services/{id}` sama (`api-client.ts` otomatis mengubah PUT+FormData menjadi POST + `_method`).

| field | tipe | wajib | catatan |
|---|---|---|---|
| `service_type_id` | int | ya | |
| `name` | string ≤150 | ya | |
| `short_desc` | string ≤300 | ya | subjudul kartu |
| `body` | string | **tidak** | |
| `thumbnail` | file jpg/jpeg/png/webp ≤10MB | tidak | foto kartu |
| `icon` | file jpg/jpeg/png/webp/svg ≤5MB | tidak | |
| `features[0..n]` | string | tidak | |
| `is_featured` | `"1"`/`"0"` | tidak | masuk carousel beranda |
| `is_active` | `"1"`/`"0"` | tidak | |
| `order` | int | tidak | |
| **`price`** | int ≥0 | tidak | kirim `""` untuk mengosongkan |
| **`price_note`** | string ≤30 | tidak | mis. `/pcs` |

---

## 5. Section `products` di CMS

Halaman dirender dari `sections[]`. Field-nya **`key`**, bukan `type`.

```json
{
  "key": "products",
  "title": "Produk Unggulan",
  "subtitle": "Cetak satuan atau partai besar, harga transparan",
  "payload": { "limit": 12, "cta_label": "Lihat Semua Produk", "cta_url": "/produk" }
}
```

Data produk tetap dari `GET /services?featured=1`; section hanya mengatur judul, batas jumlah, dan CTA.
Section ini hanya dirender bila `services.layout !== "accordion"`.

---

## 6. Tugas — `portal-landing-page` (CMS)

Path relatif `src/`.

1. **`types/cms.types.ts`** — `ServiceItem`: tambah `price: number | null; price_note: string | null;` dan ubah `body: string` → `body: string | null`.
2. **`features/services/schemas/service.schemas.ts`** — `ServiceFormValues`: tambah `price: number | null; price_note: string;`.
3. **`features/services/components/service-form-dialog.tsx`**
   - `buildServiceFormData()`: kirim `price` dan `price_note` dengan **`appendOptionalText`** (bukan `appendNumber`/`appendText`). Kalau pakai helper biasa, nilai kosong tidak terkirim sehingga admin tidak bisa mengosongkan harga.
   - Tambah field setelah `short_desc`:
     ```tsx
     <FormInputNumber name="price" label="Harga" leftAddon="Rp" thousandSeparator min={0}
       helperText="Kosongkan bila tanpa harga." />
     <FormInput name="price_note" label="Keterangan Harga" placeholder="/pcs" maxLength={30} />
     ```
   - `defaultValues` / `reset` saat edit: `price: service?.price ?? null`, `price_note: service?.price_note ?? ""`.
4. **`features/services/actions/services.actions.ts`** — hapus `body` dari guard wajib (`if (!name || !serviceTypeId || !shortDesc || !body)`).
5. **`features/services/components/service-columns.tsx`** — tambah kolom Harga: `Intl.NumberFormat("id-ID", …)`, `—` bila null, suffix `price_note`.
6. **Settings `services.layout`**
   - `features/settings/actions/settings.actions.ts` — tambah `{ group: "services", key: "layout", value: values.services_layout }`.
   - `features/settings/schemas/settings.schema.ts` — `services_layout: "accordion" | "carousel" | "grid"`, default `accordion`.
   - `features/settings/components/settings-display-tab.tsx` (baru, atau numpang di tab Modules) — `FormSelect` dengan 3 opsi.
   - `features/settings/components/settings-form.tsx` — daftarkan tab baru.

Tidak perlu menyentuh repository / table / halaman — semuanya passthrough.

---

## 7. Tugas — `landing-page` (publik)

Path relatif `src/`.

### 7a. Wajib lebih dulu: daftarkan tenant Miset
- `types/site.types.ts` — tambah `"miset"` ke `TenantSlug`.
- `config/sites.ts` — tambah entri `miset`: slug, apiKey, name, shortName, brand color, modules.

> **Kalau dilewat:** `resolveSiteConfig()` fallback diam-diam ke KSP. Warna brand, nama, footer, nomor WhatsApp, dan module flag jadi milik KSP **tanpa error apa pun**.

- Env deploy: `NEXT_PUBLIC_SITE_KEY`, `NEXT_PUBLIC_API_URL`.

### 7b. Komponen
- `features/services/components/product-card.tsx` — kartu: gambar `aspect-4/3`, nama, harga + `price_note`. Gaya mengikuti kartu yang ada: `rounded-2xl border border-slate-200/90 shadow-xs hover:shadow-lg`.
  **Daur ulang** `features/services/components/service-card.tsx` — sudah punya gambar + judul + CTA, dan saat ini orphan (hanya dipakai `services-preview.tsx`, yang juga tidak dipakai). Untuk varian produk, hapus CTA "Detail & Syarat"/"Ajukan" (itu alur pinjaman KSP).
- `features/services/components/product-carousel.tsx` — bungkus `components/ui/carousel.tsx` (embla sudah terpasang): `CarouselItem className="basis-1/2 md:basis-1/3 lg:basis-1/4 pl-4"`.
- `features/services/components/product-grid.tsx` — `grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6`.
- Placeholder bila `thumbnail_url` null: blok `bg-slate-100` + ikon dari `service_type.icon` (pakai `DynamicIcon` yang sudah ada).

### 7c. Data & wiring
- `features/services/services/services.queries.ts:29` — `getServices` sekarang hanya mengirim `service_type`; tambahkan parameter `featured`. Tetap pakai `"use cache"` + `CACHE_TAGS.services(siteKey)`.
- `app/(public)/page.tsx` — render carousel/grid produk unggulan bila `services.layout !== "accordion"`.
- `app/(public)/produk/page.tsx` + `features/services/components/products-accordion-section.tsx` — cabang: `accordion` → komponen sekarang; `carousel`/`grid` → `ProductGrid`. Judul jangan hardcode "Daftar Produk Simpanan & Pinjaman"; ambil dari CMS page (`page.title`/hero) dengan fallback umum.
- `features/home/components/section-renderer.tsx` — tambah `"products"` ke `SUPPORTED_SECTION_KEYS` + satu `case "products"`.
- Opsional: `lib/seo-jsonld.ts:204` — tambah `offers: { price, priceCurrency: "IDR" }` bila `price` ada.

---

## 8. Kode yang masih KSP-only — jangan dipakai untuk Miset

| File | Masalah |
|---|---|
| `features/home/components/featured-card.tsx` | klasifikasi `simpanan`/`pinjaman` dari judul + ikon bank + label "Kategori 01" |
| `features/services/components/product-accordion-body.tsx` | memecah `body` per newline jadi bullet |
| `features/services/components/service-card.tsx` | CTA "Detail & Syarat"/"Ajukan" → `/kontak` |
| `features/services/components/products-accordion-section.tsx` | judul & deskripsi default hardcode KSP |
| `lib/seo-jsonld.ts` | JSON-LD `Service` tanpa `offers` |

Juga: `services-preview.tsx` saat ini tidak diimpor di mana pun — aman dihapus/diganti.

---

## 9. Urutan kerja

1. Backend deploy (kolom harga + setting `services.layout` + OpenAPI).
2. Portal: 5 file service + tab settings → isi 8 produk Miset (foto, harga, `is_featured`).
3. Landing: daftarkan tenant `miset` → komponen kartu/carousel/grid → cabang `services.layout`.
4. Verifikasi silang.

## 10. Checklist terima

- [ ] KSP: `GET /services` tanpa `price`; beranda dan `/produk` tidak berubah.
- [ ] Miset: beranda carousel produk unggulan; `/produk` grid semua produk; harga + `price_note` tampil.
- [ ] Produk tanpa foto → kartu placeholder, bukan baris teks.
- [ ] Admin simpan layanan tanpa `body` + dengan `price` → berhasil.
- [ ] Admin mengosongkan `price` (kirim `""`) → tersimpan null.
- [ ] Ubah `services.layout` di portal memengaruhi beranda dan `/produk`.
- [ ] Tenant Miset tidak memakai brand/nomor KSP.

---

## 11. Media gallery (gambar di storage)

Backend sudah menyediakan 3 endpoint admin (sudah live):

| Method | Path | Fungsi |
|---|---|---|
| `GET` | `/api/v1/admin/media` | daftar gambar + dipakai di mana |
| `POST` | `/api/v1/admin/media/replace` | ganti gambar: simpan path baru + update referensi konten (multipart: `path`, `file`) |
| `DELETE` | `/api/v1/admin/media` | hapus file (JSON: `path`, `force`) |

Query `GET`: `?unused=1` (belum dipakai), `?q=` (cari path), `?dir=services/ksp` (prefix folder).
Semua hanya menyentuh file milik site aktif (`X-Site-Key`) atau yang direferensikan konten site tersebut.

Contoh item:

```json
{
  "path": "services/ksp/kantorpusat.webp",
  "url": "http://43.157.197.42:8088/storage/services/ksp/kantorpusat.webp?v=1763000000",
  "size": 48213,
  "modified_at": "2026-11-24T04:50:00+00:00",
  "owner_site": "ksp",
  "usage": [
    { "type": "page_section", "id": 12, "label": "Beranda / hero", "field": "payload" },
    { "type": "service", "id": 5, "label": "Simpanan Pendidikan", "field": "thumbnail_path" }
  ]
}
```

Aturan:
- `DELETE` mengembalikan **409** bila gambar masih dipakai, dengan `data.usage` berisi daftar pemakai. Kirim `force: true` untuk tetap menghapus (referensi jadi gambar rusak — tampilkan peringatan di UI).
- `replace` menyimpan file sebagai **path baru** lalu memperbarui semua referensi konten site itu (services, portfolios, branches, business_units, articles, partners, testimonials, page_sections termasuk payload, dan settings) ke path baru. Respons berisi `data.path` (baru), `data.previous_path`, dan `data.references_updated`. File lama dihapus bila tidak lagi dipakai site mana pun.
- Karena path berubah, ekstensi **boleh berbeda** dan URL ikut baru → cache otomatis segar. Selalu pakai `data.url` dari respons, jangan menyusun URL sendiri.
- URL di list diberi cache buster `?v=<mtime>`; setelah replace/delete, refetch list.
- `usage.type`: `service`, `portfolio`, `branch`, `business_unit`, `article`, `partner`, `testimonial`, `page_section`, `setting`.

Tugas portal (halaman `/media`):
1. `features/media/api/media.api.ts` — `listMedia({unused,q,dir})`, `replaceMedia(path, file)` (FormData), `deleteMedia(path, force)`.
2. Halaman grid: thumbnail (`object-cover`, `rounded-xl`), path kecil, badge jumlah pemakaian, tombol **Ganti** (file picker → replace) dan **Hapus** (confirm; kalau 409 tampilkan daftar `usage` + opsi force).
3. Toolbar: search, toggle "hanya yang belum dipakai", filter folder.
4. Setelah replace/delete → invalidasi query media **dan** query konten terkait (service/pages/portfolio/…) karena gambarnya berubah.

Landing page tidak perlu perubahan: `url` yang dipakai sudah mengandung `?v=`, jadi gambar baru langsung terlihat.
