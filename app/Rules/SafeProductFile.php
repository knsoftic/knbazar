<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

/**
 * Validates a downloadable product file: the extension must be in the admin
 * allow-list and never in the hard block-list, and the detected (content
 * sniffed) MIME type must not be an executable/script/HTML type.
 */
class SafeProductFile implements ValidationRule
{
    /**
     * @param  array<int, string>  $allowedExtensions
     */
    public function __construct(protected array $allowedExtensions) {}

    public static function fromSettings(): self
    {
        return new self(array_filter(array_map('trim', explode(',', Str::lower((string) setting('upload_file_types'))))));
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile || ! $value->isValid()) {
            $fail('The :attribute upload failed.');

            return;
        }

        $extension = Str::lower($value->getClientOriginalExtension());
        $blocked = config('marketplace.blocked_extensions', []);

        if ($extension === '' || in_array($extension, $blocked, true) || ! in_array($extension, $this->allowedExtensions, true)) {
            $fail('The :attribute must be one of: '.implode(', ', array_diff($this->allowedExtensions, $blocked)).'.');

            return;
        }

        // Reject double extensions such as "theme.php.zip" that hide script names.
        $name = Str::lower($value->getClientOriginalName());
        foreach ($blocked as $bad) {
            if (str_contains($name, '.'.$bad.'.')) {
                $fail('The :attribute name contains a forbidden extension.');

                return;
            }
        }

        if (in_array((string) $value->getMimeType(), config('marketplace.blocked_mimes', []), true)) {
            $fail('The :attribute content type is not allowed.');
        }
    }
}
