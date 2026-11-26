<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class ProfilePhotoService
{
    private const DISK      = 'public';
    private const DIRECTORY = 'profiles';
    private const MAX_SIZE  = 512;   // px — sisi maksimal setelah resize
    private const QUALITY   = 80;    // WebP / JPEG quality

    /**
     * Simpan, crop, resize, konversi ke WebP, hapus foto lama.
     * Kembalikan path relatif yang disimpan ke kolom `foto` di tabel users.
     * Contoh: profiles/user_1_1732600000.webp
     */
    public function storeAndOptimize(UploadedFile $file, User $user): string
    {
        $tmpPath = $file->getRealPath();
        $mime    = $file->getMimeType();

        // 1. Buat resource GD dari file upload
        $src = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($tmpPath),
            'image/png'  => @imagecreatefrompng($tmpPath),
            'image/webp' => @imagecreatefromwebp($tmpPath),
            default      => false,
        };

        if (! $src) {
            throw new RuntimeException('Format gambar tidak didukung atau file rusak.');
        }

        // 2. Auto-rotate berdasarkan data EXIF (foto dari kamera HP)
        $src = $this->autoRotate($src, $tmpPath, $mime);

        // 3. Center-crop menjadi persegi 1:1
        $src = $this->centerCrop($src);

        // 4. Resize ke maksimal MAX_SIZE × MAX_SIZE px
        $src = $this->resize($src, self::MAX_SIZE);

        // 5. Encode ke WebP (fallback JPEG jika GD tidak support WebP)
        $ext      = function_exists('imagewebp') ? 'webp' : 'jpg';
        $filename = 'user_' . $user->id . '_' . time() . '.' . $ext;
        $tmpOut   = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $filename;

        $encoded = $ext === 'webp'
            ? imagewebp($src, $tmpOut, self::QUALITY)
            : imagejpeg($src, $tmpOut, self::QUALITY);

        imagedestroy($src);

        if (! $encoded) {
            throw new RuntimeException('Gagal mengonversi gambar ke ' . strtoupper($ext) . '.');
        }

        // 6. Hapus foto profil lama
        $this->delete($user->foto);

        // 7. Pindahkan file hasil encode ke storage
        $storagePath = self::DIRECTORY . '/' . $filename;
        Storage::disk(self::DISK)->put($storagePath, file_get_contents($tmpOut));
        @unlink($tmpOut);

        return $storagePath;
    }

    /**
     * Hapus file foto dari storage berdasarkan path relatif kolom `foto`.
     */
    public function delete(?string $storagePath): void
    {
        if (! $storagePath) {
            return;
        }

        if (Storage::disk(self::DISK)->exists($storagePath)) {
            Storage::disk(self::DISK)->delete($storagePath);
        }
    }

    /**
     * Kembalikan URL publik foto profil, atau null jika belum ada.
     */
    public function url(?string $storagePath): ?string
    {
        if (! $storagePath) {
            return null;
        }

        return Storage::disk(self::DISK)->url($storagePath);
    }

    // ─── Private helpers ──────────────────────────────────────────────────────

    private function autoRotate(\GdImage $img, string $path, string $mime): \GdImage
    {
        if ($mime !== 'image/jpeg' || ! function_exists('exif_read_data')) {
            return $img;
        }

        $exif = @exif_read_data($path);
        $orientation = $exif['Orientation'] ?? 1;

        $img = match ((int) $orientation) {
            3       => imagerotate($img, 180, 0),
            6       => imagerotate($img, -90, 0),
            8       => imagerotate($img, 90, 0),
            default => $img,
        };

        return $img;
    }

    private function centerCrop(\GdImage $img): \GdImage
    {
        $w = imagesx($img);
        $h = imagesy($img);

        if ($w === $h) {
            return $img;
        }

        $side = min($w, $h);
        $x    = (int) (($w - $side) / 2);
        $y    = (int) (($h - $side) / 2);

        $cropped = imagecreatetruecolor($side, $side);
        imagecopy($cropped, $img, 0, 0, $x, $y, $side, $side);
        imagedestroy($img);

        return $cropped;
    }

    private function resize(\GdImage $img, int $maxSide): \GdImage
    {
        $w = imagesx($img);
        $h = imagesy($img);

        if ($w <= $maxSide && $h <= $maxSide) {
            return $img;
        }

        $resized = imagecreatetruecolor($maxSide, $maxSide);
        imagecopyresampled($resized, $img, 0, 0, 0, 0, $maxSide, $maxSide, $w, $h);
        imagedestroy($img);

        return $resized;
    }
}
