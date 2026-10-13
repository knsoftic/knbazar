<?php

namespace App\Services;

use Closure;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Central list of cache keys for public catalog data, so every write path
 * can invalidate them consistently.
 *
 * Only plain arrays are cached (never serialized PHP objects): Laravel 13
 * refuses to unserialize classes from the cache by default to prevent
 * gadget-chain attacks, which is the safer behaviour to keep.
 */
class CatalogCache
{
    public const HOME = 'catalog.home';

    public const NAV_CATEGORIES = 'catalog.nav_categories';

    public const FOOTER_PAGES = 'catalog.footer_pages';

    public const SITEMAP = 'catalog.sitemap';

    public const BANNERS = 'catalog.banners';

    public static function flush(): void
    {
        foreach ([self::HOME, self::HOME.'.categories', self::NAV_CATEGORIES, self::FOOTER_PAGES, self::SITEMAP, self::BANNERS] as $key) {
            Cache::forget($key);
        }
    }

    /**
     * Cache a query's raw attribute rows and hydrate them back into models.
     *
     * @template TModel of Model
     *
     * @param  class-string<TModel>  $model
     * @param  Closure(): Collection<int, TModel>  $query
     * @return Collection<int, TModel>
     */
    public static function models(string $key, int $ttl, string $model, Closure $query): Collection
    {
        $rows = Cache::remember($key, $ttl, fn () => $query()->map(fn (Model $m) => $m->getAttributes())->all());

        return $model::hydrate(is_array($rows) ? $rows : []);
    }
}
