<?php

namespace App\Http\Controllers;

use App\Enums\PublisherStatus;
use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use App\Models\User;
use App\Services\CatalogCache;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

/**
 * Dynamic robots.txt and XML sitemap.
 */
class SeoFileController extends Controller
{
    public function robots(): Response
    {
        $lines = ['User-agent: *'];

        if (! setting('seo_robots_index')) {
            $lines[] = 'Disallow: /';
        } else {
            foreach (['/admin', '/publisher/products', '/account', '/orders', '/downloads', '/download/', '/cart', '/checkout', '/notifications', '/search'] as $path) {
                $lines[] = 'Disallow: '.$path;
            }
            $lines[] = 'Allow: /';

            if (setting('seo_sitemap_enabled')) {
                $lines[] = '';
                $lines[] = 'Sitemap: '.route('sitemap');
            }
        }

        return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    public function sitemap(): Response
    {
        abort_unless(setting('seo_sitemap_enabled'), 404);

        $xml = Cache::remember(CatalogCache::SITEMAP, config('marketplace.cache.sitemap_ttl'), function () {
            $urls = [
                ['loc' => route('home'), 'lastmod' => null, 'priority' => '1.0', 'changefreq' => 'daily'],
                ['loc' => route('products.index'), 'lastmod' => null, 'priority' => '0.9', 'changefreq' => 'daily'],
                ['loc' => route('categories.index'), 'lastmod' => null, 'priority' => '0.6', 'changefreq' => 'weekly'],
            ];

            if (setting('seo_sitemap_products')) {
                Product::query()->visible()->select(['id', 'slug', 'canonical_url', 'updated_at'])->orderBy('id')
                    ->chunk(500, function ($products) use (&$urls) {
                        foreach ($products as $product) {
                            // Products canonicalised elsewhere are left out.
                            if ($product->canonical_url && $product->canonical_url !== $product->url()) {
                                continue;
                            }
                            $urls[] = ['loc' => $product->url(), 'lastmod' => $product->updated_at, 'priority' => '0.8', 'changefreq' => 'weekly'];
                        }
                    });
            }

            if (setting('seo_sitemap_categories')) {
                foreach (Category::query()->active()->ordered()->get(['id', 'slug', 'updated_at']) as $category) {
                    $urls[] = ['loc' => $category->url(), 'lastmod' => $category->updated_at, 'priority' => '0.7', 'changefreq' => 'weekly'];
                }
            }

            if (setting('seo_sitemap_pages')) {
                foreach (Page::query()->active()->get(['id', 'slug', 'updated_at']) as $page) {
                    $urls[] = [
                        'loc' => $page->slug === 'contact' ? route('contact.show') : $page->url(),
                        'lastmod' => $page->updated_at,
                        'priority' => '0.4',
                        'changefreq' => 'monthly',
                    ];
                }
            }

            if (setting('seo_sitemap_publishers')) {
                User::query()
                    ->where('status', UserStatus::Active->value)
                    ->whereHas('role', fn ($q) => $q->where('slug', RoleName::Publisher->value))
                    ->whereHas('publisherProfile', fn ($q) => $q->where('status', PublisherStatus::Approved->value))
                    ->whereHas('products', fn ($q) => $q->visible())
                    ->get(['id', 'username', 'updated_at', 'role_id'])
                    ->each(function (User $user) use (&$urls) {
                        $urls[] = ['loc' => route('publishers.show', $user->username), 'lastmod' => $user->updated_at, 'priority' => '0.5', 'changefreq' => 'weekly'];
                    });
            }

            return view('seo.sitemap', ['urls' => $urls])->render();
        });

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
