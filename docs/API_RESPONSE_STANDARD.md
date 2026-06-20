# API Response Standard — AcadCheck AI

Dokumen ini mendefinisikan **response envelope standar** untuk seluruh endpoint API.

---

## 1. Response Envelope

Semua response **harus** menggunakan format envelope berikut:

### Success Response

```json
{
  "success": true,
  "message": "Pesan deskriptif dalam Bahasa Indonesia.",
  "data": { ... }
}
```

Atau untuk list/collection:

```json
{
  "success": true,
  "message": "Data berhasil dimuat.",
  "data": [ ... ]
}
```

### Error Response

```json
{
  "success": false,
  "message": "Pesan error deskriptif.",
  "errors": { ... }
}
```

### Validation Error Response

```json
{
  "success": false,
  "message": "Validasi gagal.",
  "errors": {
    "field_name": [
      "Error pertama untuk field ini.",
      "Error kedua untuk field ini."
    ]
  }
}
```

---

## 2. Field Definitions

| Field | Type | Wajib | Deskripsi |
|-------|------|:-----:|-----------|
| `success` | `boolean` | ✅ | `true` jika request berhasil, `false` jika gagal |
| `message` | `string` | ✅ | Pesan deskriptif dalam Bahasa Indonesia |
| `data` | `object` / `array` | ❌ | Data response utama. Hadir hanya saat `success: true` |
| `errors` | `object` | ❌ | Detail error per field. Hadir hanya saat `success: false` |

---

## 3. Response Examples Per Kategori

### 3.1 Single Resource

```json
{
  "success": true,
  "message": "Detail dokumen berhasil dimuat.",
  "data": {
    "id": 1,
    "title": "Judul Dokumen",
    "status": "analyzed",
    "created_at": "2026-06-19T10:00:00.000000Z"
  }
}
```

### 3.2 Collection / List

```json
{
  "success": true,
  "message": "Daftar dokumen berhasil dimuat.",
  "data": [
    {
      "id": 1,
      "title": "Dokumen 1",
      "status": "analyzed"
    },
    {
      "id": 2,
      "title": "Dokumen 2",
      "status": "uploaded"
    }
  ]
}
```

### 3.3 Paginated Collection

```json
{
  "success": true,
  "message": "Daftar user berhasil dimuat.",
  "data": [
    { "id": 1, "name": "User A" },
    { "id": 2, "name": "User B" }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 15,
    "total": 72,
    "from": 1,
    "to": 15
  }
}
```

### 3.4 Created Resource (201)

```json
{
  "success": true,
  "message": "Dokumen berhasil diunggah.",
  "data": {
    "id": 1,
    "title": "Judul Dokumen"
  }
}
```

### 3.5 Validation Error (422)

```json
{
  "success": false,
  "message": "Validasi gagal.",
  "errors": {
    "email": ["Email sudah terdaftar."],
    "password": ["Password minimal 8 karakter."]
  }
}
```

### 3.6 Authentication Error (401)

```json
{
  "success": false,
  "message": "Email atau password salah."
}
```

### 3.7 Authorization Error (403)

```json
{
  "success": false,
  "message": "Anda tidak memiliki akses ke sumber daya ini."
}
```

### 3.8 Not Found (404)

```json
{
  "success": false,
  "message": "Sumber daya tidak ditemukan."
}
```

### 3.9 Server Error (500)

```json
{
  "success": false,
  "message": "Terjadi kesalahan internal server."
}
```

### 3.10 AI Provider Error (502)

```json
{
  "success": false,
  "message": "Layanan AI sedang tidak tersedia. Silakan coba kembali."
}
```

---

## 4. Current Deviation Status

| Endpoint Group | Mengikuti Standar? | Catatan |
|----------------|:------------------:|---------|
| Auth (login/register) | ❌ | Menggunakan `{message, user, token}` bukan `{success, message, data}` |
| Auth (logout/me) | ⚠️ | `me` pakai `{data}`, `logout` pakai `{message}` — tidak ada `success` |
| Semua resource endpoints | ⚠️ | Pakai `{message, data}` — tidak ada `success` field |
| Admin JournalController | ⚠️ | Beberapa response tidak konsisten |

### Migration Plan

1. Tambahkan `success` field ke semua response
2. Ubah response `login`/`register` dari `{message, user, token}` → `{success, message, data: {user, token}}`
3. Ubah response `me` dari `{data}` → `{success, message, data}`
4. Ubah response `logout` dari `{message}` → `{success, message}`
5. Standarisasi semua error response dengan `success: false`
6. Pindahkan pagination metadata ke key `meta` (terpisah dari `data`)
