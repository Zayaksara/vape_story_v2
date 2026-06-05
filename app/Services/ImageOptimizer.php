<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Kompresi + resize gambar upload memakai ekstensi GD (tanpa dependency tambahan).
 *
 * Gambar dikecilkan agar muat dalam batas dimensi (tanpa upscale), lalu disimpan
 * sebagai WebP untuk ukuran file paling kecil (mendukung transparansi). Kalau WebP
 * tidak tersedia di server, otomatis fallback ke JPEG/PNG.
 */
class ImageOptimizer
{
    /**
     * Optimasi sebuah file upload lalu simpan ke disk.
     *
     * @param  UploadedFile  $file  File hasil upload.
     * @param  string  $directory  Folder tujuan di dalam disk (cth. "products").
     * @param  int  $maxWidth  Lebar maksimum (px).
     * @param  int  $maxHeight  Tinggi maksimum (px).
     * @param  int  $quality  Kualitas kompresi 1-100.
     * @param  string  $disk  Nama disk Laravel.
     * @return string Path relatif file yang tersimpan (cth. "products/uuid.webp").
     */
    public function optimize(
        UploadedFile $file,
        string $directory,
        int $maxWidth = 1000,
        int $maxHeight = 1000,
        int $quality = 80,
        string $disk = 'public',
    ): string {
        $source = $this->createImageFromFile($file);

        // Kalau GD tidak bisa membaca file (format aneh), simpan apa adanya.
        if ($source === null) {
            return $file->store($directory, $disk);
        }

        $source = $this->applyExifOrientation($source, $file);

        $srcWidth = imagesx($source);
        $srcHeight = imagesy($source);

        $scale = min($maxWidth / $srcWidth, $maxHeight / $srcHeight, 1);
        $dstWidth = max(1, (int) round($srcWidth * $scale));
        $dstHeight = max(1, (int) round($srcHeight * $scale));

        $canvas = imagecreatetruecolor($dstWidth, $dstHeight);

        // Pertahankan transparansi (penting untuk logo PNG).
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
        imagefilledrectangle($canvas, 0, 0, $dstWidth, $dstHeight, $transparent);

        imagecopyresampled($canvas, $source, 0, 0, 0, 0, $dstWidth, $dstHeight, $srcWidth, $srcHeight);
        imagedestroy($source);

        [$extension, $binary] = $this->encode($canvas, $quality);
        imagedestroy($canvas);

        $path = rtrim($directory, '/').'/'.Str::uuid()->toString().'.'.$extension;
        Storage::disk($disk)->put($path, $binary);

        return $path;
    }

    /**
     * Buat resource GD dari file upload berdasarkan tipe MIME-nya.
     */
    private function createImageFromFile(UploadedFile $file): ?\GdImage
    {
        $path = $file->getRealPath();

        if ($path === false) {
            return null;
        }

        $image = match ($file->getMimeType()) {
            'image/jpeg' => @imagecreatefromjpeg($path),
            'image/png' => @imagecreatefrompng($path),
            'image/gif' => @imagecreatefromgif($path),
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : false,
            'image/bmp' => function_exists('imagecreatefrombmp') ? @imagecreatefrombmp($path) : false,
            default => false,
        };

        return $image instanceof \GdImage ? $image : null;
    }

    /**
     * Putar gambar sesuai metadata EXIF orientation (foto dari HP sering miring).
     */
    private function applyExifOrientation(\GdImage $image, UploadedFile $file): \GdImage
    {
        if (! function_exists('exif_read_data') || $file->getMimeType() !== 'image/jpeg') {
            return $image;
        }

        $path = $file->getRealPath();
        if ($path === false) {
            return $image;
        }

        $exif = @exif_read_data($path);
        $orientation = $exif['Orientation'] ?? null;

        $angle = match ($orientation) {
            3 => 180,
            6 => -90,
            8 => 90,
            default => 0,
        };

        if ($angle !== 0) {
            $rotated = imagerotate($image, $angle, 0);
            if ($rotated instanceof \GdImage) {
                imagedestroy($image);

                return $rotated;
            }
        }

        return $image;
    }

    /**
     * Encode canvas ke format paling efisien yang tersedia.
     *
     * @return array{0: string, 1: string} [ekstensi, binary]
     */
    private function encode(\GdImage $canvas, int $quality): array
    {
        if (function_exists('imagewebp')) {
            ob_start();
            imagewebp($canvas, null, $quality);

            return ['webp', (string) ob_get_clean()];
        }

        // Fallback: PNG untuk gambar bertransparansi, JPEG selain itu.
        ob_start();
        imagejpeg($canvas, null, $quality);

        return ['jpg', (string) ob_get_clean()];
    }
}
