# Testing Backend Modul 12
## Update Profil, Ganti Password, Upload Foto WebP

**Sebelum testing, jalankan perintah berikut di terminal Laragon:**

```bash
cd c:\laragon\www\backend2110068

# 1. Verifikasi route terdaftar
php artisan route:list --path=api

# 2. Cek ekstensi PHP GD (harus aktif untuk crop/resize/WebP)
php -r "echo 'GD: '.(extension_loaded('gd')?'AKTIF':'TIDAK AKTIF').PHP_EOL;"
php -r "echo 'WebP: '.(function_exists('imagewebp')?'DIDUKUNG':'TIDAK DIDUKUNG').PHP_EOL;"

# 3. Buat symlink storage (agar foto bisa diakses via URL publik)
php artisan storage:link

# 4. Jalankan server
php artisan serve --host=0.0.0.0 --port=8000
```

---

## 1. Register Akun Baru

**Method:** `POST`  
**URL:** `http://192.168.1.198:8000/api/register`  
**Body (JSON):**
```json
{
  "name": "Hakim Pratama",
  "email": "hakim@test.com",
  "no_hp": "081234567890",
  "password": "123456",
  "password_confirmation": "123456"
}
```

**Response Sukses (201):**
```json
{
  "success": true,
  "message": "Registrasi akun berhasil.",
  "token": "1|abcd1234efgh5678...",
  "user": {
    "id": 1,
    "name": "Hakim Pratama",
    "email": "hakim@test.com",
    "no_hp": "081234567890",
    "foto": null,
    "created_at": "2026-11-26T10:30:00.000000Z",
    "updated_at": "2026-11-26T10:30:00.000000Z"
  }
}
```

**Simpan token** untuk testing endpoint berikutnya.

---

## 2. Login

**Method:** `POST`  
**URL:** `http://192.168.1.198:8000/api/login`  
**Body (JSON):**
```json
{
  "email": "hakim@test.com",
  "password": "123456"
}
```

**Response Sukses (200):**
```json
{
  "success": true,
  "message": "Login berhasil.",
  "token": "2|xyz9876abcd1234...",
  "user": {
    "id": 1,
    "name": "Hakim Pratama",
    "email": "hakim@test.com",
    "no_hp": "081234567890",
    "foto": null
  }
}
```

---

## 3. Ambil Profil

**Method:** `GET`  
**URL:** `http://192.168.1.198:8000/api/profile`  
**Headers:**
```
Authorization: Bearer 2|xyz9876abcd1234...
Accept: application/json
```

**Response Sukses (200):**
```json
{
  "success": true,
  "user": {
    "id": 1,
    "name": "Hakim Pratama",
    "email": "hakim@test.com",
    "no_hp": "081234567890",
    "foto": null,
    "email_verified_at": null,
    "created_at": "2026-11-26T10:30:00.000000Z",
    "updated_at": "2026-11-26T10:30:00.000000Z"
  }
}
```

---

## 4. Update Profil (Nama, Email, No HP)

**Method:** `PUT`  
**URL:** `http://192.168.1.198:8000/api/profile`  
**Headers:**
```
Authorization: Bearer 2|xyz9876abcd1234...
Content-Type: application/json
Accept: application/json
```

**Body (JSON):**
```json
{
  "name": "Hakim Pratama Wijaya",
  "email": "hakim.wijaya@test.com",
  "no_hp": "081234567899"
}
```

**Response Sukses (200):**
```json
{
  "success": true,
  "message": "Profil berhasil diperbarui.",
  "user": {
    "id": 1,
    "name": "Hakim Pratama Wijaya",
    "email": "hakim.wijaya@test.com",
    "no_hp": "081234567899",
    "foto": null,
    "created_at": "2026-11-26T10:30:00.000000Z",
    "updated_at": "2026-11-26T10:35:00.000000Z"
  },
  "foto_url": null
}
```

**Response Error — Email sudah dipakai (422):**
```json
{
  "message": "Email ini sudah digunakan akun lain.",
  "errors": {
    "email": ["Email ini sudah digunakan akun lain."]
  }
}
```

---

## 5. Ganti Password

**Method:** `PUT`  
**URL:** `http://192.168.1.198:8000/api/profile/password`  
**Headers:**
```
Authorization: Bearer 2|xyz9876abcd1234...
Content-Type: application/json
Accept: application/json
```

**Body (JSON):**
```json
{
  "current_password": "123456",
  "password": "654321",
  "password_confirmation": "654321"
}
```

**Response Sukses (200):**
```json
{
  "success": true,
  "message": "Kata sandi berhasil diperbarui."
}
```

**Response Error — Sandi lama salah (422):**
```json
{
  "success": false,
  "message": "Kata sandi lama yang Anda masukkan salah."
}
```

**Response Error — Konfirmasi tidak cocok (422):**
```json
{
  "message": "Konfirmasi kata sandi tidak cocok.",
  "errors": {
    "password": ["Konfirmasi kata sandi tidak cocok."]
  }
}
```

---

## 6. Upload Foto Profil

**Method:** `POST`  
**URL:** `http://192.168.1.198:8000/api/profile/photo`  
**Headers:**
```
Authorization: Bearer 2|xyz9876abcd1234...
Accept: application/json
```

**Body (form-data):**
| Key | Type | Value |
|-----|------|-------|
| `foto` | file | (pilih file: JPG/PNG/WebP, maks 5 MB) |

**Response Sukses (200):**
```json
{
  "success": true,
  "message": "Foto profil berhasil diperbarui.",
  "user": {
    "id": 1,
    "name": "Hakim Pratama Wijaya",
    "email": "hakim.wijaya@test.com",
    "no_hp": "081234567899",
    "foto": "profiles/user_1_1732600123.webp",
    "created_at": "2026-11-26T10:30:00.000000Z",
    "updated_at": "2026-11-26T10:40:00.000000Z"
  },
  "foto_url": "http://192.168.1.198:8000/storage/profiles/user_1_1732600123.webp"
}
```

**Cek foto:**  
Buka URL `foto_url` di browser — file WebP 512×512 px harus tampil.

**Response Error — File terlalu besar (422):**
```json
{
  "message": "Ukuran foto maksimal 5 MB.",
  "errors": {
    "foto": ["Ukuran foto maksimal 5 MB."]
  }
}
```

**Response Error — Format tidak didukung (422):**
```json
{
  "message": "Format foto harus JPG, JPEG, PNG, atau WEBP.",
  "errors": {
    "foto": ["Format foto harus JPG, JPEG, PNG, atau WEBP."]
  }
}
```

---

## 7. Logout

**Method:** `POST`  
**URL:** `http://192.168.1.198:8000/api/logout`  
**Headers:**
```
Authorization: Bearer 2|xyz9876abcd1234...
Accept: application/json
```

**Response Sukses (200):**
```json
{
  "success": true,
  "message": "Berhasil keluar dan token telah dihapus dari server."
}
```

Setelah logout, token yang dipakai tidak bisa digunakan lagi. Coba akses `/api/profile` lagi — harus muncul `401 Unauthenticated`.

---

## Troubleshooting

| Masalah | Solusi |
|---------|--------|
| `Class "App\Services\ProfilePhotoService" not found` | Jalankan `composer dump-autoload` |
| `Call to undefined function imagewebp()` | Aktifkan `extension=gd` di `php.ini`, restart Laragon |
| `500 Internal Server Error` di PUT/POST `/api/profile` | Jalankan `php artisan optimize:clear` |
| Foto tidak muncul di URL `foto_url` | Jalankan `php artisan storage:link` |
| `Allowed memory size exhausted` saat upload foto besar | Set `memory_limit = 256M` di `php.ini` |
| Upload file > 2 MB gagal | Set `upload_max_filesize = 8M` dan `post_max_size = 10M` di `php.ini` |

---

## Checklist Testing

- [ ] Register akun baru → dapat token
- [ ] Login → dapat token baru
- [ ] GET `/api/profile` → data user muncul
- [ ] PUT `/api/profile` → nama/email/no_hp berubah
- [ ] PUT `/api/profile/password` → password lama valid, bisa ganti
- [ ] PUT `/api/profile/password` → password lama salah, ditolak
- [ ] POST `/api/profile/photo` → foto terupload, dapat `foto_url`
- [ ] Buka `foto_url` di browser → gambar WebP 512×512 muncul
- [ ] Upload foto kedua kali → foto lama terhapus dari `storage/app/public/profiles/`
- [ ] POST `/api/logout` → token dihapus, tidak bisa akses `/api/profile` lagi
