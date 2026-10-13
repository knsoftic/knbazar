<?php

namespace App\Support;

/**
 * Metadata rendered into <head> by resources/views/partials/seo.blade.php.
 */
class SeoData
{
    /**
     * @param  array<int, array<string, mixed>>  $jsonLd
     */
    public function __construct(
        public string $title,
        public ?string $description = null,
        public ?string $keywords = null,
        public ?string $canonical = null,
        public string $robots = 'index,follow',
        public string $ogType = 'website',
        public ?string $ogTitle = null,
        public ?string $ogDescription = null,
        public ?string $ogImage = null,
        public string $twitterCard = 'summary_large_image',
        public array $jsonLd = [],
    ) {}

    public function noindex(): static
    {
        $this->robots = 'noindex,follow';

        return $this;
    }
}
