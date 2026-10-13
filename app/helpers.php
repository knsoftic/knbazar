<?php

use App\Services\SettingService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

if (! function_exists('setting')) {
    /**
     * Read a runtime setting (database value, falling back to config/settings.php).
     */
    function setting(string $key, mixed $default = null): mixed
    {
        return app(SettingService::class)->get($key, $default);
    }
}

if (! function_exists('currency_code')) {
    function currency_code(): string
    {
        return (string) setting('currency', 'PKR');
    }
}

if (! function_exists('money')) {
    /**
     * Format an amount using the configured currency symbol and position.
     */
    function money(float|int|string|null $amount, ?string $currency = null): string
    {
        $currency = $currency ?: currency_code();
        $symbol = config('marketplace.currencies.'.$currency, $currency);
        $amount = (float) $amount;
        $decimals = fmod($amount, 1.0) === 0.0 ? 0 : 2;
        $formatted = number_format($amount, $decimals);

        return setting('currency_position', 'before') === 'after'
            ? $formatted.' '.$symbol
            : $symbol.' '.$formatted;
    }
}

if (! function_exists('format_bytes')) {
    function format_bytes(int|float|null $bytes, int $precision = 1): string
    {
        $bytes = max(0, (float) $bytes);

        if ($bytes < 1024) {
            return (int) $bytes.' B';
        }

        $units = ['KB', 'MB', 'GB', 'TB'];
        $power = min((int) floor(log($bytes, 1024)), count($units));

        return round($bytes / (1024 ** $power), $precision).' '.$units[$power - 1];
    }
}

if (! function_exists('storage_url')) {
    /**
     * Public URL for a file on the public disk, or null.
     */
    function storage_url(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        // Local public disk: build the URL from the current host (via the
        // public/storage symlink) so it works on any domain without relying
        // on APP_URL. Cloud disks (S3, ...) return their own URL.
        if (config('filesystems.disks.public.driver') === 'local') {
            return asset('storage/'.ltrim($path, '/'));
        }

        return Storage::disk('public')->url($path);
    }
}

if (! function_exists('markdown')) {
    /**
     * Render untrusted Markdown safely (raw HTML escaped, unsafe links removed).
     */
    function markdown(?string $text, bool $allowHtml = false): string
    {
        if (blank($text)) {
            return '';
        }

        return (string) Str::markdown($text, [
            'html_input' => $allowHtml ? 'allow' : 'escape',
            'allow_unsafe_links' => false,
            'max_nesting_level' => 20,
        ]);
    }
}
