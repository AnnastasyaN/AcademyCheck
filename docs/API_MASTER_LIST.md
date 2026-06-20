# API Master List — AcadCheck AI

Base URL: `http://127.0.0.1:8000/api`

Standard Headers:
```http
Accept: application/json
Authorization: Bearer <token>  <!-- untuk endpoint yang membutuhkan auth -->
```

---

## Legend

| Icon | Meaning |
|------|---------|
| 🔓 | Public — no auth required |
| 🔒 | Authenticated — `auth:sanctum` |
| 🔐 | Admin — `auth:sanctum` + `admin` middleware |
| ✅ | Documented | 
| ❌ | Not Documented |

---

## Authentication

| # | Method | Endpoint | Auth | Documented | Description |
|---|--------|----------|:----:|:----------:|-------------|
| 1 | `POST` | `/api/register` | 🔓 | ✅ | Registrasi user baru |
| 2 | `POST` | `/api/login` | 🔓 | ✅ | Login dan dapatkan token |
| 3 | `POST` | `/api/logout` | 🔒 | ✅ | Hapus token saat ini |
| 4 | `GET` | `/api/me` | 🔒 | ✅ | Lihat data user saat ini |

---

## Dashboard

| # | Method | Endpoint | Auth | Documented | Description |
|---|--------|----------|:----:|:----------:|-------------|
| 5 | `GET` | `/api/user/dashboard` | 🔒 | ❌ | Dashboard user — ringkasan dokumen, aktivitas, prioritas revisi |
| 6 | `GET` | `/api/test-ai` | 🔒 | ❌ | Test koneksi AI (hanya berjalan di environment `local`) |

---

## Document Types

| # | Method | Endpoint | Auth | Documented | Description |
|---|--------|----------|:----:|:----------:|-------------|
| 7 | `GET` | `/api/document-types` | 🔒 | ✅ | Daftar tipe dokumen aktif (article, proposal, report) |

---

## Rubrics

| # | Method | Endpoint | Auth | Documented | Description |
|---|--------|----------|:----:|:----------:|-------------|
| 8 | `GET` | `/api/rubrics` | 🔒 | ✅ | Daftar rubrik, bisa filter dengan `?document_type=` |

---

## Documents

| # | Method | Endpoint | Auth | Documented | Description |
|---|--------|----------|:----:|:----------:|-------------|
| 9 | `GET` | `/api/documents` | 🔒 | ✅ | Daftar dokumen user saat ini |
| 10 | `POST` | `/api/documents` | 🔒 | ✅ | Upload dokumen baru |
| 11 | `GET` | `/api/documents/{document}` | 🔒 | ✅ | Detail dokumen + versi + analisis |
| 12 | `PUT` | `/api/documents/{document}` | 🔒 | ❌ | Update dokumen (metadata opsional + file opsional → auto-create version) |
| 13 | `DELETE` | `/api/documents/{document}` | 🔒 | ❌ | Hapus dokumen + semua versi + file |

---

## AI Analysis

| # | Method | Endpoint | Auth | Documented | Description |
|---|--------|----------|:----:|:----------:|-------------|
| 14 | `POST` | `/api/documents/{document}/analyze` | 🔒 | ✅ | Jalankan analisis AI pada versi terbaru |
| 15 | `GET` | `/api/documents/{document}/analysis` | 🔒 | ✅ | Ambil hasil analisis terbaru |

---

## Document Versions

| # | Method | Endpoint | Auth | Documented | Description |
|---|--------|----------|:----:|:----------:|-------------|
| 16 | `GET` | `/api/documents/{document}/versions` | 🔒 | ✅ | Daftar versi dokumen |
| 17 | `POST` | `/api/documents/{document}/versions` | 🔒 | ✅ | Upload revisi baru |

---

## Comparison

| # | Method | Endpoint | Auth | Documented | Description |
|---|--------|----------|:----:|:----------:|-------------|
| 18 | `GET` | `/api/documents/{document}/comparison` | 🔒 | ✅ | Bandingkan skor antar dua versi |

---

## Journal Recommendations

| # | Method | Endpoint | Auth | Documented | Description |
|---|--------|----------|:----:|:----------:|-------------|
| 19 | `GET` | `/api/documents/{document}/journal-recommendations` | 🔒 | ❌ | Lihat rekomendasi jurnal yang tersimpan |
| 20 | `POST` | `/api/documents/{document}/journal-recommendations` | 🔒 | ❌ | Generate rekomendasi jurnal baru via AI |

---

## Reviewer Comments

| # | Method | Endpoint | Auth | Documented | Description |
|---|--------|----------|:----:|:----------:|-------------|
| 21 | `GET` | `/api/articles/{document}/reviewer-comments` | 🔒 | ✅ | Daftar komentar reviewer |
| 22 | `POST` | `/api/articles/{document}/reviewer-comments` | 🔒 | ✅ | Tambah komentar reviewer manual |
| 23 | `POST` | `/api/articles/{document}/reviewer-comments/parse` | 🔒 | ✅ | Parse teks reviewer jadi komentar terstruktur via AI |
| 24 | `PUT` | `/api/reviewer-comments/{reviewerComment}` | 🔒 | ❌ | Update komentar reviewer |
| 25 | `PUT` | `/api/reviewer-comments/{reviewerComment}/status` | 🔒 | ❌ | Update status komentar (pending/in_progress/done/rejected_with_reason) |
| 26 | `DELETE` | `/api/reviewer-comments/{reviewerComment}` | 🔒 | ❌ | Hapus komentar reviewer |

---

## Reviewer Responses

| # | Method | Endpoint | Auth | Documented | Description |
|---|--------|----------|:----:|:----------:|-------------|
| 27 | `POST` | `/api/reviewer-comments/{reviewerComment}/responses` | 🔒 | ✅ | Simpan respons penulis |
| 28 | `POST` | `/api/reviewer-comments/{reviewerComment}/generate-response` | 🔒 | ✅ | Generate draft respons penulis via AI |
| 29 | `GET` | `/api/articles/{document}/response-matrix` | 🔒 | ✅ | Matriks komentar vs respons |
| 30 | `GET` | `/api/articles/{document}/response-letter` | 🔒 | ✅ | Download PDF Response to Reviewers |

---

## Admin — Dashboard

| # | Method | Endpoint | Auth | Documented | Description |
|---|--------|----------|:----:|:----------:|-------------|
| 31 | `GET` | `/api/admin/dashboard` | 🔐 | ✅ | Dashboard admin — statistik user, dokumen, analisis |

---

## Admin — Users

| # | Method | Endpoint | Auth | Documented | Description |
|---|--------|----------|:----:|:----------:|-------------|
| 32 | `GET` | `/api/admin/users` | 🔐 | ✅ | Daftar user dengan filter & pagination |
| 33 | `PUT` | `/api/admin/users/{user}/status` | 🔐 | ✅ | Aktifkan/nonaktifkan user |

---

## Admin — Documents

| # | Method | Endpoint | Auth | Documented | Description |
|---|--------|----------|:----:|:----------:|-------------|
| 34 | `GET` | `/api/admin/documents` | 🔐 | ✅ | Daftar semua dokumen dengan filter & pagination |

---

## Admin — Journals

| # | Method | Endpoint | Auth | Documented | Description |
|---|--------|----------|:----:|:----------:|-------------|
| 35 | `GET` | `/api/admin/journals/stats` | 🔐 | ❌ | Statistik jurnal (total, by SINTA level, eligibility) |
| 36 | `GET` | `/api/admin/journals` | 🔐 | ❌ | Daftar jurnal dengan filter & pagination |
| 37 | `POST` | `/api/admin/journals` | 🔐 | ❌ | Tambah jurnal manual |
| 38 | `POST` | `/api/admin/journals/import` | 🔐 | ❌ | Import jurnal dari CSV |
| 39 | `PUT` | `/api/admin/journals/{journal}` | 🔐 | ❌ | Update data jurnal |
| 40 | `DELETE` | `/api/admin/journals/{journal}` | 🔐 | ❌ | Hapus jurnal |

---

## Admin — Rubrics

| # | Method | Endpoint | Auth | Documented | Description |
|---|--------|----------|:----:|:----------:|-------------|
| 41 | `PUT` | `/api/admin/rubrics/{rubric}` | 🔐 | ✅ | Update rubrik (aspect_name, weight, description, is_active) |

---

## Summary

| Category | Total | Public | Authenticated | Admin | Documented | Missing Docs |
|----------|:-----:|:------:|:-------------:|:-----:|:----------:|:------------:|
| Authentication | 4 | 2 | 2 | 0 | 4 | 0 |
| Dashboard | 2 | 0 | 2 | 0 | 0 | 2 |
| Document Types | 1 | 0 | 1 | 0 | 1 | 0 |
| Rubrics | 2 | 0 | 1 | 1 | 2 | 0 |
| Documents | 5 | 0 | 5 | 0 | 3 | 2 |
| AI Analysis | 2 | 0 | 2 | 0 | 2 | 0 |
| Document Versions | 2 | 0 | 2 | 0 | 2 | 0 |
| Comparison | 1 | 0 | 1 | 0 | 1 | 0 |
| Journal Recommendations | 2 | 0 | 2 | 0 | 0 | 2 |
| Reviewer Comments | 6 | 0 | 6 | 0 | 3 | 3 |
| Reviewer Responses | 4 | 0 | 4 | 0 | 3 | 1 |
| Admin Dashboard | 1 | 0 | 0 | 1 | 1 | 0 |
| Admin Users | 2 | 0 | 0 | 2 | 2 | 0 |
| Admin Documents | 1 | 0 | 0 | 1 | 1 | 0 |
| Admin Journals | 6 | 0 | 0 | 6 | 0 | 6 |
| Admin Rubrics | 1 | 0 | 0 | 1 | 1 | 0 |
| **Grand Total** | **42** | **2** | **28** | **12** | **26** | **16** |
