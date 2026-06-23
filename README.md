# AcadCheck AI — Academic Document Assistant

**AcadCheck AI** adalah platform analisis dokumen akademik berbasis AI dan pelacakan revisi. Platform ini membantu mahasiswa, peneliti, dan akademisi dalam menganalisis artikel, proposal, dan laporan melalui tinjauan berbasis rubrik yang didukung AI, perbandingan versi, pemetaan komentar reviewer, serta rekomendasi jurnal.

---

## Fitur

### Manajemen Dokumen
- Unggah dokumen format PDF dan DOCX (maks 10 MB)
- Dikelompokkan berdasarkan tipe dokumen: **Artikel Ilmiah**, **Proposal Penelitian**, **Laporan Hasil**
- Cari, filter, dan kelola dokumen dari library terpusat
- Lacak status dokumen: `uploaded → analyzed → need_revision → revised → ready`

### Analisis Berbasis AI
- Tinjauan akademik berbasis rubrik menggunakan AI (API kompatibel OpenAI)
- Rubrik dapat dikonfigurasi per tipe dokumen dengan aspek berbobot
- Menghasilkan skor, temuan, rekomendasi, dan prioritas revisi
- Mendukung skala nilai 0–10 dan 0–100 secara otomatis
- Pemotongan dokumen besar dengan preservasi konteks

### Version Control & Perbandingan
- Unggah banyak revisi dengan penomoran versi
- Perbandingan versi berdampingan (side-by-side)
- Lacak riwayat revisi dengan catatan

### Pemetaan Komentar Reviewer
- Parsing catatan reviewer tidak terstruktur menjadi komentar terstruktur via AI
- Tetapkan tingkat prioritas: minor, major, critical
- Lacak status komentar: pending, in_progress, done, rejected_with_reason
- Draf respons penulis yang dihasilkan AI (bilingual: Inggris + Indonesia)
- Hasilkan PDF "Response to Reviewers"

### Rekomendasi Jurnal
- Database jurnal yang dapat dicari dengan tingkat SINTA, bidang ilmu, fokus & ruang lingkup
- Impor CSV untuk data jurnal massal
- Skor kelayakan otomatis (0–100) berdasarkan kelengkapan metadata
- Rekomendasi kecocokan jurnal berbasis AI per dokumen
- Ambang batas kelayakan minimal: 70 untuk rekomendasi AI

### Panel Admin
- Manajemen pengguna (aktif/nonaktifkan akun)
- Pengawasan dokumen seluruh pengguna
- Editor rubrik per tipe dokumen
- Manajemen database jurnal dengan CRUD dan impor

---

## Tech Stack

| Lapisan | Teknologi |
|---|---|
| **Backend** | Laravel 13.x (PHP ^8.3) |
| **API Auth** | Laravel Sanctum (token-based) |
| **Frontend** | Vanilla JavaScript (tanpa framework) |
| **CSS** | Tailwind CSS 4.x |
| **Build Tool** | Vite 8.x |
| **Database** | MySQL / MariaDB (via Laravel Eloquent) |
| **AI Provider** | API kompatibel OpenAI (OpenCode / Groq) |
| **PDF Generation** | DomPDF (barryvdh/laravel-dompdf) |
| **File Parsing** | smalot/pdfparser, phpoffice/phpword |
| **Queue / Cache** | Database driver |
| **Testing** | PHPUnit 12.x, Mockery |

---

## Arsitektur

### Pola Semi-SPA
AcadCheck menggunakan pendekatan **semi-SPA** (Single Page Application):
1. **Blade views** menyajikan kerangka HTML untuk setiap halaman
2. **Vanilla JavaScript** (`resources/js/app.js`) menangani semua perilaku dinamis
3. **JSON API** (`/api/*`) menyediakan data melalui panggilan `fetch()`
4. **localStorage** menyimpan token Sanctum di sisi klien

### Struktur Direktori
```
app/
├── Exceptions/              # Kelas exception kustom
├── Http/
│   ├── Controllers/
│   │   ├── Api/             # Controller JSON API
│   │   │   ├── Admin/       # Controller khusus admin
│   │   │   └── Traits/      # Trait ApiResponse
│   │   └── Web/             # Controller halaman Blade
│   └── Middleware/           # EnsureUserIsAdmin middleware
├── Models/                   # Model Eloquent (12 model)
├── Providers/                # Service provider
└── Services/                 # Lapisan logika bisnis (7 service)

resources/
├── views/
│   ├── layouts/              # Layout utama
│   ├── auth/                 # Halaman login & register
│   ├── dashboard/            # Dashboard pengguna & admin
│   ├── user/documents/       # Halaman manajemen dokumen
│   ├── user/articles/        # Ruang kerja pemetaan reviewer
│   ├── admin/                # Halaman manajemen admin
│   └── reports/              # Template PDF
└── js/app.js                 # Semua logika frontend (3338 baris)
```

### Pola Desain Utama
- **Service Layer**: Logika bisnis berada di `app/Services/`, bukan di controller
- **Respons Terstandarisasi**: Trait `ApiResponse` menyediakan bentuk JSON yang konsisten
- **Auth Sisi Klien**: Route web tidak memiliki middleware server; proteksi dilakukan JS via `requireUserSession()`
- **Penyimpanan Privat**: Dokumen disimpan di disk `local` (tidak dapat diakses publik)
- **Skor Otomatis**: Skor kelayakan jurnal otomatis dihitung saat penyimpanan via `JournalEligibilityService`

---

## Services Overview

| Service | Tanggung Jawab |
|---|---|
| **AiAnalysisService** | Mesin analisis AI inti — membangun prompt, mem-parsing respons JSON AI, menormalisasi skor |
| **AiProviderService** | HTTP client untuk API kompatibel OpenAI dengan penanganan error dan percobaan ulang |
| **AuthorResponseGeneratorService** | Menghasilkan draf respons penulis terhadap komentar reviewer berbasis AI |
| **DocumentVersionService** | Upload file, ekstraksi teks, dan pelacakan versi |
| **JournalEligibilityService** | Menghitung skor kelengkapan metadata jurnal |
| **ReviewerCommentParserService** | Mem-parsing catatan reviewer tidak terstruktur menjadi komentar terstruktur via AI |
| **TextExtractionService** | Mengekstrak teks dari file PDF dan DOCX |

---

## API Endpoints

### Autentikasi (Publik)
| Method | Endpoint | Deskripsi |
|---|---|---|
| POST | `/api/register` | Buat akun baru |
| POST | `/api/login` | Login dan terima token |

### Terautentikasi (Bearer Token)
| Method | Endpoint | Deskripsi |
|---|---|---|
| POST | `/api/logout` | Hapus token saat ini |
| GET | `/api/me` | Dapatkan info pengguna saat ini |

### Dokumen
| Method | Endpoint | Deskripsi |
|---|---|---|
| GET | `/api/document-types` | Daftar tipe dokumen aktif |
| GET | `/api/rubrics` | Daftar semua rubrik |
| GET | `/api/documents` | Daftar dokumen pengguna |
| POST | `/api/documents` | Buat dokumen baru |
| GET | `/api/documents/{id}` | Detail dokumen |
| PUT | `/api/documents/{id}` | Update dokumen |
| DELETE | `/api/documents/{id}` | Hapus dokumen |

### Analisis
| Method | Endpoint | Deskripsi |
|---|---|---|
| POST | `/api/documents/{id}/analyze` | Jalankan analisis AI |
| GET | `/api/documents/{id}/analysis` | Dapatkan hasil analisis terbaru |

### Versi & Perbandingan
| Method | Endpoint | Deskripsi |
|---|---|---|
| GET | `/api/documents/{id}/versions` | Daftar versi dokumen |
| POST | `/api/documents/{id}/versions` | Upload versi baru |
| GET | `/api/documents/{id}/comparison` | Bandingkan dua versi |

### Pemetaan Reviewer
| Method | Endpoint | Deskripsi |
|---|---|---|
| GET | `/api/articles/{id}/reviewer-comments` | Daftar komentar reviewer |
| POST | `/api/articles/{id}/reviewer-comments` | Tambah komentar manual |
| POST | `/api/articles/{id}/reviewer-comments/parse` | Parse catatan reviewer via AI |
| POST | `/api/reviewer-comments/{id}/responses` | Simpan respons penulis |
| POST | `/api/reviewer-comments/{id}/generate-response` | AI buat draf respons |
| GET | `/api/articles/{id}/response-matrix` | Dapatkan matriks respons |
| GET | `/api/articles/{id}/response-letter` | Unduh PDF response letter |

### Rekomendasi Jurnal
| Method | Endpoint | Deskripsi |
|---|---|---|
| GET | `/api/documents/{id}/journal-recommendations` | Daftar rekomendasi |
| POST | `/api/documents/{id}/journal-recommendations` | Generate rekomendasi AI |

### Dashboard
| Method | Endpoint | Deskripsi |
|---|---|---|
| GET | `/api/user/dashboard` | Data dashboard pengguna |

### Admin (`/api/admin/*`)
| Method | Endpoint | Deskripsi |
|---|---|---|
| GET | `/api/admin/dashboard` | Data dashboard admin |
| GET | `/api/admin/users` | Daftar semua pengguna |
| PUT | `/api/admin/users/{id}/status` | Aktif/nonaktifkan pengguna |
| GET | `/api/admin/documents` | Semua dokumen (view admin) |
| GET | `/api/admin/journals` | Daftar jurnal |
| POST | `/api/admin/journals` | Buat jurnal |
| POST | `/api/admin/journals/import` | Impor CSV jurnal |
| PUT | `/api/admin/journals/{id}` | Update jurnal |
| DELETE | `/api/admin/journals/{id}` | Hapus jurnal |
| PUT | `/api/admin/rubrics/{id}` | Update rubrik |

---

## Struktur Database

### Tabel Inti
| Tabel | Tujuan |
|---|---|
| `users` | Akun pengguna dengan role (user/admin) dan status aktif |
| `document_types` | Kategori dokumen (artikel, proposal, laporan) |
| `documents` | Metadata dokumen dengan pelacakan status |
| `document_versions` | Upload file dengan penomoran versi |
| `analysis_results` | Output analisis AI dengan skor dan data JSON |
| `analysis_aspect_scores` | Skor per-aspek dari analisis |
| `rubrics` | Rubrik penilaian per tipe dokumen |
| `reviewer_comments` | Umpan balik reviewer terstruktur |
| `reviewer_responses` | Respons penulis terhadap komentar reviewer |
| `journals` | Database jurnal dengan tingkat SINTA dan metadata |
| `journal_recommendations` | Kecocokan jurnal hasil AI per dokumen |

Status enum utama:
- **Dokumen**: `uploaded`, `analyzed`, `need_revision`, `ready`, `revised`, `archived`
- **Prioritas komentar**: `minor`, `major`, `critical`
- **Status komentar**: `pending`, `in_progress`, `done`, `rejected_with_reason`

---

## Instalasi & Setup

### Prasyarat
- PHP ^8.3
- Composer
- Node.js & npm
- MySQL / MariaDB

### Langkah Instalasi

```bash
# 1. Clone repositori
git clone <repository-url> acadcheck
cd acadcheck

# 2. Install dependensi PHP
composer install

# 3. Install dependensi frontend
npm install

# 4. Konfigurasi environment
cp .env.example .env
# Edit .env dengan kredensial database dan API key AI

# 5. Generate application key
php artisan key:generate

# 6. Jalankan migrasi database
php artisan migrate

# 7. Seed data awal (tipe dokumen & rubrik)
php artisan db:seed

# 8. Build asset frontend
npm run build

# 9. Jalankan server development
npm run dev
# Atau gunakan perintah all-in-one:
composer run dev
```

### Variable Environment
```
DB_DATABASE=nama_database
AI_PROVIDER=opencode          # atau groq
AI_API_KEY=api_key_anda
AI_BASE_URL=https://api.example.com/v1
AI_MODEL=nama-model
```

---

## Testing

```bash
# Jalankan semua test
php artisan test

# Jalankan test suite spesifik
php artisan test --testsuite=Feature
php artisan test --testsuite=Unit

# Jalankan dengan coverage
php artisan test --coverage
```

**Cakupan saat ini**: 93 test, 480 assertions (semua lolos)
- 18 Feature test (API endpoints, services, rendering halaman)
- 4 Unit test (services)

---

## Konfigurasi AI Provider

AcadCheck mendukung API kompatibel OpenAI.

### Provider Didukung
- **OpenCode** (`opencode`) — default
- **Groq** (`groq`) — fallback

### Konfigurasi
Setel di `.env`:
```
AI_PROVIDER=opencode
AI_API_KEY=sk-your-key
AI_BASE_URL=https://opencode.ai/zen/v1
AI_MODEL=your-model
```

### Batas Karakter per Fitur
| Fitur | Batas |
|---|---|
| Analisis dokumen | 60.000 karakter |
| Parsing komentar reviewer | 8.000 karakter |
| Generasi respons penulis | 4.000 karakter |

---

## Pengembangan

```bash
# Jalankan semua service (server + queue + logs + Vite)
composer run dev

# Jalankan queue worker
php artisan queue:work

# Build asset frontend
npm run build

# Watch untuk perubahan frontend
npm run dev
```

---

## Catatan Keamanan

- **Penyimpanan Token**: Token Sanctum disimpan di `localStorage` (risiko sedang terhadap XSS)
- **Route Web**: Saat ini mengandalkan autentikasi JS sisi klien; tidak ada middleware server
- **Rate Limiting**: Belum diimplementasikan pada endpoint auth
- **Tidak Ada Verifikasi Email**: Pendaftaran langsung membuat akun aktif
- **Validasi File**: Upload dibatasi PDF/DOCX, maks 10 MB

---

## Audit Kesiapan

Audit terbaru memberikan skor **78% kesiapan**:

| Kategori | Status |
|---|---|
| Core API | ✅ Solid |
| Test | ✅ 93 lolos |
| Integrasi AI | ✅ Fungsional |
| Penanganan File | ✅ Bekerja |
| Auth Server-Side | ⚠️ Belum ada di route web |
| Paritas CRUD Frontend | ⚠️ Sebagian |
| Testing Admin Journal | ⚠️ Belum lengkap |
| Rate Limiting | ❌ Belum diimplementasi |
| Constraint DB | ⚠️ Beberapa belum ada |

---

## Lisensi

Proyek ini dikembangkan untuk tujuan akademik sebagai bagian dari tugas akhir (UAS).
