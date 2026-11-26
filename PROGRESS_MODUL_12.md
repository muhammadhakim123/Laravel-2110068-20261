# Progress Pengerjaan Modul 12
## Update Profil, Ganti Password, dan Upload Foto WebP

**Nama Proyek Backend :** `backend2110068`  
**Nama Proyek Mobile  :** `Marketplace2110068`  
**Lokasi Backend      :** `c:\laragon\www\backend2110068`  
**Lokasi Mobile       :** `c:\laragon\www\Marketplace2110068`  
**Database            :** `dbapihakim` (MySQL, Laragon)  
**IPv4 Wi-Fi Laptop   :** `192.168.1.198`  
**Port Server         :** `8000`  
**Tanggal Dikerjakan  :** 26 November 2026  

---

## ✅ Checklist Pengerjaan Backend

| # | Langkah | Status |
|---|---------|--------|
| 1 | Buat `app/Services/ProfilePhotoService.php` (crop, resize, konversi WebP via PHP GD) | ✅ Selesai |
| 2 | Buat `app/Http/Controllers/Api/ProfileController.php` (updateProfile, updatePassword, updatePhoto) | ✅ Selesai |
| 3 | Update `routes/api.php` — tambah 3 route baru di grup `auth:sanctum` | ✅ Selesai |
| 4 | Jalankan `php artisan storage:link` (symlink `public/storage`) | ✅ Selesai |
| 5 | Verifikasi 7 route dengan `php artisan route:list --path=api` | ✅ Selesai |

---

## ✅ Checklist Pengerjaan Mobile (Expo)

| # | Langkah | Status |
|---|---------|--------|
| 1 | Install `expo-image-picker` dan `expo-file-system` | ✅ Selesai |
| 2 | Update `app.json` — tambah plugin `expo-image-picker` (izin kamera & galeri) | ✅ Selesai |
| 3 | Update `src/config/api.ts` — tambah `API_ORIGIN`, 3 endpoint baru, `getStorageImageUrl()` | ✅ Selesai |
| 4 | Update `src/services/authService.ts` — tambah `fetchProfileApi`, `updateProfileApi`, `updatePasswordApi`, `uploadProfilePhotoApi`, `updateStoredUser` | ✅ Selesai |
| 5 | Update `src/styles/tabStyles.ts` — tambah style avatar besar, tombol foto, segment tab, form input | ✅ Selesai |
| 6 | Update `src/app/(tabs)/profil.tsx` — foto profil (kamera/galeri), update profil, ganti sandi, logout | ✅ Selesai |
| 7 | Update `src/app/(tabs)/home.tsx` — ganti `useEffect` → `useFocusEffect` agar nama/email auto-refresh | ✅ Selesai |

---

## Daftar Endpoint API (7 Route)

| Method | URL | Auth | Fungsi |
|--------|-----|------|--------|
| `POST` | `http://192.168.1.198:8000/api/register` | ❌ Publik | Daftar akun baru |
| `POST` | `http://192.168.1.198:8000/api/login` | ❌ Publik | Login & terima token |
| `GET` | `http://192.168.1.198:8000/api/profile` | ✅ Bearer | Ambil data profil dari server |
| `PUT` | `http://192.168.1.198:8000/api/profile` | ✅ Bearer | Update nama, email, no HP |
| `PUT` | `http://192.168.1.198:8000/api/profile/password` | ✅ Bearer | Ganti kata sandi |
| `POST` | `http://192.168.1.198:8000/api/profile/photo` | ✅ Bearer | Upload foto profil (multipart) |
| `POST` | `http://192.168.1.198:8000/api/logout` | ✅ Bearer | Logout & hapus token |

---

## File Backend yang Dibuat / Dimodifikasi

### 1. `app/Services/ProfilePhotoService.php` *(File Baru)*

Service murni pengolahan gambar menggunakan ekstensi PHP GD:

- Auto-rotate EXIF (foto portrait dari kamera HP)
- Center-crop persegi 1:1
- Resize maksimal 512×512 px
- Konversi ke **WebP** kualitas 80 (fallback JPEG jika GD tidak support WebP)
- Hapus foto profil lama dari `storage/app/public/profiles/`
- Simpan ke `storage/app/public/profiles/user_{id}_{timestamp}.webp`

```php
<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class ProfilePhotoService
{
    public function storeAndOptimize(UploadedFile $file, User $user): string
    {
        // ... crop, resize, konversi WebP via PHP GD
    }
}
```

### 2. `app/Http/Controllers/Api/ProfileController.php` *(File Baru)*

| Fungsi | Method | Endpoint | Keterangan |
|--------|--------|----------|------------|
| `updateProfile` | PUT | `/api/profile` | Update nama, email, no_hp |
| `updatePassword` | PUT | `/api/profile/password` | Ganti sandi dengan verifikasi sandi lama |
| `updatePhoto` | POST | `/api/profile/photo` | Upload foto, validasi MIME, konversi WebP |

```php
<?php

namespace App\Http\Controllers\Api;

class ProfileController extends Controller
{
    public function __construct(private ProfilePhotoService $photoService) {}

    public function updateProfile(Request $request) { /* ... */ }
    public function updatePassword(Request $request) { /* ... */ }
    public function updatePhoto(Request $request)    { /* ... */ }
}
```

**Validasi foto:** `mimes:jpg,jpeg,png,webp` + `mimetypes:image/jpeg,image/png,image/webp` + `max:5120` (5 MB)  
**Validasi ganti sandi:** Verifikasi `current_password` via `Hash::check()`, password baru di-hash dengan `Hash::make()`

### 3. `routes/api.php` *(Dimodifikasi)*

```php
<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProfileController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/profile',          [AuthController::class,    'profile']);
    Route::put('/profile',          [ProfileController::class, 'updateProfile']);
    Route::put('/profile/password', [ProfileController::class, 'updatePassword']);
    Route::post('/profile/photo',   [ProfileController::class, 'updatePhoto']);
    Route::post('/logout',          [AuthController::class,    'logout']);
});
```

---

## File Mobile yang Dibuat / Dimodifikasi

### 1. `src/config/api.ts` *(Dimodifikasi)*

```ts
export const API_BASE_URL = "http://192.168.1.198:8000/api";
export const API_ORIGIN   = API_BASE_URL.replace(/\/api\/?$/, "");

export const API_ENDPOINTS = {
  LOGIN:            `${API_BASE_URL}/login`,
  REGISTER:         `${API_BASE_URL}/register`,
  PROFILE:          `${API_BASE_URL}/profile`,
  UPDATE_PROFILE:   `${API_BASE_URL}/profile`,
  UPDATE_PASSWORD:  `${API_BASE_URL}/profile/password`,
  UPDATE_PHOTO:     `${API_BASE_URL}/profile/photo`,
  LOGOUT:           `${API_BASE_URL}/logout`,
};

// Konversi path relatif foto (profiles/user_1_xxx.webp)
// menjadi URL lengkap (http://192.168.1.198:8000/storage/profiles/user_1_xxx.webp)
export function getStorageImageUrl(path?: string | null): string | null {
  if (!path) return null;
  if (path.startsWith("http://") || path.startsWith("https://")) return path;
  return `${API_ORIGIN}/storage/${path.replace(/^\/+/, "")}`;
}
```

### 2. `src/services/authService.ts` *(Dimodifikasi)*

Fungsi baru yang ditambahkan:

| Fungsi | Keterangan |
|--------|------------|
| `fetchProfileApi(token)` | GET profil terbaru dari server |
| `updateProfileApi(token, payload)` | PUT update nama, email, no_hp |
| `updatePasswordApi(token, payload)` | PUT ganti sandi (current + new + confirm) |
| `uploadProfilePhotoApi(token, asset)` | POST upload foto multipart (expo-file-system SDK 58+) |
| `updateStoredUser(user)` | Update cache user di AsyncStorage tanpa hapus token |
| `getAuthSession()` | Ambil `{token, user}` dari AsyncStorage sekaligus |
| `clearAuthSession()` | Hapus token + user dari AsyncStorage |

**Catatan penting upload foto (Expo SDK 58+):**
```ts
// Native: gunakan expo-file-system File, bukan {uri, name, type} gaya lama
import { File } from "expo-file-system";
const file = new File(asset.uri);
formData.append("foto", file, fileName); // fileName wajib disertakan
```

### 3. `src/styles/tabStyles.ts` *(Dimodifikasi)*

Style baru yang ditambahkan:
- `profileHeaderCard` — card container avatar
- `profileAvatar` — avatar 92×92 px dengan `overflow: hidden`
- `profileAvatarImage` — gambar penuh dalam avatar
- `avatarOverlayLoading` — overlay loading saat upload
- `photoActionRow` / `photoActionButton` — tombol Kamera & Galeri
- `segmentContainer` / `segmentButton` — tab switcher Data Profil / Ganti Sandi
- `formGroup` / `formLabel` / `inputWrapper` / `textInput` — form input
- `saveButton` / `saveButtonDisabled` / `saveButtonText` — tombol simpan

### 4. `src/app/(tabs)/profil.tsx` *(Ditulis Ulang)*

Fitur lengkap:
- **Avatar foto profil** — tampil dari server (`getStorageImageUrl`) atau preview lokal (`localPhotoUri`) sebelum upload selesai
- **Tombol Kamera** — `ImagePicker.launchCameraAsync()` dengan izin runtime
- **Tombol Galeri** — `ImagePicker.launchImageLibraryAsync()` dengan izin runtime
- **Tab Data Profil** — form nama, email, no HP + tombol Simpan → `PUT /api/profile`
- **Tab Ganti Sandi** — form sandi lama, baru, konfirmasi + tombol Simpan → `PUT /api/profile/password`
- **useFocusEffect** — sinkronisasi otomatis dengan server setiap kali tab dibuka
- **Tombol Logout** — konfirmasi dialog → `POST /api/logout` → redirect ke `/login`

### 5. `src/app/(tabs)/home.tsx` *(Dimodifikasi)*

Ganti `useEffect` → `useFocusEffect` agar nama dan email user langsung terupdate di Beranda setelah profil diubah dari tab Profil.

---

## Cara Menjalankan

```bash
# Terminal 1 — Backend Laravel
cd c:\laragon\www\backend2110068
php artisan serve --host=0.0.0.0 --port=8000

# Terminal 2 — Mobile Expo
cd c:\laragon\www\Marketplace2110068
npx expo start
```

> **Pastikan Laragon (MySQL) sudah dinyalakan** sebelum menjalankan Laravel.  
> Jika pindah jaringan Wi-Fi, update `API_BASE_URL` di `src/config/api.ts` sesuai IP baru dari `ipconfig`.

---

## Alur Upload Foto Profil

```
HP (Kamera/Galeri)
  → expo-image-picker (crop 1:1, quality 0.85)
  → Preview lokal langsung tampil di avatar
  → FormData multipart POST /api/profile/photo
  → Laravel: validasi MIME + max 5 MB
  → ProfilePhotoService: auto-rotate EXIF → crop → resize 512px → WebP
  → Simpan ke storage/app/public/profiles/user_{id}_{ts}.webp
  → Hapus foto lama
  → Response JSON: {success, user, foto_url}
  → Avatar update dari URL server
```

---

## Troubleshooting

| Masalah | Penyebab | Solusi |
|---------|----------|--------|
| `500 Internal Server Error` di `/api/profile` | `ProfileController.php` belum disalin atau cache lama | Salin file, jalankan `php artisan optimize:clear` |
| `404` di `PUT /api/profile` | `routes/api.php` belum diupdate | Ganti isi `routes/api.php` dengan versi Modul 12 |
| Foto tidak muncul setelah upload | `storage:link` belum dijalankan | Jalankan `php artisan storage:link` |
| `Ekstensi PHP GD belum aktif` | GD tidak aktif di php.ini Laragon | Aktifkan `extension=gd` di php.ini, restart server |
| Upload foto > 2 MB gagal | `upload_max_filesize` PHP masih 2M | Set `upload_max_filesize = 8M` dan `post_max_size = 10M` di php.ini |
| `Unsupported FormDataPart implementation` | Expo SDK 58+, cara lama tidak didukung | Gunakan `new File(uri)` dari `expo-file-system` (sudah diterapkan) |
| Nama tidak update di tab Beranda | `useEffect` tidak re-run saat pindah tab | Sudah diganti `useFocusEffect` di `home.tsx` |
| IP tidak bisa diakses dari HP | IP di `api.ts` sudah berubah | Jalankan `ipconfig`, update `API_BASE_URL` |
