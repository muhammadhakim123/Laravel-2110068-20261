<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ProfilePhotoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function __construct(private ProfilePhotoService $photoService) {}

    /**
     * PUT /api/profile
     * Update nama, email, dan nomor HP.
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name'  => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'no_hp' => 'required|string|max:20',
        ], [
            'name.required'  => 'Nama lengkap wajib diisi.',
            'email.required' => 'Alamat email wajib diisi.',
            'email.email'    => 'Format alamat email tidak valid.',
            'email.unique'   => 'Email ini sudah digunakan akun lain.',
            'no_hp.required' => 'Nomor HP wajib diisi.',
        ]);

        $user->update($validated);

        return response()->json([
            'success'  => true,
            'message'  => 'Profil berhasil diperbarui.',
            'user'     => $user->fresh(),
            'foto_url' => $this->photoService->url($user->foto),
        ], 200);
    }

    /**
     * PUT /api/profile/password
     * Ganti kata sandi — wajib verifikasi sandi lama.
     */
    public function updatePassword(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'current_password'      => 'required|string',
            'password'              => 'required|string|min:6|confirmed',
            'password_confirmation' => 'required|string',
        ], [
            'current_password.required' => 'Kata sandi lama wajib diisi.',
            'password.required'         => 'Kata sandi baru wajib diisi.',
            'password.min'              => 'Kata sandi baru minimal 6 karakter.',
            'password.confirmed'        => 'Konfirmasi kata sandi tidak cocok.',
        ]);

        if (! Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Kata sandi lama yang Anda masukkan salah.',
            ], 422);
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Kata sandi berhasil diperbarui.',
        ], 200);
    }

    /**
     * POST /api/profile/photo
     * Upload foto profil — crop, resize 512px, konversi WebP via PHP GD.
     * Form-data key: foto (jpg/jpeg/png/webp, maks 5 MB)
     */
    public function updatePhoto(Request $request)
    {
        $request->validate([
            'foto' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'mimetypes:image/jpeg,image/png,image/webp',
                'max:5120',
            ],
        ], [
            'foto.required'   => 'File foto wajib disertakan.',
            'foto.image'      => 'File harus berupa gambar.',
            'foto.mimes'      => 'Format foto harus JPG, JPEG, PNG, atau WEBP.',
            'foto.mimetypes'  => 'Format foto harus JPG, JPEG, PNG, atau WEBP.',
            'foto.max'        => 'Ukuran foto maksimal 5 MB.',
        ]);

        $user = $request->user();

        try {
            $storagePath = $this->photoService->storeAndOptimize($request->file('foto'), $user);
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        $user->update(['foto' => $storagePath]);

        return response()->json([
            'success'  => true,
            'message'  => 'Foto profil berhasil diperbarui.',
            'user'     => $user->fresh(),
            'foto_url' => $this->photoService->url($storagePath),
        ], 200);
    }
}
