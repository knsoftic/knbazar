<?php

namespace App\Support;

use App\Rules\SafeProductFile;

/**
 * Validation rule sets for uploads, driven by the admin upload settings.
 */
class UploadRules
{
    /**
     * @return array<int, mixed>
     */
    public static function image(bool $required = false): array
    {
        return [
            $required ? 'required' : 'nullable',
            'file',
            'image',
            'mimes:'.self::imageTypes(),
            'max:'.self::imageMaxKb(),
            'dimensions:max_width=8000,max_height=8000',
        ];
    }

    /**
     * @return array<int, mixed>
     */
    public static function productFile(bool $required = false): array
    {
        return [
            $required ? 'required' : 'nullable',
            'file',
            'max:'.((int) setting('upload_file_max_mb', 100) * 1024),
            SafeProductFile::fromSettings(),
        ];
    }

    /**
     * @return array<int, mixed>
     */
    public static function favicon(): array
    {
        return ['nullable', 'file', 'mimes:png,ico', 'max:512'];
    }

    public static function imageTypes(): string
    {
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        $configured = array_map('trim', explode(',', strtolower((string) setting('upload_image_types', 'jpg,jpeg,png,webp'))));

        return implode(',', array_intersect($configured, $allowed) ?: ['jpg', 'jpeg', 'png', 'webp']);
    }

    public static function imageMaxKb(): int
    {
        return (int) setting('upload_image_max_kb', 2048);
    }

    public static function imageAccept(): string
    {
        return collect(explode(',', self::imageTypes()))->map(fn ($ext) => '.'.$ext)->implode(',');
    }

    public static function fileAccept(): string
    {
        return collect(explode(',', (string) setting('upload_file_types')))
            ->map(fn ($ext) => '.'.trim($ext))
            ->implode(',');
    }
}
