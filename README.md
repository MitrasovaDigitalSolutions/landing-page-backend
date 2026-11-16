# Mitrasova Centralized Headless Multi-Tenant CMS Backend

Backend CMS berbasis Laravel 12 untuk mensuplai data konten ke berbagai landing page Next.js unit usaha (KSP, Konveksi, Teknologi, Percetakan, Transportasi, Bengkel Las) secara terpusat.

---

## 1. Spesifikasi Teknis
* **Framework:** Laravel 12 (PHP 8.2+)
* **Database:** PostgreSQL (Production) / SQLite (Local Dev & Testing)
* **Arsitektur:** Headless Multi-Tenant REST API
* **Tenancy Identifier:** Header `X-Site-Key` (menerima `api_key` atau `slug` unit usaha)
* **Dokumentasi API:** OpenAPI 3.0.3 & Interactive Swagger UI di `/docs`
* **File Upload Limit:** Validasi Laravel maks 10MB (10240 KB) per file

---

## 2. Unit Usaha Terdaftar (Default Seeded)

| Unit Usaha | Slug | API Key Header (`X-Site-Key`) |
| :--- | :--- | :--- |
| **KSP Mitra Sejati Persada** | `ksp` | `key-ksp-12345` |
| **Mitrasova Konveksi & Garment** | `konveksi` | `key-konveksi-12345` |
| **Mitrasova Digital Solutions** | `teknologi` | `key-teknologi-12345` |
| **Mitrasova Printing** | `percetakan` | `key-percetakan-12345` |
| **Mitrasova Trans & Logistics** | `transportasi` | `key-trans-12345` |
| **Mitrasova Las & Interior Estetika** | `bengkel-las` | `key-las-12345` |

*Catatan: Frontend dapat mengirim `api_key` atau `slug` pada header `X-Site-Key`.*

---

## 3. Menjalankan Backend Secara Lokal

```bash
# 1. Jalankan migrasi & seeder
php artisan migrate:fresh --seed

# 2. Buat symlink storage untuk akses file media publik
php artisan storage:link

# 3. Jalankan server lokal
php artisan serve
```

Server aktif di `http://localhost:8000`.

---

## 4. Dokumentasi OpenAPI & Swagger UI

* **Interactive Swagger UI:** Buka di browser: `http://localhost:8000/docs`
* **OpenAPI Raw JSON:** `http://localhost:8000/openapi.json`
* Terdapat tombol **"Authorize"** di Swagger UI untuk memasukkan header `X-Site-Key` (misal: `key-ksp-12345` atau `ksp`).

---

## 5. Ringkasan Endpoint API (`/api/v1`)

Semua endpoint wajib menyertakan header `X-Site-Key: <api_key|slug>`:

| Endpoint | Method | Deskripsi |
| :--- | :--- | :--- |
| `/api/v1/settings` | `GET` | Pengaturan situs, logo, kontak, sosmed, meta SEO |
| `/api/v1/pages/{slug}` | `GET` | Detail halaman dan section landing page (hero, visi misi, cta) |
| `/api/v1/service-types` | `GET` | Master kategori/tipe layanan |
| `/api/v1/services` | `GET` | List produk/layanan (support filter `?service_type=...`) |
| `/api/v1/services/{slug}` | `GET` | Detail satu layanan/produk |
| `/api/v1/portfolios` | `GET` | List portofolio/galeri hasil kerja |
| `/api/v1/portfolios/{slug}` | `GET` | Detail portofolio dan list foto galeri |
| `/api/v1/article-categories`| `GET` | Master kategori artikel/berita |
| `/api/v1/articles` | `GET` | List artikel terbit (support pagination & filter kategori) |
| `/api/v1/articles/{slug}` | `GET` | Detail artikel |
| `/api/v1/faqs` | `GET` | List FAQ dan kategorinya |
| `/api/v1/testimonials` | `GET` | List testimoni/ulasan nasabah/klien |
| `/api/v1/partners` | `GET` | List logo partner/klien |
| `/api/v1/inquiries` | `POST` | Kirim pesan kontak / permintaan penawaran (dukung upload lampiran maks 10MB) |

### 5.1 Autentikasi Admin

Semua endpoint `/api/v1/admin/*` membutuhkan header `Authorization: Bearer <token>` (Sanctum) dan `X-Site-Key` (kecuali grup Sites/Users yang superadmin-only).

| Endpoint | Method | Fungsi |
| :--- | :--- | :--- |
| `/api/v1/auth/login` | `POST` | Login admin, mengembalikan token |
| `/api/v1/auth/me` | `GET` | Profil admin yang sedang login |
| `/api/v1/auth/logout` | `POST` | Revoke token aktif |

### 5.2 Endpoint Admin CMS

| Grup | Endpoint | Fungsi |
| :--- | :--- | :--- |
| Settings | `GET/POST /api/v1/admin/settings` | Baca & bulk-upsert setting (grouped) |
| Pages | `GET/POST /api/v1/admin/pages`, `GET/PUT/DELETE /api/v1/admin/pages/{id}` | CRUD halaman |
| Sections | `POST /api/v1/admin/pages/{pageId}/sections`, `PUT/DELETE /api/v1/admin/sections/{id}` | CRUD section (upload gambar) |
| Service Types | `GET/POST /api/v1/admin/service-types`, `PUT/DELETE /api/v1/admin/service-types/{id}` | Master tipe layanan |
| Services | `GET/POST /api/v1/admin/services`, `GET/PUT/DELETE /api/v1/admin/services/{id}` | CRUD layanan + icon & thumbnail |
| Portfolios | `GET/POST /api/v1/admin/portfolios`, `GET/PUT/DELETE /api/v1/admin/portfolios/{id}` | CRUD portofolio + gallery |
| Article Categories | `GET/POST /api/v1/admin/article-categories`, `PUT/DELETE /api/v1/admin/article-categories/{id}` | Master kategori artikel |
| Articles | `GET/POST /api/v1/admin/articles`, `GET/PUT/DELETE /api/v1/admin/articles/{id}` | CRUD artikel (draft/published/archived) |
| FAQ Categories | `GET/POST /api/v1/admin/faq-categories`, `PUT/DELETE /api/v1/admin/faq-categories/{id}` | Master kategori FAQ |
| FAQs | `GET/POST /api/v1/admin/faqs`, `PUT/DELETE /api/v1/admin/faqs/{id}` | CRUD FAQ |
| Testimonials | `GET/POST /api/v1/admin/testimonials`, `PUT/DELETE /api/v1/admin/testimonials/{id}` | CRUD testimoni |
| Partners | `GET/POST /api/v1/admin/partners`, `PUT/DELETE /api/v1/admin/partners/{id}` | CRUD partner + logo |
| Inquiries | `GET /api/v1/admin/inquiries?status=`, `GET/DELETE /api/v1/admin/inquiries/{id}`, `PATCH /api/v1/admin/inquiries/{id}/status` | Inbox & status inquiry |
| Sites *(superadmin)* | `GET/POST /api/v1/admin/sites`, `PUT/DELETE /api/v1/admin/sites/{id}` | CRUD unit usaha |
| Users *(superadmin)* | `GET/POST /api/v1/admin/users`, `PUT /api/v1/admin/users/{id}`, `POST/DELETE /api/v1/admin/sites/{siteId}/users[/{userId}]` | CRUD user & assignment role per site |

---

## 6. Konfigurasi Upload Max Filesize di Server (VPS Tencent Cloud)

Untuk memastikan upload file gambar/dokumen hingga 10MB tidak ditolak oleh web server:

### A. PHP Configuration (`php.ini` atau `/etc/php/8.2/fpm/php.ini`)
```ini
upload_max_filesize = 10M
post_max_size = 12M
memory_limit = 256M
max_execution_time = 60
```

### B. Nginx Server Block (`/etc/nginx/sites-available/...`)
```nginx
server {
    ...
    client_max_body_size 12M;
    ...
}
```

---

## 7. Menjalankan Automated Test

```bash
php artisan test
```
Seluruh 23 test (152 assertions) menguji isolasi tenancy, validasi header `X-Site-Key`, CRUD query scope, validasi form inquiries & upload file, serta route Swagger UI.
