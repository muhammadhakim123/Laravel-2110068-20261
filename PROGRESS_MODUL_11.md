# Progress Pengerjaan Modul 11
## Laravel 13 + Sanctum API Token — Backend `backend2110068`

**Nama Proyek Backend :** `backend2110068`  
**Lokasi              :** `c:\laragon\www\backend2110068`  
**Database            :** `dbapihakim` (MySQL, Laragon)  
**IPv4 Wi-Fi Laptop   :** `192.168.1.12`  
**Port Server         :** `8000`  
**Tanggal Dikerjakan  :** 22 November 2026  

---

## ✅ Checklist Pengerjaan Backend

| # | Langkah | Status |
|---|---------|--------|
| 1 | Konfigurasi `.env` ke MySQL (`dbapihakim`) | ✅ Selesai |
| 2 | Tambah kolom `no_hp` & `foto` pada migrasi `users` | ✅ Selesai |
| 3 | Jalankan `php artisan install:api` (Sanctum + `routes/api.php`) | ✅ Selesai |
| 4 | Jalankan `php artisan migrate:fresh` (semua tabel terbuat) | ✅ Selesai |
| 5 | Update `User.php` — aktifkan `HasApiTokens`, tambah `no_hp` & `foto` ke `Fillable` | ✅ Selesai |
| 6 | Buat `app/Http/Controllers/Api/AuthController.php` | ✅ Selesai |
| 7 | Daftarkan 4 endpoint di `routes/api.php` | ✅ Selesai |
| 8 | Publish konfigurasi CORS (`php artisan config:publish cors`) | ✅ Selesai |
| 9 | Uji endpoint `POST /api/register` → response token Sanctum muncul | ✅ Selesai |
| 10 | Uji endpoint `POST /api/login` → response token Sanctum muncul | ✅ Selesai |

---

## Konfigurasi `.env` (Bagian Database)

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=dbapihakim
DB_USERNAME=root
DB_PASSWORD=
```

---

## Tabel yang Terbuat di Database `dbapihakim`

| Tabel | Keterangan |
|-------|------------|
| `users` | Data pengguna — id, name, email, **no_hp**, **foto**, password, timestamps |
| `personal_access_tokens` | Token Sanctum untuk autentikasi API |
| `cache` | Cache Laravel |
| `jobs` | Queue jobs Laravel |
| `sessions` | Sesi aplikasi |
| `password_reset_tokens` | Reset password |

### Struktur Tabel `users`

```
id                  — bigint, primary key, auto increment
name                — varchar(255)
email               — varchar(255), unique
no_hp               — varchar(20), nullable
foto                — varchar(255), nullable
email_verified_at   — timestamp, nullable
password            — varchar(255)
remember_token      — varchar(100), nullable
created_at          — timestamp
updated_at          — timestamp
```

---

## File yang Dibuat / Dimodifikasi

### 1. `database/migrations/0001_01_01_000000_create_users_table.php`
Ditambahkan kolom `no_hp` dan `foto` pada `Schema::create('users', ...)`:

```php
$table->string('no_hp', 20)->nullable();
$table->string('foto')->nullable();
```

### 2. `app/Models/User.php`
Ditambahkan trait `HasApiTokens` dan field `no_hp`, `foto` ke atribut `Fillable`:

```php
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'no_hp', 'foto', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
    // ...
}
```

### 3. `app/Http/Controllers/Api/AuthController.php` *(File Baru)*
Controller API dengan 4 fungsi:

| Fungsi | Method | Endpoint | Keterangan |
|--------|--------|----------|------------|
| `register` | POST | `/api/register` | Daftar akun baru, kembalikan token |
| `login` | POST | `/api/login` | Login, hapus token lama, kembalikan token baru |
| `profile` | GET | `/api/profile` | Ambil data user dari token (protected) |
| `logout` | POST | `/api/logout` | Hapus token aktif dari database (protected) |

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|max:255|unique:users,email',
            'no_hp'    => 'required|string|max:20',
            'foto'     => 'nullable|string|max:255',
            'password' => 'required|string|min:6|confirmed',
        ], [
            'name.required'      => 'Nama lengkap wajib diisi.',
            'email.required'     => 'Alamat email wajib diisi.',
            'email.email'        => 'Format alamat email tidak valid.',
            'email.unique'       => 'Email ini sudah terdaftar, silakan gunakan email lain.',
            'no_hp.required'     => 'Nomor HP wajib diisi.',
            'password.min'       => 'Kata sandi minimal terdiri dari 6 karakter.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak cocok.',
        ]);

        $user = User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'no_hp'    => $validated['no_hp'],
            'foto'     => $validated['foto'] ?? null,
            'password' => $validated['password'],
        ]);

        $token = $user->createToken('mobile-app-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Registrasi akun berhasil.',
            'token'   => $token,
            'user'    => $user,
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ], [
            'email.required'    => 'Email wajib diisi.',
            'email.email'       => 'Format email tidak valid.',
            'password.required' => 'Kata sandi wajib diisi.',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Email atau kata sandi yang Anda masukkan salah.',
            ], 401);
        }

        $user->tokens()->delete();
        $token = $user->createToken('mobile-app-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil.',
            'token'   => $token,
            'user'    => $user,
        ], 200);
    }

    public function profile(Request $request)
    {
        return response()->json([
            'success' => true,
            'user'    => $request->user(),
        ], 200);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Berhasil keluar dan token telah dihapus dari server.',
        ], 200);
    }
}
```

### 4. `routes/api.php`

```php
<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

// Endpoint Publik (Belum punya token)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);

// Endpoint Terproteksi (Wajib membawa Header Authorization: Bearer <token>)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::post('/logout', [AuthController::class, 'logout']);
});
```

### 5. `config/cors.php` *(Dipublish)*

```php
'paths'           => ['api/*', 'sanctum/csrf-cookie'],
'allowed_methods' => ['*'],
'allowed_origins' => ['*'],
'allowed_headers' => ['*'],
```

---

## Daftar Endpoint API

| Method | URL | Auth | Fungsi |
|--------|-----|------|--------|
| `POST` | `http://192.168.1.12:8000/api/register` | ❌ Publik | Daftar akun baru |
| `POST` | `http://192.168.1.12:8000/api/login` | ❌ Publik | Login & terima token |
| `GET` | `http://192.168.1.12:8000/api/profile` | ✅ Bearer Token | Ambil data profil |
| `POST` | `http://192.168.1.12:8000/api/logout` | ✅ Bearer Token | Logout & hapus token |

> Untuk pengujian di **browser/Expo Web laptop**, ganti `192.168.1.12` dengan `127.0.0.1`.  
> Untuk pengujian di **HP fisik via Expo Go**, pastikan HP dan laptop terhubung Wi-Fi yang sama, gunakan `192.168.1.12`.

---

## Contoh Request & Response

### Register — `POST /api/register`

**Request Body (JSON):**
```json
{
  "name": "Muhammad Hakim",
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
  "token": "1|uFBz84o4DKQLmTPLETUoGBugkJbOdmbIEjFCAHCg...",
  "user": {
    "id": 1,
    "name": "Muhammad Hakim",
    "email": "hakim@test.com",
    "no_hp": "081234567890",
    "foto": null,
    "created_at": "2026-11-22T12:21:34.000000Z",
    "updated_at": "2026-11-22T12:21:34.000000Z"
  }
}
```

### Login — `POST /api/login`

**Request Body (JSON):**
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
  "token": "2|mbENqyYKPaPjbLlxfUd77MfP9wBvc2zjOIvrB8rg...",
  "user": {
    "id": 1,
    "name": "Muhammad Hakim",
    "email": "hakim@test.com",
    "no_hp": "081234567890",
    "foto": null
  }
}
```

**Response Gagal (401):**
```json
{
  "success": false,
  "message": "Email atau kata sandi yang Anda masukkan salah."
}
```

### Profile — `GET /api/profile`

**Header:**
```
Authorization: Bearer 2|mbENqyYKPaPjbLlxfUd77MfP9wBvc2zjOIvrB8rg...
Accept: application/json
```

**Response Sukses (200):**
```json
{
  "success": true,
  "user": {
    "id": 1,
    "name": "Muhammad Hakim",
    "email": "hakim@test.com",
    "no_hp": "081234567890",
    "foto": null
  }
}
```

### Logout — `POST /api/logout`

**Header:**
```
Authorization: Bearer 2|mbENqyYKPaPjbLlxfUd77MfP9wBvc2zjOIvrB8rg...
Accept: application/json
```

**Response Sukses (200):**
```json
{
  "success": true,
  "message": "Berhasil keluar dan token telah dihapus dari server."
}
```

---

## Cara Menjalankan Server

```bash
# Masuk ke folder proyek
cd c:\laragon\www\backend2110068

# Jalankan server agar bisa diakses dari HP via Wi-Fi
php artisan serve --host=0.0.0.0 --port=8000
```

> **Pastikan Laragon (MySQL) sudah dinyalakan** sebelum menjalankan server Laravel.

---

## Catatan untuk Sisi Mobile (React Native Expo)

Lanjutkan di proyek Expo dengan konfigurasi berikut:

### `src/config/api.ts`
```ts
// Ganti IP ini sesuai hasil ipconfig di CMD jika pindah jaringan
export const API_BASE_URL = "http://192.168.1.12:8000/api";

export const API_ENDPOINTS = {
  LOGIN:    `${API_BASE_URL}/login`,
  REGISTER: `${API_BASE_URL}/register`,
  PROFILE:  `${API_BASE_URL}/profile`,
  LOGOUT:   `${API_BASE_URL}/logout`,
};
```

### Langkah selanjutnya di Expo:
1. Install AsyncStorage: `npx expo install @react-native-async-storage/async-storage`
2. Buat `src/config/api.ts` (isi IP sesuai `ipconfig`)
3. Buat `src/services/authService.ts`
4. Update `src/styles/authStyles.ts` (tambah style loading)
5. Update `src/app/index.tsx` (cek sesi otomatis)
6. Update `src/app/login.tsx` (hubungkan ke `loginApi()`)
7. Update `src/app/register.tsx` (tambah input `no_hp`, hubungkan ke `registerApi()`)
8. Update `src/app/(tabs)/home.tsx` (tampilkan data user dari AsyncStorage)
9. Update `src/app/(tabs)/profil.tsx` (tampilkan profil + logout ke server)

---

## Troubleshooting

| Masalah | Penyebab | Solusi |
|---------|----------|--------|
| `Tidak dapat terhubung ke server` | IP di `api.ts` salah atau server mati | Cek `ipconfig`, pastikan server jalan dengan `--host=0.0.0.0` |
| `no_hp` bernilai `NULL` padahal diisi | `no_hp` tidak ada di `Fillable` | Cek `#[Fillable]` di `app/Models/User.php` |
| `Email ini sudah terdaftar` | Wajar — validasi dari Laravel berjalan | Gunakan email berbeda atau cek tabel `users` di phpMyAdmin |
| `Unauthenticated` saat akses `/profile` | Token tidak disertakan atau sudah expired | Sertakan header `Authorization: Bearer <token>` |
| HP tidak bisa konek tapi laptop bisa | HP dan laptop beda jaringan | Sambungkan HP dan laptop ke hotspot/Wi-Fi yang sama |
| Port 8000 diblokir | Windows Firewall aktif | Tambahkan exception port 8000 di Windows Firewall |
