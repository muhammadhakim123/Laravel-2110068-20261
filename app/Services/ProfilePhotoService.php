<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProfilePhotoService
{
    /**
     * Direktori penyimpanan foto profil di dalam storage/app/public/
     */
    private const DISK      = 'public';
    private const DIRECTORY = 'profile_photos';

    /**
     * Simpan foto baru dan hapus foto lama jika ada.
     *
     * @param  UploadedFile  $file      File gambar yang diupload
     * @param  string|null   $oldPhoto  Nama file lama (nilai kolom `foto` di tabel users)
     * @return string                   Nama file baru yang disimpan ke kolom `foto`
     */
    public function store(UploadedFile $file, ?string $oldPhoto = null): string
    {
        // Hapus foto lama terlebih dahulu agar storage tidak menumpuk
        $this->delete($oldPhoto);

        // Buat nama file unik: foto_<uuid>.<ext>
        $extension = $file->getClientOriginalExtension() ?: $file->guessExtension();
        $filename  = 'foto_' . Str::uuid() . '.' . $extension;

        // Simpan ke storage/app/public/profile_photos/<filename>
        $file->storeAs(self::DIRECTORY, $filename, self::DISK);

        return $filename;
    }

    /**
     * Hapus file foto dari storage.
     *
     * @param  string|null  $filename  Nilai kolom `foto` di tabel users
     */
    public function delete(?string $filename): void
    {
        if (! $filename) {
            return;
        }

        $path = self::DIRECTORY . '/' . $filename;

        if (Storage::disk(self::DISK)->exists($path)) {
            Storage::disk(self::DISK)->delete($path);
        }
    }

    /**
     * Kembalikan URL publik foto profil, atau null jika belum ada.
     *
     * @param  string|null  $filename
     * @return string|null
     */
    public function url(?string $filename): ?string
    {
        if (! $filename) {
            return null;
        }

        return Storage::disk(self::DISK)->url(self::DIRECTORY . '/' . $filename);
    }
}
