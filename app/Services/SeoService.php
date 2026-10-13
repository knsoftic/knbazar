<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use App\Models\User;
use App\Support\SeoData;
use Illuminate\Support\Str;

/**
 * Generates page metadata. Configured SEO values always win; otherwise
 * sensible defaults are derived from the content and the site settings.
 */
class SeoService
{
    public function defaults(?string $title = null, ?string $description = null): SeoData
    {
        return new SeoData(
            title: $title ? $this->formatTitle($title) : (string) setting('seo_default_title'),
            description: $this->clean($description ?: setting('seo_default_description')),
            keywords: setting('seo_default_keywords') ?: null,
            canonical: url()->current(),
            robots: $this->robots(),
            ogImage: storage_url(setting('seo_default_og_image')),
        );
    }

    public function forHome(): SeoData
    {
        $seo = $this->defaults();
        $seo->title = setting('seo_home_title') ?: (string) setting('seo_default_title');
        $seo->description = $this->clean(setting('seo_home_description') ?: setting('seo_default_description'));
        $seo->keywords = setting('seo_home_keywords') ?: $seo->keywords;
        $seo->canonical = route('home');
        $seo->jsonLd = [
            [
                '@context' => 'https://schema.org',
                '@type' => 'WebSite',
                'name' => setting('site_name'),
                'url' => route('home'),
                'potentialAction' => [
                    '@type' => 'SearchAction',
                    'target' => route('search').'?q={search_term_string}',
                    'query-input' => 'required name=search_term_string',
                ],
            ],
            $this->organization(),
        ];

        return $seo;
    }

    public function forProduct(Product $product): SeoData
    {
        $description = $product->seo_description
            ?: $product->short_description
            ?: Str::limit($this->clean(markdown($product->description)), 160);

        $keywords = $product->seo_keywords
            ?: $product->keywords->pluck('name')->take(10)->implode(', ')
            ?: null;

        $image = $product->ogImageUrl() ?? storage_url(setting('seo_default_og_image'));

        $seo = new SeoData(
            title: $this->formatTitle($product->seo_title ?: $product->name),
            description: $this->clean($description),
            keywords: $keywords,
            canonical: $product->canonical_url ?: $product->url(),
            robots: $this->robots(),
            ogType: 'product',
            ogTitle: $product->og_title ?: ($product->seo_title ?: $product->name),
            ogDescription: $this->clean($product->og_description ?: $description),
            ogImage: $image,
        );

        $seo->jsonLd = [$this->productSchema($product, $seo), $this->breadcrumbs([
            ['name' => 'Home', 'url' => route('home')],
            ['name' => $product->category?->name ?? 'Products', 'url' => $product->category?->url() ?? route('products.index')],
            ['name' => $product->name, 'url' => $product->url()],
        ])];

        return $seo;
    }

    public function forCategory(Category $category, int $page = 1): SeoData
    {
        $seo = $this->defaults(
            $category->seo_title ?: $category->name,
            $category->seo_description ?: $category->description ?: 'Browse '.$category->name.' on '.setting('site_name').'.'
        );
        $seo->keywords = $category->seo_keywords ?: $seo->keywords;
        $seo->canonical = $category->url().($page > 1 ? '?page='.$page : '');
        $seo->ogImage = $category->imageUrl() ?? $seo->ogImage;
        $seo->jsonLd = [$this->breadcrumbs([
            ['name' => 'Home', 'url' => route('home')],
            ['name' => $category->name, 'url' => $category->url()],
        ])];

        return $seo;
    }

    public function forPage(Page $page): SeoData
    {
        $seo = $this->defaults(
            $page->seo_title ?: $page->title,
            $page->seo_description ?: Str::limit($this->clean(markdown($page->content, true)), 160)
        );
        $seo->keywords = $page->seo_keywords ?: $seo->keywords;
        $seo->canonical = $page->url();

        return $seo;
    }

    public function forPublisher(User $publisher, int $page = 1): SeoData
    {
        $profile = $publisher->publisherProfile;
        $seo = $this->defaults(
            $publisher->publicName().' - Publisher',
            $profile?->bio ? Str::limit($profile->bio, 160) : 'Digital products by '.$publisher->publicName().'.'
        );
        $seo->canonical = route('publishers.show', $publisher->username).($page > 1 ? '?page='.$page : '');
        $seo->ogType = 'profile';
        $seo->ogImage = $profile?->logoUrl() ?? $seo->ogImage;

        return $seo;
    }

    public function formatTitle(string $title): string
    {
        $site = (string) setting('site_name');

        if ($site === '' || Str::contains($title, $site)) {
            return $title;
        }

        return $title.(setting('seo_title_separator') ?: ' | ').$site;
    }

    public function robots(): string
    {
        return setting('seo_robots_index') ? 'index,follow' : 'noindex,nofollow';
    }

    /**
     * @return array<string, mixed>
     */
    protected function productSchema(Product $product, SeoData $seo): array
    {
        $images = array_values(array_filter([
            $product->image ? $product->imageUrl() : null,
            ...$product->images->map(fn ($image) => $image->url())->all(),
        ]));

        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $product->name,
            'description' => $seo->description,
            'image' => $images ?: null,
            'sku' => 'P'.$product->getKey(),
            'productID' => (string) $product->getKey(),
            'category' => $product->category?->name,
            'brand' => ['@type' => 'Brand', 'name' => $product->publisherName() ?: setting('site_name')],
            'keywords' => $seo->keywords,
            'offers' => [
                '@type' => 'Offer',
                'url' => $product->url(),
                'price' => number_format($product->finalPrice(), 2, '.', ''),
                'priceCurrency' => currency_code(),
                'availability' => 'https://schema.org/'.($product->isPublished() ? 'InStock' : 'OutOfStock'),
                'itemCondition' => 'https://schema.org/NewCondition',
                'seller' => ['@type' => 'Organization', 'name' => setting('site_name')],
            ],
        ], fn ($value) => $value !== null && $value !== '');
    }

    /**
     * @param  array<int, array{name: string, url: string}>  $items
     * @return array<string, mixed>
     */
    protected function breadcrumbs(array $items): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => collect($items)->values()->map(fn (array $item, int $i) => [
                '@type' => 'ListItem',
                'position' => $i + 1,
                'name' => $item['name'],
                'item' => $item['url'],
            ])->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function organization(): array
    {
        $sameAs = collect(['social_facebook', 'social_twitter', 'social_instagram', 'social_youtube', 'social_linkedin', 'social_tiktok', 'social_github'])
            ->map(fn ($key) => setting($key))
            ->filter()
            ->values()
            ->all();

        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => setting('site_name'),
            'url' => route('home'),
            'logo' => storage_url(setting('site_logo')),
            'email' => setting('contact_email') ?: null,
            'sameAs' => $sameAs ?: null,
        ]);
    }

    protected function clean(?string $text): ?string
    {
        if (blank($text)) {
            return null;
        }

        return Str::limit(trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags($text)))), 300, '...');
    }
}
