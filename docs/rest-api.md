# AcadCheck AI REST API

Base URL lokal:

```text
http://127.0.0.1:8000
```

Header wajib untuk semua request:

```http
Accept: application/json
```

Header untuk endpoint yang membutuhkan autentikasi:

```http
Authorization: Bearer <token>
```

---

## Daftar Isi

- [Authentication](#authentication)
- [Dashboard](#dashboard)
- [Document Types](#document-types)
- [Rubrics](#rubrics)
- [Documents](#documents)
- [AI Analysis](#ai-analysis)
- [Document Versions](#document-versions)
- [Comparison](#comparison)
- [Journal Recommendations](#journal-recommendations)
- [Reviewer Comments](#reviewer-comments)
- [Reviewer Responses](#reviewer-responses)
- [Admin Dashboard](#admin-dashboard)
- [Admin Users](#admin-users)
- [Admin Documents](#admin-documents)
- [Admin Journals](#admin-journals)
- [Admin Rubrics](#admin-rubrics)

---

## Authentication

### Register

Mendaftarkan user baru.

```http
POST /api/register
Content-Type: application/json
```

**Request Body:**

| Field | Type | Required | Description |
|-------|------|:--------:|-------------|
| `name` | string | ✅ | Nama lengkap, maks 255 karakter |
| `email` | string | ✅ | Email valid, unik |
| `password` | string | ✅ | Minimal 8 karakter |

**Response `201 Created`:**

```json
{
  "message": "Register berhasil.",
  "user": {
    "id": 1,
    "name": "User Demo",
    "email": "demo@example.com",
    "role": "user",
    "is_active": true,
    "created_at": "2026-06-19T10:00:00.000000Z",
    "updated_at": "2026-06-19T10:00:00.000000Z"
  },
  "token": "1|abc123def456..."
}
```

**Response `422 Unprocessable Entity`:**

```json
{
  "message": "Email sudah terdaftar.",
  "errors": {
    "email": ["Email sudah terdaftar."]
  }
}
```

---

### Login

```http
POST /api/login
Content-Type: application/json
```

**Request Body:**

| Field | Type | Required | Description |
|-------|------|:--------:|-------------|
| `email` | string | ✅ | Email terdaftar |
| `password` | string | ✅ | Password user |

**Response `200 OK`:**

```json
{
  "message": "Login berhasil.",
  "user": {
    "id": 1,
    "name": "User Demo",
    "email": "demo@example.com",
    "role": "user",
    "is_active": true
  },
  "token": "1|abc123def456..."
}
```

**Response `401 Unauthorized`:**

```json
{
  "message": "Email atau password salah."
}
```

**Response `403 Forbidden`:**

```json
{
  "message": "Akun tidak aktif."
}
```

---

### Logout

Menghapus token autentikasi saat ini.

```http
POST /api/logout
Authorization: Bearer <token>
```

**Response `200 OK`:**

```json
{
  "message": "Logout berhasil."
}
```

---

### Current User

Mendapatkan data user yang sedang login.

```http
GET /api/me
Authorization: Bearer <token>
```

**Response `200 OK`:**

```json
{
  "data": {
    "id": 1,
    "name": "User Demo",
    "email": "demo@example.com",
    "role": "user",
    "is_active": true,
    "created_at": "2026-06-19T10:00:00.000000Z",
    "updated_at": "2026-06-19T10:00:00.000000Z"
  }
}
```

---

## Dashboard

### User Dashboard

Ringkasan dashboard untuk user: total dokumen, rata-rata skor, dokumen terbaru, prioritas revisi, analisis terbaru.

```http
GET /api/user/dashboard
Authorization: Bearer <token>
```

**Response `200 OK`:**

```json
{
  "data": {
    "summary": {
      "total_documents": 5,
      "average_score": 78.5,
      "by_type": {
        "article": 2,
        "proposal": 2,
        "report": 1
      },
      "by_status": {
        "uploaded": 0,
        "analyzed": 2,
        "need_revision": 2,
        "revised": 1,
        "ready": 0
      }
    },
    "reviewer_comments": {
      "total": 12,
      "pending": 4
    },
    "latest_documents": [],
    "latest_activities": [],
    "revision_priorities": [],
    "latest_analyses": []
  }
}
```

### Test AI Connection

Mengecek koneksi ke AI provider. **Hanya berjalan di environment `local`.**

```http
GET /api/test-ai
Authorization: Bearer <token>
```

**Response `200 OK`:**

```json
{
  "message": "Koneksi AI aktif.",
  "provider": "opencode-ai",
  "model": "gpt-4o-mini"
}
```

**Response `404 Not Found`** (jika environment bukan `local`):

```json
{
  "message": ""
}
```

---

## Document Types

### List Document Types

Mendapatkan daftar tipe dokumen yang tersedia.

```http
GET /api/document-types
Authorization: Bearer <token>
```

Gunakan `id` dari response sebagai `document_type_id` saat upload dokumen.

**Response `200 OK`:**

```json
{
  "data": [
    {
      "id": 1,
      "name": "article",
      "label": "Artikel Ilmiah",
      "description": "Dokumen artikel ilmiah"
    },
    {
      "id": 2,
      "name": "proposal",
      "label": "Proposal",
      "description": "Dokumen proposal"
    },
    {
      "id": 3,
      "name": "report",
      "label": "Laporan",
      "description": "Dokumen laporan"
    }
  ]
}
```

---

## Rubrics

### List Rubrics

```http
GET /api/rubrics?document_type=article
Authorization: Bearer <token>
```

**Query Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|:--------:|-------------|
| `document_type` | string | ❌ | Filter berdasarkan nama tipe dokumen: `article`, `proposal`, `report` |

**Response `200 OK`:**

```json
{
  "data": [
    {
      "id": 1,
      "document_type_id": 1,
      "aspect_name": "Judul dan Abstrak",
      "weight": 10,
      "description": "Judul jelas dan mencerminkan isi, abstrak informatif.",
      "is_active": true,
      "document_type": {
        "id": 1,
        "name": "article",
        "label": "Artikel Ilmiah"
      }
    }
  ]
}
```

---

## Documents

### List Documents

Mendapatkan daftar dokumen milik user yang sedang login.

```http
GET /api/documents
Authorization: Bearer <token>
```

**Response `200 OK`:**

```json
{
  "data": [
    {
      "id": 1,
      "user_id": 1,
      "document_type_id": 1,
      "title": "Judul Artikel",
      "topic": "Kecerdasan Buatan",
      "keywords": "AI, Machine Learning",
      "description": "Deskripsi singkat",
      "status": "analyzed",
      "latest_score": 85,
      "latest_version_id": 2,
      "created_at": "2026-06-19T10:00:00.000000Z",
      "updated_at": "2026-06-19T10:30:00.000000Z",
      "document_type": {
        "id": 1,
        "name": "article",
        "label": "Artikel Ilmiah"
      },
      "latest_version": {
        "id": 2,
        "version_number": 2,
        "file_original_name": "artikel_revisi.pdf",
        "file_type": "pdf",
        "file_size": 2048576
      }
    }
  ]
}
```

---

### Upload Document

Upload dokumen baru.

```http
POST /api/documents
Authorization: Bearer <token>
Content-Type: multipart/form-data
```

**Request Fields:**

| Key | Type | Required | Description |
|-----|------|:--------:|-------------|
| `document_type_id` | integer | ✅ | ID dari tipe dokumen |
| `title` | string | ✅ | Judul dokumen, maks 255 karakter |
| `topic` | string | ❌ | Topik penelitian, maks 255 karakter |
| `keywords` | string | ❌ | Kata kunci, pisahkan dengan koma |
| `description` | string | ❌ | Deskripsi singkat |
| `file` | file | ✅ | File PDF atau DOCX, maks 10 MB |

**Response `201 Created`:**

```json
{
  "message": "Dokumen berhasil diunggah dan teks berhasil diekstrak.",
  "data": {
    "document": {
      "id": 1,
      "user_id": 1,
      "document_type_id": 1,
      "title": "Judul Artikel",
      "topic": "Kecerdasan Buatan",
      "keywords": "AI, Machine Learning",
      "description": null,
      "status": "uploaded",
      "latest_score": null,
      "latest_version_id": 1,
      "created_at": "2026-06-19T10:00:00.000000Z",
      "updated_at": "2026-06-19T10:00:00.000000Z",
      "document_type": {
        "id": 1,
        "name": "article",
        "label": "Artikel Ilmiah"
      }
    },
    "version": {
      "id": 1,
      "document_id": 1,
      "version_number": 1,
      "file_path": "documents/user_1/document_1/abc123.pdf",
      "file_original_name": "artikel.pdf",
      "file_type": "pdf",
      "file_size": 1048576,
      "uploaded_at": "2026-06-19T10:00:00.000000Z"
    },
    "extracted_text_preview": "Lorem ipsum dolor sit amet..."
  }
}
```

**Response `422 Unprocessable Entity`** (file tidak valid):

```json
{
  "message": "Teks dokumen gagal diekstrak. File mungkin rusak atau tidak didukung."
}
```

---

### Document Detail

```http
GET /api/documents/{document_id}
Authorization: Bearer <token>
```

**Response `200 OK`:**

```json
{
  "data": {
    "id": 1,
    "user_id": 1,
    "document_type_id": 1,
    "title": "Judul Artikel",
    "topic": "Kecerdasan Buatan",
    "keywords": "AI, Machine Learning",
    "description": null,
    "status": "analyzed",
    "latest_score": 85,
    "latest_version_id": 2,
    "created_at": "2026-06-19T10:00:00.000000Z",
    "updated_at": "2026-06-19T10:30:00.000000Z",
    "document_type": {
      "id": 1,
      "name": "article",
      "label": "Artikel Ilmiah"
    },
    "versions": [
      { "id": 1, "version_number": 1 },
      { "id": 2, "version_number": 2 }
    ],
    "latest_version": {
      "id": 2,
      "version_number": 2,
      "file_original_name": "artikel_revisi.pdf",
      "file_type": "pdf",
      "file_size": 2048576
    },
    "analysis_results": [
      {
        "id": 1,
        "total_score": 85,
        "status": "completed",
        "aspect_scores": [
          { "aspect_name": "Judul dan Abstrak", "score": 85 },
          { "aspect_name": "Metode", "score": 80 }
        ]
      }
    ]
  }
}
```

**Response `403 Forbidden`:**

```json
{
  "message": "Anda tidak memiliki akses ke dokumen ini."
}
```

---

### Update Document

Memperbarui metadata dokumen. Jika menyertakan file, sistem akan membuat versi baru secara otomatis.

```http
PUT /api/documents/{document_id}
Authorization: Bearer <token>
Content-Type: multipart/form-data
```

**Request Fields (semua opsional):**

| Key | Type | Required | Description |
|-----|------|:--------:|-------------|
| `document_type_id` | integer | ❌ | ID tipe dokumen baru |
| `title` | string | ❌ | Judul baru |
| `topic` | string | ❌ | Topik baru |
| `keywords` | string | ❌ | Kata kunci baru |
| `description` | string | ❌ | Deskripsi baru |
| `file` | file | ❌ | File PDF/DOCX baru (akan buat versi baru) |

**Response `200 OK`** (metadata only):

```json
{
  "message": "Dokumen berhasil diperbarui.",
  "data": {
    "id": 1,
    "title": "Judul Baru",
    "status": "uploaded"
  }
}
```

**Response `200 OK`** (dengan file baru):

```json
{
  "message": "Dokumen berhasil diperbarui dan file baru disimpan sebagai versi terbaru.",
  "data": {
    "id": 1,
    "title": "Judul Baru",
    "latest_version_id": 3
  }
}
```

---

### Delete Document

Menghapus dokumen beserta semua versi dan file terkait.

```http
DELETE /api/documents/{document_id}
Authorization: Bearer <token>
```

**Response `200 OK`:**

```json
{
  "message": "Dokumen berhasil dihapus."
}
```

---

## AI Analysis

### Run Analysis

Menjalankan analisis AI pada versi terbaru dokumen.

```http
POST /api/documents/{document_id}/analyze
Authorization: Bearer <token>
```

**Response `201 Created`:**

```json
{
  "message": "Analisis AI berhasil.",
  "data": {
    "id": 1,
    "document_id": 1,
    "document_version_id": 2,
    "total_score": 85,
    "status": "completed",
    "summary": "Dokumen ini memiliki kualitas yang baik dengan beberapa area yang perlu diperbaiki.",
    "main_issues": [
      "Metode penelitian perlu diperjelas.",
      "Referensi perlu diperbarui."
    ],
    "recommendations": [
      "Tambahkan detail pada bagian metode.",
      "Gunakan referensi 5 tahun terakhir."
    ],
    "revision_priorities": [
      {
        "aspect": "Metode",
        "priority": "high",
        "suggestion": "Jelaskan secara detail langkah-langkah penelitian."
      }
    ],
    "document_version": {
      "id": 2,
      "version_number": 2
    },
    "aspect_scores": [
      {
        "id": 1,
        "aspect_name": "Judul dan Abstrak",
        "score": 90,
        "status": "good",
        "finding": "Judul jelas dan abstrak informatif.",
        "recommendation": "Pertahankan kualitas ini."
      },
      {
        "id": 2,
        "aspect_name": "Metode",
        "score": 75,
        "status": "needs_improvement",
        "finding": "Metode kurang detail.",
        "recommendation": "Tambahkan langkah-langkah penelitian."
      }
    ]
  }
}
```

**Response `422 Unprocessable Entity`:**

```json
{
  "message": "Teks dokumen belum tersedia untuk dianalisis."
}
```

**Response `502 Bad Gateway`** (AI error):

```json
{
  "message": "Analisis AI gagal. Silakan coba kembali."
}
```

---

### Latest Analysis

Mendapatkan hasil analisis AI terbaru.

```http
GET /api/documents/{document_id}/analysis
Authorization: Bearer <token>
```

**Response `200 OK`:** (sama dengan response Run Analysis tanpa `message`)

```json
{
  "data": {
    "id": 1,
    "document_id": 1,
    "total_score": 85,
    "status": "completed",
    "summary": "...",
    "main_issues": [],
    "recommendations": [],
    "revision_priorities": [],
    "document_version": { "id": 2, "version_number": 2 },
    "aspect_scores": []
  }
}
```

**Response `404 Not Found`:**

```json
{
  "message": "Hasil analisis belum tersedia."
}
```

---

## Document Versions

### List Versions

```http
GET /api/documents/{document_id}/versions
Authorization: Bearer <token>
```

**Response `200 OK`:**

```json
{
  "data": [
    {
      "id": 1,
      "document_id": 1,
      "version_number": 1,
      "file_original_name": "artikel.pdf",
      "file_type": "pdf",
      "file_size": 1048576,
      "revision_note": null,
      "uploaded_at": "2026-06-19T10:00:00.000000Z",
      "analysis_results_count": 1
    },
    {
      "id": 2,
      "document_id": 1,
      "version_number": 2,
      "file_original_name": "artikel_revisi.pdf",
      "file_type": "pdf",
      "file_size": 2048576,
      "revision_note": "Revisi setelah review",
      "uploaded_at": "2026-06-19T11:00:00.000000Z",
      "analysis_results_count": 0
    }
  ]
}
```

---

### Upload Revision

Mengunggah revisi baru untuk dokumen. Sistem akan membuat nomor versi berikutnya secara otomatis.

```http
POST /api/documents/{document_id}/versions
Authorization: Bearer <token>
Content-Type: multipart/form-data
```

**Request Fields:**

| Key | Type | Required | Description |
|-----|------|:--------:|-------------|
| `file` | file | ✅ | File PDF/DOCX, maks 10 MB |
| `revision_note` | text | ❌ | Catatan revisi, maks 5000 karakter |

**Response `201 Created`:**

```json
{
  "message": "Versi revisi berhasil diunggah.",
  "data": {
    "version": {
      "id": 2,
      "document_id": 1,
      "version_number": 2,
      "file_original_name": "artikel_revisi.pdf",
      "file_type": "pdf",
      "file_size": 2048576,
      "revision_note": "Revisi setelah review",
      "uploaded_at": "2026-06-19T11:00:00.000000Z"
    },
    "extracted_text_preview": "Lorem ipsum dolor sit amet..."
  }
}
```

---

## Comparison

Membandingkan skor analisis antara dua versi dokumen.

```http
GET /api/documents/{document_id}/comparison?from_version_id=1&to_version_id=2
Authorization: Bearer <token>
```

**Query Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|:--------:|-------------|
| `from_version_id` | integer | ✅ | ID versi pertama |
| `to_version_id` | integer | ✅ | ID versi kedua — harus berbeda |

**Response `200 OK`:**

```json
{
  "data": {
    "document_id": 1,
    "from_version_id": 1,
    "to_version_id": 2,
    "from_analysis_id": 1,
    "to_analysis_id": 2,
    "from_total_score": 75,
    "to_total_score": 85,
    "total_difference": 10,
    "total_status": "improved",
    "aspect_comparison": [
      {
        "aspect_name": "Judul dan Abstrak",
        "from_score": 80,
        "to_score": 90,
        "difference": 10,
        "status": "improved"
      },
      {
        "aspect_name": "Metode",
        "from_score": 70,
        "to_score": 80,
        "difference": 10,
        "status": "improved"
      }
    ]
  }
}
```

**Response `422 Unprocessable Entity`:**

```json
{
  "message": "Kedua versi harus sudah dianalisis terlebih dahulu."
}
```

---

## Journal Recommendations

Rekomendasi jurnal hanya tersedia untuk dokumen dengan tipe `article`.

### List Recommendations

Mendapatkan rekomendasi jurnal yang sudah tersimpan.

```http
GET /api/documents/{document_id}/journal-recommendations
Authorization: Bearer <token>
```

**Response `200 OK`:**

```json
{
  "data": [
    {
      "id": 1,
      "document_id": 1,
      "journal_id": 5,
      "fit_score": 92,
      "fit_reason": "Topik artikel sesuai dengan focus scope jurnal di bidang kecerdasan buatan.",
      "submission_risk": "Persaingan tinggi, pastikan metodologi sangat detail.",
      "suggested_improvement": "Tambahkan analisis statistik yang lebih mendalam.",
      "journal": {
        "id": 5,
        "name": "Jurnal Ilmu Komputer dan Kecerdasan Buatan",
        "publisher": "Universitas Contoh",
        "sinta_level": "S2",
        "subject_area": "Computer Science, Artificial Intelligence"
      }
    }
  ]
}
```

**Response `422 Unprocessable Entity`:**

```json
{
  "message": "Rekomendasi jurnal hanya tersedia untuk artikel ilmiah."
}
```

---

### Generate Recommendations

Menjalankan AI untuk menghasilkan rekomendasi jurnal baru. Akan menimpa rekomendasi sebelumnya.

```http
POST /api/documents/{document_id}/journal-recommendations
Authorization: Bearer <token>
```

**Response `201 Created`:**

```json
{
  "message": "Rekomendasi jurnal berhasil dibuat.",
  "data": [
    {
      "id": 1,
      "journal_id": 5,
      "fit_score": 92,
      "fit_reason": "Topik sesuai dengan fokus jurnal.",
      "submission_risk": "Kompetitif, review ketat.",
      "suggested_improvement": "Perkuat bagian eksperimen.",
      "journal": { "id": 5, "name": "Jurnal AI", "sinta_level": "S2" }
    }
  ]
}
```

**Response `422 Unprocessable Entity`** (data jurnal tidak cukup):

```json
{
  "message": "Data jurnal eligible AI belum cukup. Lengkapi metadata minimal 3 jurnal dengan eligibility score 70+."
}
```

---

## Reviewer Comments

Reviewer Mapping hanya tersedia untuk dokumen dengan tipe `article`.

### List Reviewer Comments

```http
GET /api/articles/{document_id}/reviewer-comments
Authorization: Bearer <token>
```

**Response `200 OK`:**

```json
{
  "data": [
    {
      "id": 1,
      "document_id": 1,
      "reviewer_label": "Reviewer 1",
      "comment_number": 1,
      "original_comment": "Metode perlu diperjelas.",
      "related_section": "Metode",
      "priority": "major",
      "status": "done",
      "response": {
        "id": 1,
        "author_response": "Terima kasih. Metode telah diperjelas.",
        "revision_made": "Ditambahkan diagram alir.",
        "revision_location": "Halaman 4",
        "revised_version_id": 2,
        "revised_version": { "id": 2, "version_number": 2 }
      }
    }
  ]
}
```

---

### Add Reviewer Comment

Menambahkan komentar reviewer secara manual.

```http
POST /api/articles/{document_id}/reviewer-comments
Authorization: Bearer <token>
Content-Type: application/json
```

**Request Body:**

| Field | Type | Required | Description |
|-------|------|:--------:|-------------|
| `reviewer_label` | string | ✅ | Label reviewer, misal "Reviewer 1", maks 100 karakter |
| `comment_number` | integer | ❌ | Nomor komentar (default: null) |
| `original_comment` | string | ✅ | Isi komentar asli |
| `related_section` | string | ❌ | Bagian terkait dalam dokumen, maks 100 karakter |
| `priority` | string | ✅ | Prioritas: `minor`, `major`, `critical` |
| `status` | string | ❌ | Status awal: `pending`, `in_progress`, `done`, `rejected_with_reason` (default: `pending`) |

**Response `201 Created`:**

```json
{
  "message": "Komentar reviewer berhasil ditambahkan.",
  "data": {
    "id": 2,
    "document_id": 1,
    "reviewer_label": "Reviewer 1",
    "comment_number": 1,
    "original_comment": "Metode perlu diperjelas.",
    "related_section": "Metode",
    "priority": "major",
    "status": "pending"
  }
}
```

---

### Parse Reviewer Comments With AI

Mengirim teks reviewer mentah ke AI untuk diparsing menjadi komentar terstruktur.

```http
POST /api/articles/{document_id}/reviewer-comments/parse
Authorization: Bearer <token>
Content-Type: application/json
```

**Request Body:**

| Field | Type | Required | Description |
|-------|------|:--------:|-------------|
| `reviewer_text` | string | ✅ | Teks mentah komentar reviewer, maks 30000 karakter |
| `save_to_database` | boolean | ❌ | Jika `true`, hasil parsing langsung disimpan ke database |

**Response `200 OK`:**

```json
{
  "message": "Komentar reviewer berhasil diproses AI.",
  "data": {
    "parsed_comments": [
      {
        "reviewer_label": "Reviewer 1",
        "comment_number": 1,
        "original_comment": "Metode perlu diperjelas.",
        "related_section": "Metode",
        "priority": "major"
      }
    ],
    "saved_comments": []
  }
}
```

**Response `502 Bad Gateway`:**

```json
{
  "message": "Gagal memproses komentar reviewer dengan AI. Silakan coba kembali."
}
```

---

### Update Reviewer Comment

Memperbarui komentar reviewer.

```http
PUT /api/reviewer-comments/{reviewer_comment_id}
Authorization: Bearer <token>
Content-Type: application/json
```

**Request Body (semua opsional):**

| Field | Type | Required | Description |
|-------|------|:--------:|-------------|
| `reviewer_label` | string | ❌ | Label reviewer baru |
| `comment_number` | integer | ❌ | Nomor komentar baru |
| `original_comment` | string | ❌ | Isi komentar baru |
| `related_section` | string | ❌ | Bagian terkait baru |
| `priority` | string | ❌ | Prioritas baru: `minor`, `major`, `critical` |

**Response `200 OK`:**

```json
{
  "message": "Komentar reviewer berhasil diperbarui.",
  "data": {
    "id": 1,
    "reviewer_label": "Reviewer 1",
    "original_comment": "Metode perlu diperjelas (revisi).",
    "priority": "major",
    "status": "pending",
    "response": null
  }
}
```

---

### Update Comment Status

Memperbarui status komentar reviewer.

```http
PUT /api/reviewer-comments/{reviewer_comment_id}/status
Authorization: Bearer <token>
Content-Type: application/json
```

**Request Body:**

| Field | Type | Required | Description |
|-------|------|:--------:|-------------|
| `status` | string | ✅ | Status baru: `pending`, `in_progress`, `done`, `rejected_with_reason` |

**Response `200 OK`:**

```json
{
  "message": "Status komentar reviewer berhasil diperbarui.",
  "data": {
    "id": 1,
    "status": "in_progress"
  }
}
```

---

### Delete Reviewer Comment

```http
DELETE /api/reviewer-comments/{reviewer_comment_id}
Authorization: Bearer <token>
```

**Response `200 OK`:**

```json
{
  "message": "Komentar reviewer berhasil dihapus."
}
```

---

## Reviewer Responses

### Store or Update Author Response

Menyimpan atau memperbarui respons penulis untuk sebuah komentar reviewer.

```http
POST /api/reviewer-comments/{reviewer_comment_id}/responses
Authorization: Bearer <token>
Content-Type: application/json
```

**Request Body:**

| Field | Type | Required | Description |
|-------|------|:--------:|-------------|
| `author_response` | string | ✅ | Teks respons penulis |
| `revision_made` | string | ❌ | Deskripsi revisi yang dilakukan |
| `revision_location` | string | ❌ | Lokasi revisi dalam dokumen, maks 255 karakter |
| `revised_version_id` | integer | ❌ | ID versi tempat revisi dilakukan |

**Response `200 OK`:**

```json
{
  "message": "Respons penulis berhasil disimpan.",
  "data": {
    "id": 1,
    "reviewer_comment_id": 1,
    "author_response": "Terima kasih atas masukannya. Metode penelitian telah kami perjelas dengan menambahkan diagram alir dan langkah-langkah detail.",
    "revision_made": "Ditambahkan diagram alir pada bagian Metode",
    "revision_location": "Halaman 4-5",
    "revised_version_id": 2,
    "revised_version": {
      "id": 2,
      "version_number": 2
    }
  }
}
```

---

### Generate Author Response

Menghasilkan draft respons penulis menggunakan AI.

```http
POST /api/reviewer-comments/{reviewer_comment_id}/generate-response
Authorization: Bearer <token>
Content-Type: application/json
```

**Request Body:**

| Field | Type | Required | Description |
|-------|------|:--------:|-------------|
| `revision_made` | string | ✅ | Deskripsi revisi yang telah dilakukan, maks 10000 karakter |
| `revision_location` | string | ❌ | Lokasi revisi dalam dokumen, maks 255 karakter |
| `save_to_database` | boolean | ❌ | Jika `true`, hasil generate langsung disimpan sebagai respons |

**Response `200 OK`:**

```json
{
  "message": "Draft respons penulis berhasil dibuat.",
  "data": {
    "generated_response": {
      "author_response": "Terima kasih atas masukannya. Kami telah memperjelas metode penelitian dengan menambahkan diagram alir dan penjelasan langkah demi langkah pada halaman 4-5.",
      "revision_made": "Ditambahkan diagram alir",
      "revision_location": "Halaman 4-5"
    },
    "saved_response": null
  }
}
```

---

### Response Matrix

Mendapatkan matriks lengkap komentar reviewer dan respons penulis.

```http
GET /api/articles/{document_id}/response-matrix
Authorization: Bearer <token>
```

**Response `200 OK`:**

```json
{
  "data": {
    "document_id": 1,
    "title": "Judul Artikel",
    "response_matrix": [
      {
        "reviewer_comment_id": 1,
        "reviewer": "Reviewer 1",
        "comment_number": 1,
        "original_comment": "Metode perlu diperjelas.",
        "related_section": "Metode",
        "priority": "major",
        "status": "done",
        "author_response": "Terima kasih. Metode telah diperjelas.",
        "revision_made": "Ditambahkan diagram alir.",
        "revision_location": "Halaman 4",
        "revised_version_id": 2,
        "revised_version_number": 2
      }
    ]
  }
}
```

---

### Response Letter (PDF Download)

Mengunduh Response to Reviewers dalam format PDF.

```http
GET /api/articles/{document_id}/response-letter
Authorization: Bearer <token>
```

**Response:** File PDF (`response_to_reviewers_document_{id}.pdf`) dengan `Content-Type: application/pdf`.

**Response `422 Unprocessable Entity`:**

```json
{
  "message": "Belum ada komentar reviewer untuk artikel ini."
}
```

---

## Admin Dashboard

### Admin Dashboard

Ringkasan statistik untuk admin.

```http
GET /api/admin/dashboard
Authorization: Bearer <token>
```

**Response `200 OK`:**

```json
{
  "data": {
    "summary": {
      "total_users": 10,
      "total_regular_users": 8,
      "total_admins": 2,
      "active_users": 9,
      "inactive_users": 1,
      "total_analysis": 25,
      "total_documents": 15,
      "average_score": 78.5,
      "by_type": { "article": 5, "proposal": 6, "report": 4 },
      "by_status": {
        "uploaded": 2,
        "analyzed": 5,
        "need_revision": 4,
        "revised": 3,
        "ready": 1
      }
    },
    "latest_documents": [
      {
        "id": 1,
        "title": "Judul Dokumen",
        "user": { "id": 1, "name": "User A", "email": "usera@example.com" },
        "document_type": { "id": 1, "name": "article", "label": "Artikel Ilmiah" }
      }
    ],
    "latest_analyses": []
  }
}
```

---

## Admin Users

### List Users

Mendapatkan daftar semua user dengan filter dan pagination.

```http
GET /api/admin/users?search=&role=user&is_active=true&per_page=15
Authorization: Bearer <token>
```

**Query Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|:--------:|-------------|
| `search` | string | ❌ | Cari berdasarkan nama atau email |
| `role` | string | ❌ | Filter role: `user` atau `admin` |
| `is_active` | boolean | ❌ | Filter status aktif |
| `per_page` | integer | ❌ | Jumlah per halaman (1-100, default: 15) |

**Response `200 OK`:**

```json
{
  "data": [
    {
      "id": 1,
      "name": "User A",
      "email": "usera@example.com",
      "role": "user",
      "is_active": true,
      "created_at": "2026-06-19T10:00:00.000000Z",
      "documents_count": 3
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 15,
    "total": 8,
    "from": 1,
    "to": 8
  }
}
```

---

### Update User Status

Mengaktifkan atau menonaktifkan user.

```http
PUT /api/admin/users/{user_id}/status
Authorization: Bearer <token>
Content-Type: application/json
```

**Request Body:**

| Field | Type | Required | Description |
|-------|------|:--------:|-------------|
| `is_active` | boolean | ✅ | `true` untuk aktifkan, `false` untuk nonaktifkan |

**Response `200 OK`:**

```json
{
  "message": "User berhasil dinonaktifkan.",
  "data": {
    "id": 1,
    "name": "User A",
    "is_active": false
  }
}
```

**Response `422 Unprocessable Entity`** (admin nonaktifkan diri sendiri):

```json
{
  "message": "Admin tidak dapat menonaktifkan akunnya sendiri."
}
```

---

## Admin Documents

### List All Documents

Mendapatkan daftar semua dokumen dari seluruh user.

```http
GET /api/admin/documents?search=&document_type=article&status=analyzed&per_page=15
Authorization: Bearer <token>
```

**Query Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|:--------:|-------------|
| `search` | string | ❌ | Cari berdasarkan judul, topik, nama user, atau email |
| `document_type` | string | ❌ | Filter tipe: `article`, `proposal`, `report` |
| `status` | string | ❌ | Filter status: `uploaded`, `analyzed`, `need_revision`, `revised`, `ready`, `archived` |
| `per_page` | integer | ❌ | Jumlah per halaman (1-100, default: 15) |

**Response `200 OK`:**

```json
{
  "data": [
    {
      "id": 1,
      "title": "Judul Dokumen",
      "status": "analyzed",
      "latest_score": 85,
      "user": { "id": 1, "name": "User A", "email": "usera@example.com", "is_active": true },
      "document_type": { "id": 1, "name": "article", "label": "Artikel Ilmiah" },
      "versions_count": 2,
      "analysis_results_count": 1,
      "reviewer_comments_count": 3,
      "created_at": "2026-06-19T10:00:00.000000Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 15,
    "total": 15,
    "from": 1,
    "to": 15
  }
}
```

---

## Admin Journals

### Journal Stats

Statistik data jurnal.

```http
GET /api/admin/journals/stats
Authorization: Bearer <token>
```

**Response `200 OK`:**

```json
{
  "data": {
    "total": 120,
    "active": 80,
    "pending_review": 30,
    "verified": 90,
    "ai_ready": 45,
    "minimum_ai_score": 70,
    "by_sinta": [
      { "sinta_level": "S1", "total": 10 },
      { "sinta_level": "S2", "total": 25 },
      { "sinta_level": "S3", "total": 30 },
      { "sinta_level": "Belum diisi", "total": 5 }
    ]
  }
}
```

---

### List Journals

Mendapatkan daftar jurnal dengan filter dan pagination.

```http
GET /api/admin/journals?search=&sinta_level=S2&verification_status=verified&is_active=true
Authorization: Bearer <token>
```

**Query Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|:--------:|-------------|
| `search` | string | ❌ | Cari berdasarkan nama, publisher, subject area, keywords, focus scope |
| `sinta_level` | string | ❌ | Filter level SINTA: `S1`-`S6` |
| `verification_status` | string | ❌ | Filter status: `pending_review`, `verified`, `rejected` |
| `is_active` | boolean | ❌ | Filter status aktif |

**Response `200 OK`:**

```json
{
  "data": [
    {
      "id": 1,
      "name": "Jurnal Ilmu Komputer",
      "publisher": "Universitas Contoh",
      "sinta_level": "S2",
      "subject_area": "Computer Science",
      "is_active": true,
      "verification_status": "verified",
      "eligibility_score": 85
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 6,
    "per_page": 20,
    "total": 120,
    "from": 1,
    "to": 20
  }
}
```

---

### Create Journal

Menambahkan data jurnal secara manual.

```http
POST /api/admin/journals
Authorization: Bearer <token>
Content-Type: application/json
```

**Request Body:**

| Field | Type | Required | Description |
|-------|------|:--------:|-------------|
| `name` | string | ✅ | Nama jurnal, maks 255 karakter |
| `publisher` | string | ❌ | Penerbit, maks 255 karakter |
| `sinta_level` | string | ❌ | Level SINTA: `S1`-`S6` |
| `subject_area` | string | ❌ | Bidang subjek, maks 255 karakter |
| `focus_scope` | string | ❌ | Ruang lingkup fokus |
| `keywords` | string | ❌ | Kata kunci |
| `p_issn` | string | ❌ | ISSN cetak, maks 50 karakter |
| `e_issn` | string | ❌ | ISSN online, maks 50 karakter |
| `website_url` | string (URL) | ❌ | URL website jurnal |
| `editor_url` | string (URL) | ❌ | URL editorial board |
| `template_url` | string (URL) | ❌ | URL template |
| `author_guideline_url` | string (URL) | ❌ | URL panduan penulis |
| `indexing` | string | ❌ | Informasi indexing |
| `impact` | string | ❌ | Impact factor, maks 255 karakter |
| `h5_index` | string | ❌ | H5-index, maks 255 karakter |
| `citations_5yr` | string | ❌ | Sitasi 5 tahun, maks 255 karakter |
| `citations_total` | string | ❌ | Total sitasi, maks 255 karakter |
| `source_url` | string (URL) | ❌ | URL sumber data |
| `raw_text` | string | ❌ | Teks mentah (untuk ekstraksi publisher) |
| `last_verified_at` | date | ❌ | Tanggal verifikasi terakhir |

**Response `201 Created`:**

```json
{
  "message": "Data jurnal berhasil ditambahkan.",
  "data": {
    "id": 121,
    "name": "Jurnal Baru",
    "sinta_level": "S3",
    "is_active": false,
    "verification_status": "pending_review",
    "eligibility_score": 30
  }
}
```

---

### Import Journals from CSV

Mengimpor data jurnal dari file CSV.

```http
POST /api/admin/journals/import
Authorization: Bearer <token>
Content-Type: multipart/form-data
```

**Request Fields:**

| Key | Type | Required | Description |
|-----|------|:--------:|-------------|
| `file` | file | ✅ | File CSV atau TXT dengan header |

Header CSV yang didukung:

| Column | Required | Description |
|--------|:--------:|-------------|
| `name` | ✅ | Nama jurnal |
| `publisher` | ❌ | Penerbit |
| `sinta_level` | ❌ | S1-S6 |
| `subject_area` | ❌ | Bidang subjek |
| `focus_scope` | ❌ | Ruang lingkup |
| `keywords` | ❌ | Kata kunci |
| `p_issn` | ❌ | ISSN cetak |
| `e_issn` | ❌ | ISSN online |
| `website_url` | ❌ | URL (harus http/https) |
| `editor_url` | ❌ | URL (harus http/https) |
| `template_url` | ❌ | URL (harus http/https) |
| `author_guideline_url` | ❌ | URL (harus http/https) |
| `indexing` | ❌ | Indexing |
| `impact` | ❌ | Impact factor |
| `h5_index` | ❌ | H5-index |
| `citations_5yr` | ❌ | Sitasi 5 tahun |
| `citations_total` | ❌ | Total sitasi |
| `source_url` | ❌ | URL sumber |
| `raw_text` | ❌ | Teks mentah |
| `last_verified_at` | ❌ | Tanggal verifikasi |

**Response `200 OK`:**

```json
{
  "message": "Import CSV jurnal selesai.",
  "summary": {
    "imported": 95,
    "updated": 20,
    "failed": 5
  },
  "errors": [
    {
      "name": "Jurnal X",
      "errors": ["sinta_level harus S1 sampai S6"]
    }
  ]
}
```

---

### Update Journal

```http
PUT /api/admin/journals/{journal_id}
Authorization: Bearer <token>
Content-Type: application/json
```

**Request Body:** sama dengan Create Journal, semua field opsional.

**Response `200 OK`:**

```json
{
  "message": "Data jurnal berhasil diperbarui.",
  "data": {
    "id": 121,
    "name": "Jurnal Baru (diperbarui)",
    "is_active": true,
    "verification_status": "verified",
    "eligibility_score": 85
  }
}
```

---

### Delete Journal

```http
DELETE /api/admin/journals/{journal_id}
Authorization: Bearer <token>
```

**Response `200 OK`:**

```json
{
  "message": "Data jurnal berhasil dihapus."
}
```

---

## Admin Rubrics

### Update Rubric

Memperbarui rubrik AI untuk suatu tipe dokumen.

```http
PUT /api/admin/rubrics/{rubric_id}
Authorization: Bearer <token>
Content-Type: application/json
```

**Request Body (semua opsional):**

| Field | Type | Required | Description |
|-------|------|:--------:|-------------|
| `aspect_name` | string | ❌ | Nama aspek, maks 255 karakter |
| `weight` | integer | ❌ | Bobot penilaian (0-100) |
| `description` | string | ❌ | Deskripsi aspek (bisa null) |
| `is_active` | boolean | ❌ | Aktif/nonaktifkan aspek |

**Response `200 OK`:**

```json
{
  "message": "Rubrik berhasil diperbarui.",
  "data": {
    "id": 1,
    "document_type_id": 1,
    "aspect_name": "Referensi",
    "weight": 10,
    "description": "Referensi relevan, mutakhir, dan konsisten.",
    "is_active": false,
    "document_type": {
      "id": 1,
      "name": "article",
      "label": "Artikel Ilmiah"
    }
  }
}
```

---

## Error Reference

### HTTP Status Codes

| Status | Description |
|--------|-------------|
| `200` | Request berhasil |
| `201` | Resource berhasil dibuat |
| `401` | Tidak ada token atau token tidak valid |
| `403` | Tidak punya akses ke resource |
| `404` | Resource tidak ditemukan |
| `422` | Validasi request body gagal |
| `500` | Internal server error |
| `502` | AI provider tidak tersedia |

### Standard Error Response

```json
{
  "message": "Pesan error deskriptif dalam Bahasa Indonesia."
}
```

### Standard Validation Error Response

```json
{
  "message": "Validasi gagal.",
  "errors": {
    "field_name": [
      "Deskripsi error untuk field ini."
    ]
  }
}
```

---

## Alur Penggunaan Umum

### User Flow

1. **Register** → `POST /api/register`
2. **Login** → `POST /api/login` → simpan token
3. **Get document types** → `GET /api/document-types`
4. **Upload document** → `POST /api/documents`
5. **Run analysis** → `POST /api/documents/{id}/analyze`
6. **Upload revision** → `POST /api/documents/{id}/versions`
7. **Compare versions** → `GET /api/documents/{id}/comparison`
8. **(Article only) Reviewer mapping** → Reviewer comments → Responses → Matrix → Letter
9. **(Article only) Journal recommendations** → `POST /api/documents/{id}/journal-recommendations`

### Admin Flow

1. **Login** as admin
2. **Dashboard** → `GET /api/admin/dashboard`
3. **Manage users** → `GET /api/admin/users` → `PUT /api/admin/users/{id}/status`
4. **Manage documents** → `GET /api/admin/documents`
5. **Manage rubrics** → `PUT /api/admin/rubrics/{id}`
6. **Manage journals** → CRUD + Import CSV
