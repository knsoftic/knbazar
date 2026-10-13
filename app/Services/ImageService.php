<?php

namespace App\Services;

use GdImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Re-encodes uploaded images with GD. Re-encoding strips metadata and any
 * payload hidden inside the original file, normalises orientation, limits
 * dimensions and produces optimised WEBP output with generated filenames.
 */
class ImageService
{
    /**
     * Store a resized copy of the image and optionally a cropped thumbnail.
     *
     * @param  array{0: int, 1: int}|null  $thumb  [width, height] for a cover-cropped thumbnail
     * @return array{path: string, thumb: string|null}
     */
    public function store(UploadedFile $file, string $directory, ?int $maxWidth = null, ?array $thumb = null, string $disk = 'public', string $field = 'image'): array
    {
        $image = $this->load($file, $field);
        $maxWidth ??= (int) config('marketplace.image.max_width', 1600);

        $main = $this->resizeToWidth($image, $maxWidth);
        $path = $this->save($main, $directory, $disk);

        $thumbPath = null;
        if ($thumb) {
            $thumbPath = $this->save($this->cover($image, $thumb[0], $thumb[1]), $directory, $disk, 'thumb_');
        }

        return ['path' => $path, 'thumb' => $thumbPath];
    }

    /**
     * Store a square, cover-cropped image (avatars, logos).
     */
    public function storeSquare(UploadedFile $file, string $directory, int $size, string $disk = 'public', string $field = 'image'): string
    {
        $image = $this->load($file, $field);

        return $this->save($this->cover($image, $size, $size), $directory, $disk);
    }

    public function delete(?string ...$paths): void
    {
        $this->deleteFrom('public', ...$paths);
    }

    public function deleteFrom(string $disk, ?string ...$paths): void
    {
        $paths = array_values(array_filter($paths));

        if ($paths) {
            Storage::disk($disk)->delete($paths);
        }
    }

    protected function load(UploadedFile $file, string $field): GdImage
    {
        $contents = @file_get_contents($file->getRealPath());
        $image = $contents !== false ? @imagecreatefromstring($contents) : false;

        if (! $image instanceof GdImage) {
            Log::channel('marketplace')->warning('[upload] Image could not be decoded', ['name' => $file->getClientOriginalName()]);

            throw ValidationException::withMessages([$field => 'The uploaded image could not be processed. Please upload a valid JPG, PNG or WEBP image.']);
        }

        if (! imageistruecolor($image)) {
            imagepalettetotruecolor($image);
        }

        imagealphablending($image, false);
        imagesavealpha($image, true);

        return $this->applyExifOrientation($image, $file);
    }

    protected function resizeToWidth(GdImage $image, int $maxWidth): GdImage
    {
        $width = imagesx($image);

        if ($width <= $maxWidth) {
            return $image;
        }

        $height = (int) round(imagesy($image) * ($maxWidth / $width));

        return $this->resample($image, 0, 0, $width, imagesy($image), $maxWidth, max(1, $height));
    }

    /**
     * Resize and centre-crop to exactly $width x $height.
     */
    protected function cover(GdImage $image, int $width, int $height): GdImage
    {
        $srcW = imagesx($image);
        $srcH = imagesy($image);
        $scale = max($width / $srcW, $height / $srcH);
        $cropW = (int) round($width / $scale);
        $cropH = (int) round($height / $scale);
        $srcX = (int) max(0, ($srcW - $cropW) / 2);
        $srcY = (int) max(0, ($srcH - $cropH) / 2);

        return $this->resample($image, $srcX, $srcY, min($cropW, $srcW), min($cropH, $srcH), $width, $height);
    }

    protected function resample(GdImage $src, int $srcX, int $srcY, int $srcW, int $srcH, int $dstW, int $dstH): GdImage
    {
        $dst = imagecreatetruecolor($dstW, $dstH);
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        imagefill($dst, 0, 0, imagecolorallocatealpha($dst, 0, 0, 0, 127));
        imagecopyresampled($dst, $src, 0, 0, $srcX, $srcY, $dstW, $dstH, $srcW, $srcH);

        return $dst;
    }

    protected function save(GdImage $image, string $directory, string $disk, string $prefix = ''): string
    {
        $quality = (int) config('marketplace.image.quality', 82);

        ob_start();
        if (function_exists('imagewebp')) {
            imagewebp($image, null, $quality);
            $extension = 'webp';
        } else {
            imagepng($image, null, 6);
            $extension = 'png';
        }
        $binary = (string) ob_get_clean();

        $path = trim($directory, '/').'/'.$prefix.Str::random(40).'.'.$extension;
        Storage::disk($disk)->put($path, $binary);

        return $path;
    }

    protected function applyExifOrientation(GdImage $image, UploadedFile $file): GdImage
    {
        if (! function_exists('exif_read_data') || ! in_array($file->getMimeType(), ['image/jpeg', 'image/jpg'], true)) {
            return $image;
        }

        $exif = @exif_read_data($file->getRealPath());
        $orientation = is_array($exif) ? (int) ($exif['Orientation'] ?? 1) : 1;

        $rotated = match ($orientation) {
            3 => imagerotate($image, 180, 0),
            6 => imagerotate($image, -90, 0),
            8 => imagerotate($image, 90, 0),
            default => $image,
        };

        return $rotated instanceof GdImage ? $rotated : $image;
    }
}
