<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ProfilePhotoService;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function __construct(
        private readonly ProfilePhotoService $photoService
    ) {}

    /**
     * GET /api/profile
     * Ambil data profil user yang sedang login beserta URL foto.
     */
    public function show(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'success'   => true,
            'user'      => $user,
            'foto_url'  => $this->photoService->url($user->foto),
        ], 200);
    }

    /**
     * POST /api/profile/photo
     * Upload / ganti foto profil.
     *
     * Form-data key : foto  (file: jpg, jpeg, png, webp — maks 2 MB)
     */
    public function updatePhoto(Request $request)
    {
        $request->validate([
            'foto' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
        ], [
            'foto.required' => 'File foto wajib disertakan.',
            'foto.image'    => 'File harus berupa gambar.',
            'foto.mimes'    => 'Format foto harus JPG, JPEG, PNG, atau WEBP.',
            'foto.max'      => 'Ukuran foto maksimal 2 MB.',
        ]);

        $user     = $request->user();
        $filename = $this->photoService->store($request->file('foto'), $user->foto);

        $user->update(['foto' => $filename]);

        return response()->json([
            'success'  => true,
            'message'  => 'Foto profil berhasil diperbarui.',
            'foto'     => $filename,
            'foto_url' => $this->photoService->url($filename),
        ], 200);
    }

    /**
     * DELETE /api/profile/photo
     * Hapus foto profil (set kolom foto menjadi null).
     */
    public function deletePhoto(Request $request)
    {
        $user = $request->user();

        if (! $user->foto) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada foto profil yang perlu dihapus.',
            ], 422);
        }

        $this->photoService->delete($user->foto);
        $user->update(['foto' => null]);

        return response()->json([
            'success' => true,
            'message' => 'Foto profil berhasil dihapus.',
        ], 200);
    }
}
