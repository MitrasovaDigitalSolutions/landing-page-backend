# Baseline Rencana & Panduan Integrasi Frontend (Next.js)

Dokumen ini adalah acuan kerja (baseline) untuk pengembangan frontend landing page unit-unit usaha Mitrasova menggunakan **Next.js** (App Router) yang terhubung ke **Centralized Headless Multi-Tenant CMS Backend**.

---

## 1. Arsitektur & Lingkungan Backend

* **Backend Environment:** Live di VPS Tencent Cloud
* **API Base URL:** `http://43.157.197.42:8088/api/v1`
* **Dokumentasi Interaktif (Swagger UI):** [http://43.157.197.42:8088/docs](http://43.157.197.42:8088/docs)
* **OpenAPI Raw Specification:** [http://43.157.197.42:8088/openapi.json](http://43.157.197.42:8088/openapi.json)
* **Backend Repository:** [https://github.com/MitrasovaDigitalSolutions/landing-page-backend](https://github.com/MitrasovaDigitalSolutions/landing-page-backend)

---

## 2. Mekanisme Multi-Tenancy (1 Backend, Multi-Site)

Setiap request dari Next.js ke backend **wajib** menyertakan header `X-Site-Key`. Backend akan otomatis memfilter seluruh data (pengaturan, halaman, layanan, portofolio, artikel, FAQ, testimoni) sesuai unit usaha yang diminta.

### Daftar Tenant / Unit Usaha:

| Nama Unit Usaha | Slug | API Key Header (`X-Site-Key`) | Kebutuhan Modul Utama |
| :--- | :--- | :--- | :--- |
| **KSP Mitra Sejati Persada** | `ksp` | `key-ksp-12345` | Simpanan, Pinjaman, Kalkulator/Simulasi, FAQ, Visi Misi |
| **Mitrasova Konveksi & Garment** | `konveksi` | `key-konveksi-12345` | Katalog Seragam/Polo/Jaket, Portofolio Hasil Jadi, Form Minta Penawaran + Upload Desain |
| **Mitrasova Digital Solutions** | `teknologi` | `key-teknologi-12345` | Layanan Web/App/ERP, Portofolio Proyek Klien, Partner, Testimoni |
| **Mitrasova Printing** | `percetakan` | `key-percetakan-12345` | Brosur/Packaging/Offset, Pricelist/Spesifikasi, Form Custom Order |
| **Mitrasova Trans & Logistics** | `transportasi` | `key-trans-12345` | Sewa Armada, Antar-Jemput, Rute Logistik, Kontak Booking |
| **Mitrasova Las & Interior** | `bengkel-las` | `key-las-12345` | Kanopi, Pagar Laser Cut, Furniture Industrial, Portofolio Foto Real |

> **Catatan Developer:** Pada Next.js, cukup set variabel environment:
> ```env
> NEXT_PUBLIC_API_URL=http://43.157.197.42:8088/api/v1
> NEXT_PUBLIC_SITE_KEY=key-ksp-12345
> ```
> *(Bisa menggunakan API Key seperti `key-ksp-12345` atau langsung menggunakan slug seperti `ksp`)*.

---

## 3. TypeScript Type Contracts (`types/api.ts`)

Salin interface ini ke dalam project Next.js Anda (misal di `@/types/api.ts`):

```typescript
// Wrapper Standar API Response
export interface ApiResponse<T> {
  success: boolean;
  message?: string;
  data: T;
}

export interface PaginatedResponse<T> {
  success: boolean;
  data: T[];
  meta: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
}

// 1. Pengaturan Website (Settings)
export interface SiteSettings {
  general?: {
    site_title?: string;
    tagline?: string;
    brand_color?: string;
    meta_keywords?: string;
    logo_path?: string;
  };
  contact?: {
    phone?: string;
    email?: string;
    address?: string;
    whatsapp?: string;
    maps_embed_url?: string;
  };
  social?: {
    instagram?: string;
    facebook?: string;
    linkedin?: string;
    tiktok?: string;
  };
  [group: string]: any;
}

// 2. Sections Halaman (Hero, CTA, dsb)
export interface PageSection {
  id: number;
  key: string;            // 'hero', 'vision_mission', 'cta', dsb
  title: string | null;
  subtitle: string | null;
  body: string | null;
  payload: Record<string, any> | null;
  image_url: string | null;
  order: number;
}

export interface PageDetail {
  id: number;
  slug: string;           // 'home', 'about', dsb
  title: string;
  meta_title: string | null;
  meta_desc: string | null;
  sections: PageSection[];
}

// 3. Master Tipe Layanan & Layanan
export interface ServiceType {
  id: number;
  name: string;           // e.g. "Simpanan", "Pinjaman", "Konveksi Kaos"
  slug: string;
  description: string | null;
  icon: string | null;
  order: number;
  services_count?: number;
}

export interface Service {
  id: number;
  service_type_id: number;
  name: string;
  slug: string;
  short_desc: string;
  body: string;
  icon_url: string | null;
  thumbnail_url: string | null;
  features: string[] | null;  // Array poin keunggulan produk/layanan
  is_featured: boolean;
  order: number;
  service_type?: ServiceType;
}

// 4. Portofolio & Galeri
export interface Portfolio {
  id: number;
  service_type_id: number | null;
  title: string;
  slug: string;
  client_name: string | null;
  completion_date: string | null;
  description: string | null;
  thumbnail_url: string;
  gallery_images: string[] | null; // Array URL foto tambahan
  is_featured: boolean;
  order: number;
  service_type?: ServiceType | null;
}

// 5. Artikel & Kategori Berita
export interface ArticleCategory {
  id: number;
  name: string;
  slug: string;
  description: string | null;
}

export interface Article {
  id: number;
  article_category_id: number | null;
  title: string;
  slug: string;
  excerpt: string | null;
  body: string;
  thumbnail_url: string | null;
  published_at: string | null;
  category?: ArticleCategory | null;
}

// 6. FAQ, Testimoni, Partner
export interface FaqCategory {
  id: number;
  name: string;
  slug: string;
}

export interface Faq {
  id: number;
  faq_category_id: number | null;
  question: string;
  answer: string;
  order: number;
  category?: FaqCategory | null;
}

export interface Testimonial {
  id: number;
  name: string;
  title: string | null;       // Jabatan / Usaha nasabah
  avatar_url: string | null;
  message: string;
  rating: number;             // 1 sampai 5
  order: number;
}

export interface Partner {
  id: number;
  name: string;
  logo_url: string;
  website_url: string | null;
  order: number;
}

// 7. Request Payload Form Penawaran / Hubungi Kami
export interface InquiryPayload {
  name: string;
  phone: string;
  email?: string;
  subject?: string;
  message: string;
  attachment?: File;          // Maksimal 10MB (PDF/JPG/PNG/DOC)
}
```

---

## 4. Helper API Fetcher di Next.js (`lib/api.ts`)

Gunakan pattern fetcher dengan dukungan **ISR (Incremental Static Regeneration)** bawaan Next.js App Router:

```typescript
// lib/api.ts
import { ApiResponse, PaginatedResponse } from '@/types/api';

const API_BASE_URL = process.env.NEXT_PUBLIC_API_URL || 'http://43.157.197.42:8088/api/v1';
const SITE_KEY = process.env.NEXT_PUBLIC_SITE_KEY || 'key-ksp-12345';

export async function fetchApi<T>(
  endpoint: string,
  options?: RequestInit,
  revalidate: number = 60 // Default ISR revalidate setiap 60 detik
): Promise<T> {
  const url = `${API_BASE_URL}${endpoint}`;
  
  const res = await fetch(url, {
    ...options,
    headers: {
      'Accept': 'application/json',
      'X-Site-Key': SITE_KEY,
      ...options?.headers,
    },
    next: { revalidate },
  });

  if (!res.ok) {
    throw new Error(`API Request Failed: ${res.status} ${res.statusText}`);
  }

  const json: ApiResponse<T> = await res.json();
  return json.data;
}

// Helper khusus kirim form inquiry (Mendukung File Upload Maks 10MB)
export async function submitInquiry(formData: FormData) {
  const url = `${API_BASE_URL}/inquiries`;
  
  const res = await fetch(url, {
    method: 'POST',
    headers: {
      'Accept': 'application/json',
      'X-Site-Key': SITE_KEY,
      // Jangan set 'Content-Type': browser otomatis set boundary multipart/form-data
    },
    body: formData,
  });

  const json = await res.json();
  if (!res.ok) {
    throw { status: res.status, errors: json.errors || json.message };
  }

  return json;
}
```

---

## 5. Ringkasan Endpoint & Contoh Penggunaan di Halaman Next.js

### A. Pengaturan Situs & Kontak Navbar/Footer
* **Endpoint:** `GET /settings`
* **Contoh:** Ambil logo, nomor WhatsApp, alamat, dan link sosmed di `layout.tsx`:
```typescript
import { fetchApi } from '@/lib/api';
import { SiteSettings } from '@/types/api';

export default async function RootLayout() {
  const settings = await fetchApi<SiteSettings>('/settings');
  const waNumber = settings.contact?.whatsapp;
  ...
}
```

### B. Halaman Utama (Hero Banner & Section)
* **Endpoint:** `GET /pages/home`
* **Data:** Mengembalikan detail halaman berserta array `sections` (terurut berdasarkan field `order`).
* **Contoh mapping section:**
  * `section.key === 'hero'` -> Render komponen `<HeroBanner data={section} />`
  * `section.key === 'vision_mission'` -> Render komponen `<VisionMission data={section} />`
  * `section.key === 'cta'` -> Render tombol WhatsApp / Konsultasi

### C. Daftar Produk & Layanan
* **Endpoint:** 
  * `GET /service-types` -> List tab filter (misal: "Simpanan", "Pinjaman")
  * `GET /services` -> List semua produk (support query param `?service_type=slug-tipe`)
  * `GET /services/{slug}` -> Halaman detail produk (fitur, deskripsi lengkap)

### D. Portofolio Proyek (Penting untuk Konveksi, Bengkel Las, Teknologi)
* **Endpoint:** 
  * `GET /portfolios` -> Grid portofolio dengan gambar thumbnail
  * `GET /portfolios/{slug}` -> Detail proyek lengkap dengan `gallery_images`

### E. FAQ, Testimoni & Partner
* **Endpoint:** 
  * `GET /faqs` -> List FAQ (bisa di-group per kategori FAQ)
  * `GET /testimonials` -> List slider ulasan klien
  * `GET /partners` -> List logo klien/partner perusahaan

### F. Form Permintaan Penawaran / Kontak Masuk
* **Endpoint:** `POST /inquiries`
* **Field:**
  * `name` (string, wajib)
  * `phone` (string, wajib)
  * `email` (string, opsional)
  * `subject` (string, opsional)
  * `message` (string, wajib)
  * `attachment` (file, opsional, maks **10MB**)

---

## 6. Checklist Langkah Pengerjaan Frontend

1. **Setup Project Next.js (App Router):**
   * Rekomendasi: Next.js 14/15, Tailwind CSS, Lucide Icons, Shadcn UI (opsional).
2. **Environment Variables:**
   * Buat file `.env.local` dan isi `NEXT_PUBLIC_API_URL` dan `NEXT_PUBLIC_SITE_KEY`.
3. **Setup Types & Client:**
   * Masukkan `types/api.ts` dan `lib/api.ts` dari panduan ini.
4. **Testing Koneksi Awal:**
   * Test fetch data dari `GET /settings` dan `GET /services`.
5. **Implementasi Komponen UI:**
   * Navbar & Footer dinamis (baca data dari `settings`).
   * Hero section & CTA (baca data dari `/pages/home`).
   * Service Cards / Tabs (baca dari `/services`).
   * Form Inquiry / Penawaran (integrasikan dengan `POST /inquiries` + toast notifikasi sukses).
   * Accordion FAQ & Testimonial Carousel.
6. **Validasi & Error Handling:**
   * Handle state jika data kosong (empty state).
   * Tampilkan pesan validasi jika input form tidak lengkap (HTTP 422).

---

## 7. Catatan Teknis Tambahan

* **CORS:** Backend sudah mengaktifkan middleware CORS terbuka untuk domain frontend.
* **Image Assets:** Semua thumbnail/icon yang di-upload dari backend otomatis mengembalikan URL absolut siap pakai (contoh: `http://43.157.197.42:8088/storage/...`).
* **Next/Image Domain:** Tambahkan hostname backend di `next.config.js`:
  ```javascript
  /** @type {import('next').NextConfig} */
  const nextConfig = {
    images: {
      remotePatterns: [
        {
          protocol: 'http',
          hostname: '43.157.197.42',
          port: '8088',
          pathname: '/storage/**',
        },
      ],
    },
  };
  module.exports = nextConfig;
  ```

---

## 8. Autentikasi Admin & Endpoint CRUD CMS

Bagian ini untuk membangun **dashboard admin** (terpisah dari landing page publik). Frontend publik cukup memakai endpoint read-only di bagian 5; mutasi konten dilakukan oleh admin yang login.

### 8.1 Alur Login

```
POST /api/v1/auth/login   { email, password }
   -> 200 { data: { token, user: { id, name, email, is_superadmin, sites[] } } }
```

Simpan `token`, lalu kirim di setiap request admin:

```http
Authorization: Bearer <token>
X-Site-Key: ksp
```

* `X-Site-Key` menentukan **tenant mana** yang sedang dikelola.
* `GET /api/v1/auth/me` untuk restore sesi saat halaman di-refresh.
* `POST /api/v1/auth/logout` untuk revoke token aktif.
* Token tidak punya masa berlaku otomatis — revoke manual saat logout. Simpan di `httpOnly cookie` (disarankan) atau `secure storage`, jangan di `localStorage` polos.

### 8.2 Peran & Hak Akses

| Peran | Cara ditentukan | Bisa apa |
| :--- | :--- | :--- |
| **superadmin** | `users.is_superadmin = true` | Semua tenant, plus CRUD site & user (`/admin/sites`, `/admin/users`) |
| **site admin / editor** | baris di tabel pivot `site_user` (`role` = `admin`/`editor`) | Hanya tenant yang ter-assign |

* Tanpa token ? `401`.
* Token valid tapi tidak punya akses ke `X-Site-Key` yang dikirim ? `403`.
* Endpoint global (`/admin/sites*`, `/admin/users*`) hanya untuk superadmin ? `403` untuk site admin.
* `X-Site-Key` tidak dikirim / tidak valid pada endpoint tenant ? `400` / `404`.

### 8.3 Peta Endpoint Admin

Semua di bawah prefix `/api/v1/admin` dan butuh `Authorization` + `X-Site-Key` (kecuali grup **Sites/Users** yang superadmin-only, tanpa `X-Site-Key`).

| Grup | Endpoint | Fungsi |
| :--- | :--- | :--- |
| **Settings** | `GET/POST /settings` | Baca & bulk-upsert key-value (group: general, contact, social, seo) |
| **Pages** | `GET/POST /pages`, `GET/PUT/DELETE /pages/{id}` | CRUD halaman |
| | `POST /pages/{pageId}/sections`, `PUT/DELETE /sections/{id}` | CRUD section (hero, visi-misi, cta) |
| **Services** | `GET/POST /service-types`, `PUT/DELETE /service-types/{id}` | CRUD master tipe layanan |
| | `GET/POST /services`, `GET/PUT/DELETE /services/{id}` | CRUD layanan/produk + upload icon & thumbnail |
| **Portfolios** | `GET/POST /portfolios`, `GET/PUT/DELETE /portfolios/{id}` | CRUD portofolio + thumbnail + gallery |
| **Articles** | `GET/POST /article-categories`, `PUT/DELETE /article-categories/{id}` | CRUD kategori artikel |
| | `GET/POST /articles`, `GET/PUT/DELETE /articles/{id}` | CRUD artikel (draft/published/archived) |
| **FAQs** | `GET/POST /faq-categories`, `PUT/DELETE /faq-categories/{id}` | CRUD kategori FAQ |
| | `GET/POST /faqs`, `PUT/DELETE /faqs/{id}` | CRUD FAQ |
| **Showcase** | `GET/POST /testimonials`, `PUT/DELETE /testimonials/{id}` | CRUD testimoni + rating 1–5 |
| | `GET/POST /partners`, `PUT/DELETE /partners/{id}` | CRUD partner + logo |
| **Inquiries** | `GET /inquiries?status=`, `GET /inquiries/{id}` | Inbox pesan masuk |
| | `PATCH /inquiries/{id}/status` | Ubah status (`new`/`contacted`/`closed`) + catatan internal |
| | `DELETE /inquiries/{id}` | Hapus pesan |
| **Sites** *(superadmin)* | `GET/POST /sites`, `PUT/DELETE /sites/{id}` | CRUD unit usaha; hapus ditolak `422` bila masih ada konten |
| **Users** *(superadmin)* | `GET/POST /users`, `PUT /users/{id}` | CRUD user admin |
| | `POST /sites/{siteId}/users`, `DELETE /sites/{siteId}/users/{userId}` | Assign/lepas user ke site + role |

### 8.4 Upload File & Batas Ukuran

* Batas **10 MB per file** (`max:10240`) untuk seluruh upload: gambar section, icon, thumbnail, gallery, avatar testimoni, logo partner, dan lampiran inquiry.
* Field yang berisi file **wajib** dikirim sebagai `multipart/form-data`; jangan set header `Content-Type` manual di browser (biarkan boundary otomatis).
* Saat request berisi file, jangan kirim `Content-Type: application/json` — gunakan `FormData`.
* Semua path file yang tersimpan dikembalikan sebagai URL absolut (`.../storage/...`), jadi frontend tidak perlu menyusun URL sendiri.

### 8.5 Helper Admin di Next.js

```typescript
// lib/adminApi.ts — khusus dashboard admin, tidak dipakai landing page publik
const API = process.env.NEXT_PUBLIC_API_URL!;

export async function adminFetch<T>(
  path: string,
  init: RequestInit & { siteKey?: string } = {}
): Promise<T> {
  const { siteKey, ...rest } = init;
  const res = await fetch(`${API}${path}`, {
    ...rest,
    headers: {
      Accept: 'application/json',
      Authorization: `Bearer ${getToken()}`,
      ...(siteKey ? { 'X-Site-Key': siteKey } : {}),
      ...(rest.body instanceof FormData ? {} : { 'Content-Type': 'application/json' }),
      ...rest.headers,
    },
    cache: 'no-store', // dashboard tidak boleh pakai ISR cache
  });

  const json = await res.json();
  if (!res.ok) throw { status: res.status, ...json };
  return json.data;
}

export function getToken() { /* baca dari httpOnly cookie di server, atau memory store */ }
```

Catatan penting: **landing page publik memakai `revalidate` (ISR)** di `lib/api.ts`, sedangkan **dashboard admin wajib `cache: 'no-store'`** agar perubahan konten langsung terlihat.

### 8.6 Setelah Admin Simpan Konten

Karena halaman publik memakai ISR, perubahan dari admin paling cepat terlihat setelah `revalidate` habis (60 detik di contoh `lib/api.ts`). Kalau perlu instan, panggil revalidasi di server action Next.js setelah request admin sukses:

```typescript
import { revalidatePath } from 'next/cache';
// setelah POST/PUT berhasil:
revalidatePath('/', 'layout');
```

---

## 9. Konten KSP Mitra Sejati Persada (hasil migrasi situs lama)

Konten tenant `ksp` sudah bukan dummy lagi — diimpor dari `kspmitrasejatipersada.my.id` lewat perintah `php artisan cms:import-ksp` (idempotent, aman dijalankan ulang; tambahkan `--keep-extra` kalau tidak ingin konten dummy dihapus).

### 9.1 Dua endpoint baru (wajib dipakai halaman Cabang & Unit Usaha)

```http
GET /api/v1/branches           X-Site-Key: ksp
GET /api/v1/business-units     X-Site-Key: ksp
```

`GET /branches` ? kantor pusat selalu paling atas, lalu cabang berurutan:

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Kantor Pusat",
      "address": "Jl. Batujamus-Karanganyar Km.01, Plosorejo RT.02 RW.02, Kuto, Kerjo, Karanganyar",
      "phone": "(0271)-6493247",
      "is_primary": true,
      "images": ["branches/ksp/kantorpusat-C3OBD9Ew.webp"],
      "image_urls": ["http://43.157.197.42:8088/storage/branches/ksp/kantorpusat-C3OBD9Ew.webp"],
      "order": 0
    }
  ]
}
```

Pakai `image_urls` (sudah URL absolut) — jangan menyusun URL sendiri dari `images`.

`GET /business-units` ? 6 unit usaha. Field `url` dipakai untuk redirect:

```json
{
  "id": 1,
  "name": "Mitra Sejati Trans",
  "address": "Jl. Batujamus - Jambangan Km. 2, ... · 081329703060",
  "image_path": "business-units/ksp/mst-Cpk5JyTC.jpeg",
  "image_url": "http://43.157.197.42:8088/storage/business-units/ksp/mst-Cpk5JyTC.jpeg",
  "url": "https://trans.mitrasova.com",
  "order": 0
}
```

* `url` diambil dari `sites.domain` unit terkait, jadi begitu domain asli dipasang (superadmin: `PUT /api/v1/admin/sites/{id}` dengan `domain`), link ikut berubah tanpa deploy.
* `url` bisa `null` (contoh: Mitra Sejati Grosir belum punya site) ? render kartu tanpa link / non-aktif.
* Kalau mau link eksternal, bisa di-override per unit lewat `PUT /api/v1/admin/business-units/{id}`.

### 9.2 Data yang sekarang ada di tenant `ksp`

| Sumber | Endpoint | Isi |
| :--- | :--- | :--- |
| 2 tipe layanan, 7 produk | `/service-types`, `/services` | Simpanan (5: Pendidikan, Pendidikan Berjangka, Terpimpin, Mitra, Berjangka) & Pinjaman (2: Flat, Menurun). Semua syarat ada di `features[]` |
| 8 kantor | `/branches` | Kantor Pusat + 7 cabang (Kerjo, Mojogedang, Jambangan, Tasikmadu, Matesih, Jumapolo, Jatipuro) + foto |
| 6 unit usaha | `/business-units` | Trans, Grosir MSG, Percetakan, Estetika, Digital Solutions, Konveksi MISET |
| 8 halaman | `/pages/{slug}` | `home`, `profil`, `produk`, `cabang`, `unit-usaha`, `karir`, `berita`, `kontak` |
| Kontak & identitas | `/settings` | WA 6282247170756, telp (0271)-6493247, email ksumitrasejati2005@gmail.com, alamat Karanganyar, IG @kspmspersada, `general.logo_url` |

### 9.3 Struktur `page_sections.payload` untuk KSP

| Page | Section `key` | Isi payload |
| :--- | :--- | :--- |
| `home` | `hero` | `slides[]` (title, description, badge, image_path) + `stats[]` (label, value) |
| `home` | `about` | `pillars[]` (title, description) |
| `home` | `cta` | teks saja |
| `profil` | `hero`, `sejarah` (body 3 paragraf), `visi-misi` (`visi` + `misi[]`), `program` (`items[]` dengan `desc1`/`desc2`) | |
| `produk`, `cabang`, `unit-usaha`, `karir`, `berita`, `kontak` | `hero` (+ `cabang.hero.stats`, `karir.hero.image_path`, `karir.values.items[]`, `berita.instagram.posts[]`) | |

Catatan: `image_path` di dalam `payload` masih **path relatif** (contoh `hero/ksp/slide-1.webp`) — frontend cukup prefix `${NEXT_PUBLIC_API_URL}/storage/`.

### 9.4 Setelah migrate

```bash
php artisan cms:import-ksp     # jalankan sekali setelah container baru naik
```

Migration tabel baru (`branches`, `business_units`) jalan otomatis lewat entrypoint.
