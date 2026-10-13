<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Stores downloadable product files on the private "local" disk
 * (storage/app/private), which is never web accessible.
 */
class FileUploadService
{
    public const PRIVATE_DISK = 'local';

    /**
     * @return array{path: string, name: string, size: int, type: string, mime: string}
     */
    public function storeProductFile(UploadedFile $file): array
    {
        $extension = Str::lower($file->getClientOriginalExtension());
        $mime = (string) $file->getMimeType();

        // Defence in depth: the form request already validated these.
        if (in_array($extension, config('marketplace.blocked_extensions', []), true)
            || in_array($mime, config('marketplace.blocked_mimes', []), true)) {
            Log::channel('marketplace')->warning('[upload] Blocked product file', ['extension' => $extension, 'mime' => $mime]);

            throw ValidationException::withMessages(['file' => 'This file type is not allowed.']);
        }

        $directory = 'products/files/'.now()->format('Y/m');
        $stored = $file->storeAs($directory, Str::random(40).'.'.$extension, self::PRIVATE_DISK);

        if ($stored === false) {
            Log::channel('marketplace')->error('[upload] Product file could not be stored', ['name' => $file->getClientOriginalName()]);

            throw ValidationException::withMessages(['file' => 'The file could not be saved. Please try again.']);
        }

        return [
            'path' => $stored,
            'name' => $this->safeName($file->getClientOriginalName(), $extension),
            'size' => (int) $file->getSize(),
            'type' => $extension,
            'mime' => Str::limit($mime, 115, ''),
        ];
    }

    /**
     * Store an uploaded file on the public disk without processing (favicons).
     */
    public function storePublicRaw(UploadedFile $file, string $directory): string
    {
        $extension = Str::lower($file->guessExtension() ?: $file->getClientOriginalExtension());

        return $file->storeAs(trim($directory, '/'), Str::random(40).'.'.$extension, 'public');
    }

    public function deletePrivate(?string $path): void
    {
        if ($path) {
            Storage::disk(self::PRIVATE_DISK)->delete($path);
        }
    }

    /**
     * The original filename is never trusted for storage; it is only kept,
     * slugified, for display purposes.
     */
    public function safeName(string $original, string $extension): string
    {
        $base = Str::limit(Str::slug(pathinfo($original, PATHINFO_FILENAME)), 100, '') ?: 'file';

        return $base.'.'.$extension;
    }
}
