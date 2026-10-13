<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use App\Models\Category;
use App\Models\Product;
use App\Services\CatalogCache;
use App\Services\SeoService;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(SeoService $seo): View
    {
        $ttl = (int) config('marketplace.cache.home_ttl');

        // Only product IDs and scalars are cached; models are loaded fresh below.
        $cached = Cache::remember(CatalogCache::HOME, $ttl, function () {
            $limit = (int) setting('home_products_per_section', 8);
            $ids = fn ($query) => $query->limit($limit)->pluck('id')->all();
            $base = fn () => Product::query()->visible();

            return [
                'sections' => [
                    'featured' => setting('home_show_featured') ? $ids($base()->featured()->latest('published_at')) : [],
                    'latest' => setting('home_show_latest') ? $ids($base()->latest('published_at')) : [],
                    'free' => setting('home_show_free') ? $ids($base()->free()->latest('published_at')) : [],
                    'paid' => setting('home_show_paid') ? $ids($base()->paid()->latest('published_at')) : [],
                    'popular' => setting('home_show_popular')
                        ? $ids($base()->orderByDesc('sales_count')->orderByDesc('download_count')->orderByDesc('view_count'))
                        : [],
                ],
                'stats' => [
                    'products' => Product::query()->visible()->count(),
                    'downloads' => (int) Product::query()->visible()->sum('download_count'),
                ],
            ];
        });

        $allIds = collect($cached['sections'])->flatten()->unique()->values();
        $products = $allIds->isEmpty()
            ? collect()
            : Product::query()->visible()->forCard()->whereIn('id', $allIds)->get()->keyBy('id');

        $sections = collect($cached['sections'])->map(
            fn (array $ids) => collect($ids)->map(fn ($id) => $products->get($id))->filter()->values()
        );

        $categories = setting('home_show_categories')
            ? CatalogCache::models(CatalogCache::HOME.'.categories', $ttl, Category::class, fn () => Category::query()
                ->active()->ordered()->withCount(['products' => fn ($q) => $q->visible()])->limit(12)->get())
            : collect();

        $banners = CatalogCache::models(CatalogCache::BANNERS, $ttl, Banner::class, fn () => Banner::query()->live()->limit(5)->get());

        return view('home', $sections->all() + [
            'categories' => $categories,
            'stats' => $cached['stats'],
            'banners' => $banners,
            'seo' => $seo->forHome(),
        ]);
    }
}
