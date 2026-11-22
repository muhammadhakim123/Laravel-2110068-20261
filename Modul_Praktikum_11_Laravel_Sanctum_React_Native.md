# Modul Praktikum 11
## Instalasi Laravel 13, Sanctum API Token, dan Integrasi `fetch()` Login & Register

**Mata Kuliah:** Aplikasi Mobile 2 – React Native Expo  
**Penyusun:** Novinaldi, S.Kom., M.Kom.  
**Prasyarat:** Sudah menyelesaikan Modul 10 (Bottom Navigation 5 Tab)  
**Lingkungan:** PHP ≥ 8.3 (Laragon), Composer, MySQL, Expo SDK 58

> Dokumen Markdown ini disusun dari PDF Modul Praktikum 11. Ikuti urutan pengerjaan dari backend Laravel, konfigurasi API, integrasi React Native, lalu pengujian end-to-end. Ganti alamat IP contoh dengan IPv4 laptop sendiri.

---

PRAKTIKUM APLIKASI MOBILE 2 - REACT NATIVE EXPO

Instalasi Laravel 13, Sanctum API Token, dan Integrasi fetch()
Login & Register
Modul 11: Lanjutan dari Modul 10 — Membangun Backend REST API MySQL di
 E:\ReactNativeExpo2026\NovinaldiAppApi serta Memisahkan Layer API di React Native Expo

 Mata Kuliah            Aplikasi Mobile 2                       Penyusun                  Novinaldi, S.Kom., M.Kom.

                        Sudah menyelesaikan Modul 10                                      PHP ≥ 8.3 (Laragon), Composer,
 Prasyarat                                                      Lingkungan
                        (Bottom Navigation 5 Tab)                                         MySQL, Expo SDK 58


## A. Gambaran Besar: Dari Tampilan Statis ke Aplikasi Beneran

Di Modul 10 kemarin, tampilan aplikasi kita sudah jauh lebih rapi. Menu bawahnya sudah ada lima tab, halaman Home
sudah mirip marketplace, dan tombol logout sudah pindah ke halaman Profil. Cuma ada satu hal yang masih kurang:
saat Anda mengetikkan email lalu klik tombol Masuk Sekarang, akunnya belum dicek ke database mana pun—asal terisi
langsung tembus masuk. Begitu juga di halaman Register, datanya belum tersimpan ke mana-mana.

Nah, di Modul 11 inilah kita mulai menyambungkan aplikasi mobile NovinaldiApp ke server database beneran
memakai Laravel 13 dan MySQL. Proyek backend Laravel ini tidak kita campur di dalam folder React Native, melainkan
kita letakkan sejajar di dalam direktori E:\ReactNativeExpo2026\NovinaldiAppApi . Alasannya sederhana: React Native
berjalan di ekosistem Node.js/Expo, sedangkan Laravel berjalan di atas PHP dan Composer. Memisahkan dua folder
proyek ini membuat urusan paket, konfigurasi Git, dan server masing-masing tidak saling bertabrakan.

                            Alur Kerja Mobile ( NovinaldiApp ) ↔ Backend ( NovinaldiAppApi )

        Layar UI
     login.tsx /                                                                                            Database MySQL
                                     Layer API Terpisah                 Laravel 13 + Sanctum
    register.tsx                                                                                          db_novinaldi_app
     Cukup tangkap
                        →     src/services/authService.ts        →      AuthController.php           →       Tabel users
                                  Kirim fetch() + JSON                  Validasi & Terbitkan Token
    input & tampilkan                                                                                      ( no_hp , foto )
         pesan


## B. Langkah Kerja Sisi Backend (Laravel 13 & MySQL)

  Panduan Warna Kode:       + Hijau = Baris kode baru yang disisipkan/ditambahkan

   ~ Kuning = Baris kode lama yang dimodifikasi/diperbarui

Pastikan aplikasi server lokal Anda (Laragon atau XAMPP) sudah dijalankan terlebih dahulu, terutama layanan MySQL,
dan pastikan versi PHP di terminal minimal PHP 8.3 ke atas (Laravel 13 mewajibkan PHP ≥ 8.3). Anda bisa
mengeceknya dengan mengetikkan php -v di terminal.


### Langkah 1: Instalasi Proyek Laravel 13 Sejajar dengan Folder Mobile React Native
Buka terminal (PowerShell atau CMD), arahkan direktori ke Path Folder Projek Kalian Berada (satu tingkat di atas
folder NovinaldiApp ), lalu jalankan perintah Composer untuk mengunduh dan memasang kerangka kerja Laravel 13
baru dengan nama folder NovinaldiAppApi , Untuk Nama folder proyek Laravel bisa diganti sesuai selera, tapi jangan
sampai sama dengan nama folder React Native agar tidak saling menimpa.:

  cd E:\ReactNativeExpo2026
  composer create-project laravel/laravel:^13.0 NovinaldiAppApi
  cd NovinaldiAppApi

Tunggu proses pengunduhan paket selesai hingga muncul pesan Application key set successfully. Jika Anda cek isi
folder E:\ReactNativeExpo2026 sekarang, posisi folder NovinaldiApp dan NovinaldiAppApi sudah berdiri sejajar
berdampingan.


### Langkah 2: Menambah Field no_hp dan foto pada Tabel users Sebelum Migrasi
Secara bawaan, tabel users di Laravel hanya menyediakan kolom id , name , email , dan password . Padahal di
aplikasi toko/minimarket kita nanti, kita butuh mencatat nomor HP petugas/pengguna serta foto profilnya.
Menambahkan dua kolom ini sebelum perintah migrasi dijalankan jauh lebih bersih daripada harus membuat file
migrasi tambahan susulan berkali-kali.

Buka      proyek        NovinaldiAppApi        di   VS    Code,      lalu    buka      berkas     migrasi   bawaan
 database/migrations/0001_01_01_000000_create_users_table.php .       Modifikasi    bagian   Schema::create('users',
...) dengan menyisipkan kolom no_hp dan foto tepat setelah kolom email :

  Schema::create('users', function (Blueprint $table) {
      $table->id();
      $table->string('name');
      $table->string('email')->unique();
      $table->string('no_hp', 20)->nullable();
      $table->string('foto')->nullable();
      $table->timestamp('email_verified_at')->nullable();
      $table->string('password');
      $table->rememberToken();
      $table->timestamps();
  });

Kolom no_hp kita batasi panjangnya 20 karakter dan kita beri ->nullable() agar aman seandainya ada akun yang
dibuat tanpa nomor HP. Begitu juga kolom foto dibuat nullable() karena pada tahap Modul 11 ini proses unggah
file foto kamera/galeri belum kita pasang—jadi saat daftar akun baru, kolom foto boleh kosong dulu, nanti di modul
lanjutan tinggal kita isi nama file fotonya.

Jangan lupa satu hal penting di Model: buka berkas app/Models/User.php , lalu masukkan 'no_hp' dan 'foto'
ke dalam atribut #[Fillable([...])] . Kalau bagian ini terlewat, Laravel akan membuang data no_hp dan foto saat
kita memanggil User::create(...) demi alasan keamanan Mass Assignment.

  #[Fillable(['name', 'email', 'no_hp', 'foto', 'password'])]
  #[Hidden(['password', 'remember_token'])]


### Langkah 3: Mengubah File .env untuk Koneksi Database MySQL
Secara bawaan, instalasi Laravel terbaru sering kali langsung mengarahkan koneksi database ke file sqlite . Karena
kita ingin menyimpan data di MySQL agar mudah dipantau lewat phpMyAdmin, HeidiSQL, atau TablePlus, buka berkas
 .env di folder utama NovinaldiAppApi , cari blok konfigurasi DB_* , lalu ubah menjadi seperti ini:

  DB_CONNECTION=mysql
  DB_HOST=127.0.0.1
  DB_PORT=3306
  DB_DATABASE=db_novinaldi_app
  DB_USERNAME=root
  DB_PASSWORD=

Setelah menyimpan file .env , pastikan Anda sudah membuat database kosong bernama db_novinaldi_app di
MySQL (bisa lewat HeidiSQL di Laragon, phpMyAdmin, atau langsung lewat terminal MySQL dengan perintah CREATE
DATABASE db_novinaldi_app; ). Jika database-nya belum dibuat, proses migrasi pada langkah berikutnya pasti akan
gagal dengan pesan Unknown database 'db_novinaldi_app'.


### Langkah 4: Instalasi Laravel Sanctum untuk API Token
Aplikasi web biasanya mengandalkan Session Cookie di browser untuk mengingat siapa yang sedang login. Namun
aplikasi React Native tidak berjalan di browser biasa dan tidak memakai sistem session seperti itu—aplikasi mobile
berkomunikasi menggunakan Token (mirip tiket masuk digital). Di ekosistem Laravel, penyedia token resmi yang ringan
dan aman adalah Laravel Sanctum.

Pada Laravel 13, berkas rute routes/api.php beserta Laravel Sanctum memang tidak langsung dipasang di awal
supaya kerangka aplikasinya ramping. Untuk mengaktifkan keduanya sekaligus, cukup jalankan satu perintah ini di
terminal folder NovinaldiAppApi :

  php artisan install:api

  Perhatian Saat Menjalankan php artisan install:api :
  Di tengah proses instalasi, terminal bisa saja memunculkan pertanyaan: "One new database migration will be published.
  Would you like to run all pending database migrations? (yes/no)". Anda boleh ketik yes (jika database db_novinaldi_app
  sudah Anda buat di Langkah 3) atau ketik no dulu untuk mengeksekusi migrasi secara manual di Langkah 5 agar lebih
  terkontrol.

Perintah install:api tadi otomatis melakukan tiga hal penting sekaligus: mengunduh paket laravel/sanctum ,
membuatkan berkas routes/api.php beserta pengaturannya di bootstrap/app.php , serta menambahkan berkas
migrasi tabel personal_access_tokens di folder database/migrations/ .

Setelah instalasi Sanctum beres, buka kembali berkas app/Models/User.php dan aktifkan trait HasApiTokens supaya
setiap user bisa menerbitkan token saat login:

  <?php

  namespace App\Models;

  // use Illuminate\Contracts\Auth\MustVerifyEmail;
  use Database\Factories\UserFactory;
  use Illuminate\Database\Eloquent\Attributes\Fillable;
  use Illuminate\Database\Eloquent\Attributes\Hidden;
  use Illuminate\Database\Eloquent\Factories\HasFactory;
  use Illuminate\Foundation\Auth\User as Authenticatable;
  use Illuminate\Notifications\Notifiable;
  use Laravel\Sanctum\HasApiTokens;

  #[Fillable(['name', 'email', 'no_hp', 'foto', 'password'])]
  #[Hidden(['password', 'remember_token'])]
  class User extends Authenticatable
  {
      /** @use HasFactory<UserFactory> */
      use HasApiTokens, HasFactory, Notifiable;

      /**
       * Get the attributes that should be cast.
       *
       * @return array<string, string>
       */
      protected function casts(): array
      {

           return [
               'email_verified_at' => 'datetime',
               'password' => 'hashed',
           ];
      }
  }


### Langkah 5: Menjalankan Migrasi Database Laravel
Sekarang kita eksekusi seluruh berkas migrasi agar tabel users (beserta kolom baru no_hp dan foto ), tabel
 personal_access_tokens     milik    Sanctum,   dan   tabel   pendukung      lainnya   terbentuk   di   dalam   database
 db_novinaldi_app :

  php artisan migrate

Jika di terminal semua baris berstatus DONE, coba buka HeidiSQL atau phpMyAdmin dan periksa struktur tabel
 users . Pastikan kolom   no_hp     dan foto sudah muncul di sana, dan tabel personal_access_tokens sudah ikut
terbuat.


### Langkah 6: Tambahan Wajib di Backend agar API Siap Dipanggil Mobile
Setelah database dan Sanctum siap, tentu API belum bisa dipakai sebelum kita membuat Controller pengolah data,
mendaftarkan alamat endpoint-nya, memastikan aturan CORS aman, dan menyalakan server Laravel dengan alamat
jaringan lokal. Lakukan 4 langkah pelengkap di bawah ini secara berurutan:


#### 6.1. Membuat AuthController ( app/Http/Controllers/Api/AuthController.php )
Buat controller baru khusus API melalui terminal:

  php artisan make:controller Api/AuthController

Buka file app/Http/Controllers/Api/AuthController.php yang baru saja terbentuk, lalu isi dengan empat fungsi
utama: register , login , profile , dan logout . Di sini kita pastikan seluruh balasan menggunakan format JSON
yang seragam serta kode status HTTP yang jelas ( 201 saat daftar sukses, 200 saat login sukses, dan 401 jika
email/password salah):

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
        'password' => $validated['password'], // Otomatis di-hash oleh cast 'hashed' di Model User
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

    // Bersihkan token lama (opsional agar 1 perangkat tidak menumpuk banyak token)
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


#### 6.2. Mendaftarkan Rute di routes/api.php
Buka berkas routes/api.php , lalu hubungkan URL endpoint dengan fungsi-fungsi pada AuthController . Endpoint
 /register     dan   /login   dibuka untuk umum, sementara         /profile   dan      /logout   dipagari menggunakan
middleware auth:sanctum sehingga hanya pengguna yang membawa Bearer Token sah yang boleh mengaksesnya:

  <?php

  use App\Http\Controllers\Api\AuthController;
  use Illuminate\Support\Facades\Route;

  // Endpoint Publik (Belum punya token)
  Route::post('/register', [AuthController::class, 'register']);
  Route::post('/login', [AuthController::class, 'login']);

  // Endpoint Terproteksi (Wajib membawa Header Authorization: Bearer <token>)
  Route::middleware('auth:sanctum')->group(function () {
      Route::get('/profile', [AuthController::class, 'profile']);
      Route::post('/logout', [AuthController::class, 'logout']);
  });


#### 6.3. Mengecek Konfigurasi CORS (Agar Pengujian Expo Web Tidak Ditolak)
Kalau suatu saat Anda menguji aplikasi React Native lewat mode browser ( Expo Web ), browser punya aturan
keamanan ketat bernama CORS. Supaya aman, jalankan perintah publish konfigurasi CORS di terminal Laravel:

  php artisan config:publish cors

Pastikan di dalam berkas        config/cors.php     nilai   'paths'   =>   ['api/*',     'sanctum/csrf-cookie'],      dan
 'allowed_origins' => ['*'], sudah aktif.


#### 6.4. Menyalakan Server Laravel dengan --host=0.0.0.0
Ini bagian yang paling sering menjebak mahasiswa ketika praktek di kelas. Kalau Anda menyalakan server hanya
dengan php artisan serve biasa, Laravel hanya mendengarkan koneksi dari 127.0.0.1 (komputer itu sendiri).
Akibatnya, HP Anda yang memakai Expo Go lewat Wi-Fi tidak akan pernah bisa menembus server tersebut!

Oleh karena itu, wajib nyalakan server dengan perintah ini agar terbuka ke jaringan Wi-Fi LAN:

  php artisan serve --host=0.0.0.0 --port=8000

Setelah server menyala, buka CMD baru dan ketik ipconfig untuk melihat alamat IPv4 Address Wi-Fi laptop Anda
(misalnya 192.168.1.15 ). Catat IP ini baik-baik, karena alamat itulah yang akan kita tanam di proyek React Native.

  Tips Pengujian Awal Sebelum Masuk ke Mobile:
  Biasakan menguji API terlebih dahulu lewat Thunder Client / Postman (tembak POST
  http://127.0.0.1:8000/api/register dengan body JSON) sebelum menyentuh kode React Native. Jika di Postman saja
  sudah sukses dan datanya masuk ke tabel users di MySQL, berarti sisi backend sudah 100% beres. Apabila nanti ada
  kendala di mobile, kita langsung tahu masalahnya pasti di koneksi jaringan/IP atau kode React Native-nya.


## C. Mengapa Memakai fetch() dan Memisahkan File API di Mobile?

Banyak mahasiswa bertanya: "Pak, untuk memanggil API Laravel di Expo React Native, apakah harus menginstal Axios
atau bisa langsung pakai fetch() ?"

Jawabannya: Bisa langsung pakai fetch() , dan justru cara ini yang paling cepat serta ringan. fetch() sudah
tertanam bawaan di mesin JavaScript React Native (Hermes) tanpa perlu menjalankan npx expo install paket
tambahan apa pun. Selain ukurannya nol kilobyte tambahan, menggunakan fetch() membuat Anda mengerti betul
bagaimana cara kerja protokol HTTP di balik layar: mulai dari menyusun headers ( Accept: application/json ,
Content-Type: application/json ), membungkus payload lewat              JSON.stringify() , hingga mengecek kode status
HTTP sebelum mengubah respons dengan response.json() .

Lalu, mengapa perintah-perintah fetch() itu kita pisahkan ke file tersendiri dan tidak langsung ditulis di dalam
login.tsx atau register.tsx ? Prinsipnya sama persis seperti saat kita memisahkan StyleSheet ke dalam folder
src/styles/ di Modul 09 dan 10:

   Komponen layar di src/app/ tugasnya fokus pada tampilan antarmuka dan interaksi user saja.
   Jika alamat IP laptop Anda berubah saat pindah Wi-Fi dari kampus ke rumah, Anda cukup mengubah satu baris
   saja di src/config/api.ts tanpa harus mengobrak-abrik semua file halaman.
   Jika struktur penanganan error API perlu disempurnakan, Anda cukup mengedit satu file layanan di
    src/services/authService.ts .


## D. Langkah Kerja Sisi Mobile ( NovinaldiApp — React Native Expo)

  Panduan Warna Kode:      + Hijau = Baris kode baru yang ditambahkan/disisipkan

   ~ Kuning = Baris kode lama yang diubah dari Modul 09/10

Sekarang kembali ke proyek NovinaldiApp di VS Code. Struktur tambahan folder yang akan kita lengkapi di dalam
src/ terlihat seperti di bawah ini:

  src/
  ├── config/
  │   └── api.ts                       # [BARU] Pusat alamat BASE_URL & daftar endpoint API
  ├── services/
  │   └── authService.ts               # [BARU] Perintah fetch() + simpan/baca/hapus AsyncStorage
  ├── styles/
  │   └── authStyles.ts                # [UPDATE] Gaya tombol loading & layar cek sesi
  └── app/
      ├── index.tsx                    # [UPDATE] Cek sesi AsyncStorage saat aplikasi dibuka
      ├── login.tsx                    # [UPDATE] Panggil loginApi() & simpan ke AsyncStorage
      ├── register.tsx                 # [UPDATE] Tambah input Nomor HP & panggil registerApi()
      └── (tabs)/
          ├── home.tsx                 # [UPDATE] Baca nama & email user dari AsyncStorage
          └── profil.tsx               # [UPDATE] Baca profil dari AsyncStorage + Logout hapus sesi


### Langkah 1: Instalasi Pustaka AsyncStorage di Proyek Mobile
Karena kita ingin agar pengguna yang sudah berhasil login tidak perlu mengetikkan ulang email dan kata sandi setiap
kali aplikasi ditutup lalu dibuka kembali, kita simpan token dan data profilnya secara permanen di memori
penyimpanan HP menggunakan AsyncStorage. Jalankan perintah berikut di terminal folder NovinaldiApp :

  npx expo install @react-native-async-storage/async-storage


### Langkah 2: Membuat Pusat Alamat API ( src/config/api.ts )
Buat folder baru bernama       config   di dalam       src/ , lalu buat berkas   src/config/api.ts . Ingat: ganti nilai
 192.168.1.15 pada kode di bawah dengan alamat IPv4 Wi-Fi laptop Anda yang sudah Anda cek lewat ipconfig tadi
(jika menguji pakai browser laptop di Expo Web, Anda boleh memakai 127.0.0.1 sementara; tetapi jika menguji di HP
fisik lewat Expo Go, wajib memakai IP Wi-Fi laptop).

  // Ganti alamat IP ini dengan IPv4 laptop Anda (cek lewat perintah 'ipconfig' di CMD)
  // Pastikan HP dan Laptop terhubung ke jaringan Wi-Fi yang sama.
  export const API_BASE_URL = "http://192.168.1.15:8000/api";

  export const API_ENDPOINTS = {
    LOGIN: `${API_BASE_URL}/login`,
    REGISTER: `${API_BASE_URL}/register`,
    PROFILE: `${API_BASE_URL}/profile`,
    LOGOUT: `${API_BASE_URL}/logout`,
  };


### Langkah 3: Memisahkan fetch() & Helper AsyncStorage ( src/services/authService.ts )
Buat folder services di dalam src/ , lalu buat berkas src/services/authService.ts . Di sinilah seluruh urusan
kirim-terima JSON dengan Laravel 13 sekaligus manajemen penyimpanan sesi AsyncStorage (simpan, baca, hapus)
ditangani di satu tempat:

  import { API_ENDPOINTS } from "@/config/api";
  import AsyncStorage from "@react-native-async-storage/async-storage";

  const AUTH_TOKEN_KEY = "@novinaldiapp_token";
  const AUTH_USER_KEY = "@novinaldiapp_user";

  export interface UserData {
    id: number;
    name: string;
    email: string;
    no_hp: string | null;
    foto: string | null;
  }

  export interface AuthResponse {
    success: boolean;
    message: string;
    token: string;
    user: UserData;
  }

  export interface AuthSession {
    token: string;
    user: UserData;
  }

  export interface LoginPayload {
    email: string;

    password: string;
}

export interface RegisterPayload {
  name: string;
  email: string;
  no_hp: string;
  password: string;
  password_confirmation: string;
}

// Helper pembantu agar parsing error dari Laravel 13 seragam dan rapi
async function handleApiResponse<T>(response: Response): Promise<T> {
  const data = await response.json().catch(() => null);

    if (!response.ok) {
      // Jika Laravel mengembalikan error validasi 422 (misal: { errors: { email: ["..."] } })
      if (data?.errors) {
        const firstKey = Object.keys(data.errors)[0];
        const firstMsg = data.errors[firstKey]?.[0];
        if (firstMsg) {
          throw new Error(firstMsg);
        }
      }
      throw new Error(
        data?.message || `Permintaan gagal dengan status HTTP ${response.status}.`,
      );
    }

    return data as T;
}

// 1. Perintah API Login
export async function loginApi(payload: LoginPayload): Promise<AuthResponse> {
  try {
    const response = await fetch(API_ENDPOINTS.LOGIN, {
      method: "POST",
      headers: {
        Accept: "application/json",
        "Content-Type": "application/json",
      },
      body: JSON.stringify(payload),
    });

      return await handleApiResponse<AuthResponse>(response);
    } catch (error) {
      if (error instanceof TypeError) {
        throw new Error(
          "Tidak dapat terhubung ke server Laravel. Pastikan server berjalan (--host=0.0.0.0) dan IP Wi-Fi su
        );
      }
      throw error;
    }
}

// 2. Perintah API Register
export async function registerApi(
  payload: RegisterPayload,
): Promise<AuthResponse> {
  try {
    const response = await fetch(API_ENDPOINTS.REGISTER, {
      method: "POST",
      headers: {
        Accept: "application/json",

         "Content-Type": "application/json",
       },
       body: JSON.stringify(payload),
     });

      return await handleApiResponse<AuthResponse>(response);
    } catch (error) {
      if (error instanceof TypeError) {
        throw new Error(
          "Tidak dapat terhubung ke server Laravel. Periksa koneksi Wi-Fi dan alamat API_BASE_URL.",
        );
      }
      throw error;
    }
}

// 3. Perintah API Logout (Menghapus token di database server)
export async function logoutApi(token: string): Promise<void> {
  try {
    await fetch(API_ENDPOINTS.LOGOUT, {
      method: "POST",
      headers: {
        Accept: "application/json",
        "Content-Type": "application/json",
        Authorization: `Bearer ${token}`,
      },
    });
  } catch {
    // Jika jaringan sedang terputus saat logout, sesi lokal di HP tetap kita bersihkan
  }
}

// 4. Helper Penyimpanan Sesi AsyncStorage (Simpan, Baca, Hapus)
export async function saveAuthSession(
  user: UserData,
  token: string,
): Promise<void> {
  await AsyncStorage.multiSet([
    [AUTH_TOKEN_KEY, token],
    [AUTH_USER_KEY, JSON.stringify(user)],
  ]);
}

export async function getAuthSession(): Promise<AuthSession | null> {
  const values = await AsyncStorage.multiGet([AUTH_TOKEN_KEY, AUTH_USER_KEY]);
  const token = values[0]?.[1];
  const userJson = values[1]?.[1];

    if (!token || !userJson) {
      return null;
    }

    try {
      return { token, user: JSON.parse(userJson) as UserData };
    } catch {
      return null;
    }
}

export async function clearAuthSession(): Promise<void> {
  await AsyncStorage.multiRemove([AUTH_TOKEN_KEY, AUTH_USER_KEY]);
}


### Langkah 4: Menambah Style Loading di src/styles/authStyles.ts
Tambahkan gaya primaryButtonDisabled (agar tombol agak redup saat memanggil server) dan loadingContainer
(untuk layar pengecekan sesi di index.tsx ) pada src/styles/authStyles.ts :

      primaryButtonDisabled: {
        opacity: 0.65,
      },
      loadingContainer: {
        flex: 1,
        justifyContent: "center",
        alignItems: "center",
        backgroundColor: "#F8FAFC",
      },


### Langkah 5: Cek Sesi Otomatis Saat Aplikasi Dibuka ( src/app/index.tsx )
Ubah berkas src/app/index.tsx agar saat aplikasi pertama kali dibuka, aplikasi mengecek isi AsyncStorage terlebih
dahulu. Jika token tersimpan masih ada, pengguna langsung diarahkan ke /home tanpa perlu login ulang:

  import { getAuthSession } from "@/services/authService";
  import { authStyles } from "@/styles/authStyles";
  import { Redirect } from "expo-router";
  import { useEffect, useState } from "react";
  import { ActivityIndicator, View } from "react-native";

  export default function Index() {
    const [checking, setChecking] = useState(true);
    const [hasSession, setHasSession] = useState(false);

      useEffect(() => {
        getAuthSession()
          .then((session) => {
            setHasSession(Boolean(session?.token));
          })
          .finally(() => {
            setChecking(false);
          });
      }, []);

      if (checking) {
        return (
          <View style={authStyles.loadingContainer}>
            <ActivityIndicator size="large" color="#2563EB" />
          </View>
        );
      }

      return <Redirect href={hasSession ? "/home" : "/login"} />;
  }


### Langkah 6: Menghubungkan Layar Login ke loginApi() & AsyncStorage ( src/app/login.tsx )
Perhatikan betapa bersihnya berkas src/app/login.tsx setelah perintah fetch() dipisah ke authService.ts . Saat
tombol Masuk Sekarang ditekan, layar cukup memanggil loginApi({ email, password }) , menyimpan token & user
ke AsyncStorage lewat saveAuthSession() , lalu pindah ke /home :

  import { loginApi, saveAuthSession } from "@/services/authService";
  import { authStyles } from "@/styles/authStyles";
  import { Ionicons } from "@expo/vector-icons";
  import { router } from "expo-router";

import { useState } from "react";
import {
  ActivityIndicator,
  Alert,
  KeyboardAvoidingView,
  Platform,
  ScrollView,
  Text,
  TextInput,
  TouchableOpacity,
  View,
} from "react-native";
import { SafeAreaView } from "react-native-safe-area-context";

export default function LoginScreen() {
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [showPassword, setShowPassword] = useState(false);
  const [loading, setLoading] = useState(false);
  const [focusedField, setFocusedField] = useState<"email" | "password" | null>(
    null,
  );

 const handleLogin = async () => {
   if (!email.trim() || !password.trim()) {
     Alert.alert(
       "Input Belum Lengkap",
       "Silakan masukkan email dan password Anda.",
     );
     return;
   }

   setLoading(true);
   try {
     const result = await loginApi({
       email: email.trim(),
       password,
     });

     await saveAuthSession(result.user, result.token);
     router.replace("/home");
   } catch (error) {
     const message =
       error instanceof Error
         ? error.message
         : "Terjadi kesalahan saat masuk ke akun.";
     Alert.alert("Login Gagal", message);
   } finally {
     setLoading(false);
   }
 };

 return (
   <SafeAreaView style={authStyles.safeArea}>
     <KeyboardAvoidingView
       style={authStyles.flexContainer}
       behavior={Platform.OS === "ios" ? "padding" : "height"}
     >
       <ScrollView
         contentContainerStyle={authStyles.scrollContent}
         keyboardShouldPersistTaps="handled"
         showsVerticalScrollIndicator={false}
       >
         <View style={authStyles.headerArea}>

  <View style={authStyles.logoBadge}>
    <Ionicons name="shield-checkmark" size={30} color="#2563EB" />
  </View>
  <Text style={authStyles.title}>Selamat Datang</Text>
  <Text style={authStyles.subtitle}>
    Masuk ke akun Anda untuk mengakses portal praktikum Mobile 2
  </Text>
</View>

<View style={authStyles.card}>
  <View style={authStyles.formGroup}>
    <Text style={authStyles.label}>Alamat Email</Text>
    <View
      style={[
        authStyles.inputWrapper,
        focusedField === "email" && authStyles.inputWrapperFocused,
      ]}
    >
      <Ionicons
        name="mail-outline"
        size={20}
        color={focusedField === "email" ? "#2563EB" : "#64748B"}
        style={authStyles.inputIcon}
      />
      <TextInput
        style={authStyles.input}
        placeholder="nama@kampus.ac.id"
        placeholderTextColor="#94A3B8"
        keyboardType="email-address"
        autoCapitalize="none"
        editable={!loading}
        value={email}
        onChangeText={setEmail}
        onFocus={() => setFocusedField("email")}
        onBlur={() => setFocusedField(null)}
      />
    </View>
  </View>

 <View style={authStyles.formGroup}>
   <Text style={authStyles.label}>Kata Sandi</Text>
   <View
     style={[
       authStyles.inputWrapper,
       focusedField === "password" && authStyles.inputWrapperFocused,
     ]}
   >
     <Ionicons
       name="lock-closed-outline"
       size={20}
       color={focusedField === "password" ? "#2563EB" : "#64748B"}
       style={authStyles.inputIcon}
     />
     <TextInput
       style={authStyles.input}
       placeholder="Masukkan kata sandi"
       placeholderTextColor="#94A3B8"
       secureTextEntry={!showPassword}
       autoCapitalize="none"
       editable={!loading}
       value={password}
       onChangeText={setPassword}
       onFocus={() => setFocusedField("password")}
       onBlur={() => setFocusedField(null)}

                   />
                   <TouchableOpacity
                     style={authStyles.eyeButton}
                     onPress={() => setShowPassword(!showPassword)}
                   >
                     <Ionicons
                       name={showPassword ? "eye-off-outline" : "eye-outline"}
                       size={20}
                       color="#64748B"
                     />
                   </TouchableOpacity>
                 </View>
               </View>

               <TouchableOpacity
                 style={[
                   authStyles.primaryButton,
                   loading && authStyles.primaryButtonDisabled,
                 ]}
                 activeOpacity={0.85}
                 disabled={loading}
                 onPress={handleLogin}
               >
                 {loading ? (
                   <ActivityIndicator color="#FFFFFF" />
                 ) : (
                   <>
                     <Text style={authStyles.primaryButtonText}>Masuk Sekarang</Text>
                     <Ionicons name="arrow-forward" size={18} color="#FFFFFF" />
                   </>
                 )}
               </TouchableOpacity>

               <View style={authStyles.dividerRow}>
                 <View style={authStyles.dividerLine} />
                 <Text style={authStyles.dividerText}>atau</Text>
                 <View style={authStyles.dividerLine} />
               </View>

                <View style={authStyles.footerRow}>
                  <Text style={authStyles.footerText}>Belum punya akun?</Text>
                  <TouchableOpacity
                    disabled={loading}
                    onPress={() => router.push("/register")}
                  >
                    <Text style={authStyles.footerLink}>Daftar Sekarang</Text>
                  </TouchableOpacity>
                </View>
              </View>
            </ScrollView>
          </KeyboardAvoidingView>
        </SafeAreaView>
      );
  }


### Langkah 7: Menambah Input Nomor HP & Menghubungkan Register ke registerApi()
( src/app/register.tsx )
Karena di Laravel tadi kita sudah menambahkan field           no_hp   pada tabel   users ,   maka pada berkas
src/app/register.tsx kita tambahkan kolom input Nomor HP tepat di bawah kolom Email, lalu menghubungkan
tombol daftar ke fungsi registerApi() :

import { registerApi } from "@/services/authService";
import { authStyles } from "@/styles/authStyles";
import { Ionicons } from "@expo/vector-icons";
import { router } from "expo-router";
import { useState } from "react";
import {
  ActivityIndicator,
  Alert,
  KeyboardAvoidingView,
  Platform,
  ScrollView,
  Text,
  TextInput,
  TouchableOpacity,
  View,
} from "react-native";
import { SafeAreaView } from "react-native-safe-area-context";

export default function RegisterScreen() {
  const [nama, setNama] = useState("");
  const [email, setEmail] = useState("");
  const [noHp, setNoHp] = useState("");
  const [password, setPassword] = useState("");
  const [confirmPassword, setConfirmPassword] = useState("");
  const [showPassword, setShowPassword] = useState(false);
  const [showConfirm, setShowConfirm] = useState(false);
  const [loading, setLoading] = useState(false);
  const [focusedField, setFocusedField] = useState<
    "nama" | "email" | "noHp" | "password" | "confirm" | null
  >(null);

 const handleRegister = async () => {
   if (
     !nama.trim() ||
     !email.trim() ||
     !noHp.trim() ||
     !password.trim() ||
     !confirmPassword.trim()
   ) {
     Alert.alert("Pendaftaran Gagal", "Semua kolom formulir wajib diisi.");
     return;
   }

   if (password.length < 6) {
     Alert.alert("Kata Sandi Lemah", "Kata sandi minimal terdiri dari 6 karakter.");
     return;
   }

   if (password !== confirmPassword) {
     Alert.alert("Tidak Cocok", "Konfirmasi kata sandi tidak sama dengan kata sandi.");
     return;
   }

   setLoading(true);
   try {
     const result = await registerApi({
       name: nama.trim(),
       email: email.trim(),
       no_hp: noHp.trim(),
       password,
       password_confirmation: confirmPassword,
     });

    Alert.alert(
      "Pendaftaran Berhasil!",
      `Akun atas nama ${result.user.name} telah tersimpan ke database. Silakan masuk melalui halaman Logi
      [{ text: "Ke Login", onPress: () => router.back() }],
    );
  } catch (error) {
    const message =
      error instanceof Error
        ? error.message
        : "Terjadi kesalahan saat mendaftarkan akun.";
    Alert.alert("Pendaftaran Gagal", message);
  } finally {
    setLoading(false);
  }
};

return (
  <SafeAreaView style={authStyles.safeArea}>
    <KeyboardAvoidingView
      style={authStyles.flexContainer}
      behavior={Platform.OS === "ios" ? "padding" : "height"}
    >
      <ScrollView
        contentContainerStyle={authStyles.scrollContent}
        keyboardShouldPersistTaps="handled"
        showsVerticalScrollIndicator={false}
      >
        <TouchableOpacity
          style={authStyles.topBackButton}
          disabled={loading}
          onPress={() => router.back()}
        >
          <Ionicons name="arrow-back" size={20} color="#0F172A" />
        </TouchableOpacity>

       <View style={authStyles.headerArea}>
         <View style={authStyles.logoBadge}>
           <Ionicons name="person-add" size={28} color="#2563EB" />
         </View>
         <Text style={authStyles.title}>Buat Akun Baru</Text>
         <Text style={authStyles.subtitle}>
           Lengkapi data diri Anda untuk mendaftarkan akun ke server
         </Text>
       </View>

       <View style={authStyles.card}>
         {/* Nama Lengkap */}
         <View style={authStyles.formGroup}>
           <Text style={authStyles.label}>Nama Lengkap</Text>
           <View
             style={[
               authStyles.inputWrapper,
               focusedField === "nama" && authStyles.inputWrapperFocused,
             ]}
           >
             <Ionicons
               name="person-outline"
               size={20}
               color={focusedField === "nama" ? "#2563EB" : "#64748B"}
               style={authStyles.inputIcon}
             />
             <TextInput
               style={authStyles.input}
               placeholder="Contoh: Muhammad Rizki"

      placeholderTextColor="#94A3B8"
      editable={!loading}
      value={nama}
      onChangeText={setNama}
      onFocus={() => setFocusedField("nama")}
      onBlur={() => setFocusedField(null)}
    />
  </View>
</View>

{/* Email */}
<View style={authStyles.formGroup}>
  <Text style={authStyles.label}>Alamat Email</Text>
  <View
    style={[
      authStyles.inputWrapper,
      focusedField === "email" && authStyles.inputWrapperFocused,
    ]}
  >
    <Ionicons
      name="mail-outline"
      size={20}
      color={focusedField === "email" ? "#2563EB" : "#64748B"}
      style={authStyles.inputIcon}
    />
    <TextInput
      style={authStyles.input}
      placeholder="nama@kampus.ac.id"
      placeholderTextColor="#94A3B8"
      keyboardType="email-address"
      autoCapitalize="none"
      editable={!loading}
      value={email}
      onChangeText={setEmail}
      onFocus={() => setFocusedField("email")}
      onBlur={() => setFocusedField(null)}
    />
  </View>
</View>

{/* Nomor HP */}
<View style={authStyles.formGroup}>
  <Text style={authStyles.label}>Nomor HP / WhatsApp</Text>
  <View
    style={[
      authStyles.inputWrapper,
      focusedField === "noHp" && authStyles.inputWrapperFocused,
    ]}
  >
    <Ionicons
      name="call-outline"
      size={20}
      color={focusedField === "noHp" ? "#2563EB" : "#64748B"}
      style={authStyles.inputIcon}
    />
    <TextInput
      style={authStyles.input}
      placeholder="Contoh: 081234567890"
      placeholderTextColor="#94A3B8"
      keyboardType="phone-pad"
      editable={!loading}
      value={noHp}
      onChangeText={setNoHp}
      onFocus={() => setFocusedField("noHp")}

      onBlur={() => setFocusedField(null)}
    />
  </View>
</View>

{/* Password */}
<View style={authStyles.formGroup}>
  <Text style={authStyles.label}>Kata Sandi</Text>
  <View
    style={[
      authStyles.inputWrapper,
      focusedField === "password" && authStyles.inputWrapperFocused,
    ]}
  >
    <Ionicons
      name="lock-closed-outline"
      size={20}
      color={focusedField === "password" ? "#2563EB" : "#64748B"}
      style={authStyles.inputIcon}
    />
    <TextInput
      style={authStyles.input}
      placeholder="Minimal 6 karakter"
      placeholderTextColor="#94A3B8"
      secureTextEntry={!showPassword}
      autoCapitalize="none"
      editable={!loading}
      value={password}
      onChangeText={setPassword}
      onFocus={() => setFocusedField("password")}
      onBlur={() => setFocusedField(null)}
    />
    <TouchableOpacity
      style={authStyles.eyeButton}
      onPress={() => setShowPassword(!showPassword)}
    >
      <Ionicons
        name={showPassword ? "eye-off-outline" : "eye-outline"}
        size={20}
        color="#64748B"
      />
    </TouchableOpacity>
  </View>
</View>

{/* Konfirmasi Password */}
<View style={authStyles.formGroup}>
  <Text style={authStyles.label}>Konfirmasi Kata Sandi</Text>
  <View
    style={[
      authStyles.inputWrapper,
      focusedField === "confirm" && authStyles.inputWrapperFocused,
    ]}
  >
    <Ionicons
      name="checkmark-circle-outline"
      size={20}
      color={focusedField === "confirm" ? "#2563EB" : "#64748B"}
      style={authStyles.inputIcon}
    />
    <TextInput
      style={authStyles.input}
      placeholder="Ulangi kata sandi"
      placeholderTextColor="#94A3B8"

                     secureTextEntry={!showConfirm}
                     autoCapitalize="none"
                     editable={!loading}
                     value={confirmPassword}
                     onChangeText={setConfirmPassword}
                     onFocus={() => setFocusedField("confirm")}
                     onBlur={() => setFocusedField(null)}
                   />
                   <TouchableOpacity
                     style={authStyles.eyeButton}
                     onPress={() => setShowConfirm(!showConfirm)}
                   >
                     <Ionicons
                       name={showConfirm ? "eye-off-outline" : "eye-outline"}
                       size={20}
                       color="#64748B"
                     />
                   </TouchableOpacity>
                 </View>
               </View>

               <TouchableOpacity
                 style={[
                   authStyles.primaryButton,
                   loading && authStyles.primaryButtonDisabled,
                 ]}
                 activeOpacity={0.85}
                 disabled={loading}
                 onPress={handleRegister}
               >
                 {loading ? (
                   <ActivityIndicator color="#FFFFFF" />
                 ) : (
                   <>
                     <Text style={authStyles.primaryButtonText}>Daftar Akun</Text>
                     <Ionicons name="checkmark" size={19} color="#FFFFFF" />
                   </>
                 )}
               </TouchableOpacity>

                <View style={[authStyles.footerRow, { marginTop: 18 }]}>
                  <Text style={authStyles.footerText}>Sudah memiliki akun?</Text>
                  <TouchableOpacity disabled={loading} onPress={() => router.back()}>
                    <Text style={authStyles.footerLink}>Masuk di sini</Text>
                  </TouchableOpacity>
                </View>
              </View>
            </ScrollView>
          </KeyboardAvoidingView>
        </SafeAreaView>
      );
  }


### Langkah 8: Menampilkan Data User Asli di Home & Logout ke Server di Tab Profil
Terakhir, perbarui dua berkas di dalam src/app/(tabs)/ agar data pengguna asli dibaca langsung dari AsyncStorage
dan aksi Logout membersihkan token baik di server maupun di HP:


## A. Pada src/app/(tabs)/home.tsx :
Ambil data user tersimpan menggunakan getAuthSession() di dalam useEffect pada komponen HomeScreen :

  import { getAuthSession, UserData } from "@/services/authService";
  import { homeStyles } from "@/styles/homeStyles";
  import { Ionicons } from "@expo/vector-icons";
  import { router } from "expo-router";
  import { useEffect, useState } from "react";
  // ... (import Alert, ScrollView, Text, TouchableOpacity, View, SafeAreaView tetap)

  export default function HomeScreen() {
    const [user, setUser] = useState<UserData | null>(null);

    useEffect(() => {
      getAuthSession().then((session) => {
        if (session) setUser(session.user);
      });
    }, []);
    // ...
    // Di dalam kartu sambutan (welcomeCard):
    //   <View style={homeStyles.statusBadge}>
    //     <Ionicons name="checkmark-circle" size={15} color="#059669" />
    //     <Text style={homeStyles.statusBadgeText}>Terhubung ke Server Laravel 13</Text>
    //   </View>
    //   <Text style={homeStyles.greetingTitle}>Halo, {user?.name || "Selamat Datang"}!</Text>
    //   <Text style={homeStyles.greetingEmail}>{user?.email || "mahasiswa@kampus.ac.id"}</Text>


## B. Pada src/app/(tabs)/profil.tsx :
Tampilkan nama lengkap, email, dan nomor HP yang dibaca dari AsyncStorage , serta hubungkan tombol Keluar dari
Akun ke logoutApi() dan clearAuthSession() :

  import {
    clearAuthSession,
    getAuthSession,
    logoutApi,
    UserData,
  } from "@/services/authService";
  import { tabStyles } from "@/styles/tabStyles";
  import { Ionicons } from "@expo/vector-icons";
  import { router } from "expo-router";
  import { useEffect, useState } from "react";
  import {
    ActivityIndicator,
    ScrollView,
    Text,
    TouchableOpacity,
    View,
  } from "react-native";

  export default function ProfilScreen() {
    const [user, setUser] = useState<UserData | null>(null);
    const [loadingLogout, setLoadingLogout] = useState(false);

    useEffect(() => {
      getAuthSession().then((session) => {
        if (session) setUser(session.user);
      });
    }, []);

    const handleLogout = async () => {
      setLoadingLogout(true);
      try {
        const session = await getAuthSession();
        if (session?.token) {

            await logoutApi(session.token);
          }
          await clearAuthSession();
          router.replace("/login");
        } finally {
          setLoadingLogout(false);
        }
      };

      return (
        <ScrollView
          style={tabStyles.screenContainer}
          contentContainerStyle={tabStyles.scrollContent}
          showsVerticalScrollIndicator={false}
        >
          <View style={tabStyles.card}>
            <View style={tabStyles.profileAvatar}>
              <Ionicons name="people" size={30} color="#2563EB" />
            </View>
            <Text style={tabStyles.sectionTitle}>
              {user?.name || "Profil Pengguna"}
            </Text>
            <Text style={tabStyles.sectionSubtitle}>
              Email: {user?.email || "-"}{"\n"}
              Nomor HP: {user?.no_hp || "Belum diisi"}
            </Text>
          </View>

          <TouchableOpacity
            style={tabStyles.logoutButton}
            activeOpacity={0.8}
            disabled={loadingLogout}
            onPress={handleLogout}
          >
            {loadingLogout ? (
              <ActivityIndicator color="#DC2626" />
            ) : (
              <>
                <Ionicons name="log-out-outline" size={20} color="#DC2626" />
                <Text style={tabStyles.logoutButtonText}>Keluar dari Akun</Text>
              </>
            )}
          </TouchableOpacity>
        </ScrollView>
      );
 }


## E. Prosedur Uji Coba End-to-End & Troubleshooting

1. Nyalakan dua terminal sekaligus:
     • Terminal 1 (di E:\ReactNativeExpo2026\NovinaldiAppApi ): jalankan php artisan serve --host=0.0.0.0 --
     port=8000 .
     • Terminal 2 (di E:\ReactNativeExpo2026\NovinaldiApp ): jalankan npx tsc --noEmit , npx expo lint , lalu npx
     expo start .

2. Uji Register ke MySQL: Buka layar Register di HP, isi Nama, Email, Nomor HP, dan Kata Sandi, lalu tekan Daftar
     Akun. Cek di phpMyAdmin/HeidiSQL apakah baris baru pada tabel users benar-benar tersimpan lengkap dengan
     nomor HP-nya.
3. Uji Validasi Server: Coba daftarkan ulang email yang sama. Pastikan muncul pesan peringatan langsung dari
     Laravel: "Email ini sudah terdaftar, silakan gunakan email lain."

 4. Uji Login & Token Sanctum: Masuk menggunakan akun yang baru didaftarkan tadi. Cek tabel
     personal_access_tokens di MySQL—seharusnya muncul 1 baris token aktif, dan di layar Home muncul nama serta
    email asli Anda.
 5. Uji Auto-Login ( AsyncStorage ): Setelah berhasil masuk ke layar Home, coba tutup penuh aplikasi Expo Go di HP
    lalu buka kembali. Karena token masih tersimpan di AsyncStorage , aplikasi akan otomatis langsung mengarahkan
    Anda ke Beranda tanpa perlu mengisi form Login lagi.
 6. Uji Logout: Pindah ke tab Profil, periksa apakah Nomor HP Anda tampil dengan benar, lalu tekan Keluar dari
    Akun. Cek ulang tabel personal_access_tokens —baris token tersebut harus otomatis terhapus dari database.

   Kendala Umum yang Sering Terjadi Saat Praktek di Kelas:
   • Muncul pesan "Tidak dapat terhubung ke server Laravel": Cek apakah HP dan Laptop terhubung ke hotspot/Wi-Fi yang
   sama, cek apakah Windows Firewall memblokir port 8000 , dan pastikan alamat IP pada src/config/api.ts cocok
   dengan hasil ipconfig .
   • Kolom no_hp bernilai NULL padahal sudah diisi di HP: Kemungkinan besar Anda lupa menambahkan 'no_hp' ke
   atribut #[Fillable([...])] di file app/Models/User.php .


---

Matakuliah : Aplikasi Mobile 2                         Modul 11: Laravel 13, Sanctum API Token & Integrasi Fetch Auth, By Novinaldi

## Ringkasan Urutan Pengerjaan

1. Pastikan PHP minimal 8.3, Composer, MySQL, dan Expo SDK 58 tersedia.
2. Buat proyek Laravel 13 `NovinaldiAppApi` sejajar dengan proyek React Native `NovinaldiApp`.
3. Tambahkan kolom `no_hp` dan `foto` pada migrasi `users`, lalu masukkan keduanya ke atribut `Fillable` model `User`.
4. Buat database MySQL `db_novinaldi_app` dan atur koneksi pada `.env`.
5. Jalankan `php artisan install:api`, aktifkan trait `HasApiTokens`, lalu jalankan `php artisan migrate`.
6. Buat `AuthController` dengan fungsi `register`, `login`, `profile`, dan `logout`; daftarkan endpoint pada `routes/api.php`.
7. Publikasikan dan periksa konfigurasi CORS, kemudian jalankan Laravel dengan `php artisan serve --host=0.0.0.0 --port=8000`.
8. Uji endpoint backend melalui Postman atau Thunder Client sebelum menghubungkan aplikasi mobile.
9. Pada proyek Expo, pasang AsyncStorage, buat `src/config/api.ts` dan `src/services/authService.ts`.
10. Perbarui halaman index, login, register, home, dan profil sesuai instruksi modul.
11. Jalankan pemeriksaan TypeScript/lint, hidupkan Expo, lalu uji register, validasi, login, auto-login, dan logout.

## Catatan Konfigurasi Penting

- Untuk pengujian melalui HP fisik/Expo Go, HP dan laptop harus berada pada jaringan Wi-Fi yang sama.
- `API_BASE_URL` harus menggunakan IPv4 laptop, misalnya `http://192.168.1.15:8000/api`; angka tersebut hanya contoh.
- Untuk pengujian melalui browser laptop (Expo Web), modul memperbolehkan penggunaan `127.0.0.1` sementara.
- Jika koneksi gagal, periksa server Laravel, IP pada `src/config/api.ts`, jaringan Wi-Fi, dan Windows Firewall pada port 8000.
- Jika `no_hp` tidak tersimpan, periksa kembali atribut `#[Fillable([...])]` pada `app/Models/User.php`.
