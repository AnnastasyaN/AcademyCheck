# API Status Code Standard — AcadCheck AI

Dokumen ini mendefinisikan **HTTP status code standar** yang digunakan di seluruh endpoint API.

---

## 1. Status Code Reference

| Status | Meaning | Kapan Digunakan |
|--------|---------|-----------------|
| `200 OK` | Request berhasil | GET, PUT, DELETE sukses |
| `201 Created` | Resource berhasil dibuat | POST sukses (register, upload dokumen, dll) |
| `400 Bad Request` | Request tidak valid | (Saat ini belum digunakan — potensi ke depan) |
| `401 Unauthorized` | Tidak ada token / token invalid | Login gagal, token tidak dikirim |
| `403 Forbidden` | Tidak punya akses | User biasa akses endpoint admin, akses dokumen milik orang lain |
| `404 Not Found` | Resource tidak ditemukan | Document, user, atau resource spesifik tidak ada |
| `422 Unprocessable Entity` | Validasi gagal | Request body tidak memenuhi validation rules |
| `500 Internal Server Error` | Error server tidak terduga | Exception tidak terhandle |
| `502 Bad Gateway` | Error dari AI provider | AI analysis / recommendation / comment parsing gagal dari sisi AI |

---

## 2. Status Code Per Skenario

### 2.1 Success Scenarios

| Skenario | Method | Status Code |
|----------|--------|:-----------:|
| Membaca satu resource | `GET` | `200` |
| Membaca collection | `GET` | `200` |
| Membuat resource baru | `POST` | `201` |
| Mengupdate resource | `PUT` | `200` |
| Menghapus resource | `DELETE` | `200` |
| Generate / aksi khusus | `POST` | `200` atau `201` |

### 2.2 Error Scenarios

| Skenario | Status Code |
|----------|:-----------:|
| Token tidak dikirim / invalid | `401` |
| Login gagal (wrong password) | `401` |
| Akun tidak aktif | `403` |
| Akses resource milik user lain | `403` |
| User biasa akses admin endpoint | `403` |
| Resource tidak ditemukan (ID salah) | `404` |
| Hasil analisis belum tersedia | `404` |
| Validation error pada request body | `422` |
| Dokumen belum punya extracted text | `422` |
| Rubrik aktif tidak tersedia | `422` |
| Dokumen bukan artikel (untuk fitur article-only) | `422` |
| Admin menonaktifkan akun sendiri | `422` |
| AI provider error | `502` |
| AI response format invalid | `502` |
| Internal server error | `500` |

---

## 3. Status Code Map Per Endpoint

### Authentication

| Endpoint | Method | 200 | 201 | 401 | 403 | 422 | 500 |
|----------|:-----:|:---:|:---:|:---:|:---:|:---:|:---:|
| `/api/register` | POST | — | ✅ Register berhasil | — | — | ✅ Validasi gagal | ✅ |
| `/api/login` | POST | ✅ Login berhasil | — | ✅ Email/password salah | ✅ Akun tidak aktif | ✅ Validasi gagal | ✅ |
| `/api/logout` | POST | ✅ Logout berhasil | — | ✅ | — | — | ✅ |
| `/api/me` | GET | ✅ Data user | — | ✅ | — | — | ✅ |

### Dashboard

| Endpoint | Method | 200 | 403 | 500 |
|----------|:-----:|:---:|:---:|:---:|
| `/api/user/dashboard` | GET | ✅ | — | ✅ |
| `/api/test-ai` | GET | ✅ | — | ✅ |

### Document Types & Rubrics

| Endpoint | Method | 200 | 401 | 422 | 500 |
|----------|:-----:|:---:|:---:|:---:|:---:|
| `/api/document-types` | GET | ✅ | ✅ | — | ✅ |
| `/api/rubrics` | GET | ✅ | ✅ | ✅ | ✅ |

### Documents

| Endpoint | Method | 200 | 201 | 401 | 403 | 404 | 422 | 500 |
|----------|:-----:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|
| `/api/documents` | GET | ✅ | — | ✅ | — | — | — | ✅ |
| `/api/documents` | POST | — | ✅ | ✅ | — | — | ✅ | ✅ |
| `/api/documents/{document}` | GET | ✅ | — | ✅ | ✅ | ✅ | — | ✅ |
| `/api/documents/{document}` | PUT | ✅ | — | ✅ | ✅ | ✅ | ✅ | ✅ |
| `/api/documents/{document}` | DELETE | ✅ | — | ✅ | ✅ | ✅ | — | ✅ |

### AI Analysis

| Endpoint | Method | 200 | 201 | 401 | 403 | 404 | 422 | 500 | 502 |
|----------|:-----:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|
| `/api/documents/{document}/analyze` | POST | — | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| `/api/documents/{document}/analysis` | GET | ✅ | — | ✅ | ✅ | ✅ | — | ✅ | — |

### Document Versions

| Endpoint | Method | 200 | 201 | 401 | 403 | 422 | 500 |
|----------|:-----:|:---:|:---:|:---:|:---:|:---:|:---:|
| `/api/documents/{document}/versions` | GET | ✅ | — | ✅ | ✅ | — | ✅ |
| `/api/documents/{document}/versions` | POST | — | ✅ | ✅ | ✅ | ✅ | ✅ |

### Comparison

| Endpoint | Method | 200 | 401 | 403 | 422 | 500 |
|----------|:-----:|:---:|:---:|:---:|:---:|:---:|
| `/api/documents/{document}/comparison` | GET | ✅ | ✅ | ✅ | ✅ | ✅ |

### Journal Recommendations

| Endpoint | Method | 200 | 201 | 401 | 403 | 422 | 500 | 502 |
|----------|:-----:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|
| `/api/documents/{document}/journal-recommendations` | GET | ✅ | — | ✅ | ✅ | ✅ | ✅ | — |
| `/api/documents/{document}/journal-recommendations` | POST | — | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |

### Reviewer Comments

| Endpoint | Method | 200 | 201 | 401 | 403 | 404 | 422 | 500 | 502 |
|----------|:-----:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|
| `/api/articles/{document}/reviewer-comments` | GET | ✅ | — | ✅ | ✅ | — | ✅ | ✅ | — |
| `/api/articles/{document}/reviewer-comments` | POST | — | ✅ | ✅ | ✅ | — | ✅ | ✅ | — |
| `/api/articles/{document}/reviewer-comments/parse` | POST | ✅ | — | ✅ | ✅ | — | ✅ | ✅ | ✅ |
| `/api/reviewer-comments/{reviewerComment}` | PUT | ✅ | — | ✅ | ✅ | ✅ | ✅ | ✅ | — |
| `/api/reviewer-comments/{reviewerComment}/status` | PUT | ✅ | — | ✅ | ✅ | ✅ | ✅ | ✅ | — |
| `/api/reviewer-comments/{reviewerComment}` | DELETE | ✅ | — | ✅ | ✅ | ✅ | — | ✅ | — |

### Reviewer Responses

| Endpoint | Method | 200 | 401 | 403 | 404 | 422 | 500 | 502 |
|----------|:-----:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|
| `/api/reviewer-comments/{reviewerComment}/responses` | POST | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | — |
| `/api/reviewer-comments/{reviewerComment}/generate-response` | POST | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| `/api/articles/{document}/response-matrix` | GET | ✅ | ✅ | ✅ | — | ✅ | ✅ | — |
| `/api/articles/{document}/response-letter` | GET | ✅/PDF | ✅ | ✅ | — | ✅ | ✅ | — |

### Admin — Dashboard

| Endpoint | Method | 200 | 401 | 403 | 500 |
|----------|:-----:|:---:|:---:|:---:|:---:|
| `/api/admin/dashboard` | GET | ✅ | ✅ | ✅ | ✅ |

### Admin — Users

| Endpoint | Method | 200 | 401 | 403 | 404 | 422 | 500 |
|----------|:-----:|:---:|:---:|:---:|:---:|:---:|:---:|
| `/api/admin/users` | GET | ✅ | ✅ | ✅ | — | ✅ | ✅ |
| `/api/admin/users/{user}/status` | PUT | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |

### Admin — Documents

| Endpoint | Method | 200 | 401 | 403 | 422 | 500 |
|----------|:-----:|:---:|:---:|:---:|:---:|:---:|
| `/api/admin/documents` | GET | ✅ | ✅ | ✅ | ✅ | ✅ |

### Admin — Journals

| Endpoint | Method | 200 | 201 | 401 | 403 | 404 | 422 | 500 |
|----------|:-----:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|
| `/api/admin/journals/stats` | GET | ✅ | — | ✅ | ✅ | — | — | ✅ |
| `/api/admin/journals` | GET | ✅ | — | ✅ | ✅ | — | — | ✅ |
| `/api/admin/journals` | POST | — | ✅ | ✅ | ✅ | — | ✅ | ✅ |
| `/api/admin/journals/import` | POST | ✅ | — | ✅ | ✅ | — | ✅ | ✅ |
| `/api/admin/journals/{journal}` | PUT | ✅ | — | ✅ | ✅ | ✅ | ✅ | ✅ |
| `/api/admin/journals/{journal}` | DELETE | ✅ | — | ✅ | ✅ | ✅ | — | ✅ |

### Admin — Rubrics

| Endpoint | Method | 200 | 401 | 403 | 404 | 422 | 500 |
|----------|:-----:|:---:|:---:|:---:|:---:|:---:|:---:|
| `/api/admin/rubrics/{rubric}` | PUT | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |

---

## 4. Aturan Umum

1. **Jangan gunakan `200` untuk create** — gunakan `201 Created`
2. **Jangan gunakan `400` untuk validation error** — gunakan `422 Unprocessable Entity`
3. **401 vs 403**: `401` = tidak ada autentikasi, `403` = sudah autentikasi tapi tidak punya hak akses
4. **502 hanya untuk AI provider error** — bukan untuk error internal server
5. **Setiap error response HARUS** memiliki `message` field yang deskriptif dalam Bahasa Indonesia
