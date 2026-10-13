<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * Builds the public catalog query for listings, category pages, publisher
 * pages and search, applying search terms, filters and sorting.
 */
class ProductSearchService
{
    public const SORTS = [
        'latest' => 'Latest',
        'popular' => 'Most popular',
        'downloads' => 'Most downloaded',
        'featured' => 'Featured first',
        'price_asc' => 'Price: low to high',
        'price_desc' => 'Price: high to low',
        'name' => 'Name (A-Z)',
    ];

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters, ?int $perPage = null): LengthAwarePaginator
    {
        $perPage ??= (int) config('marketplace.pagination.products', 12);

        return $this->query($filters)->paginate($perPage)->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function query(array $filters): Builder
    {
        $query = Product::query()->visible()->forCard();
        $term = $this->normaliseTerm($filters['q'] ?? null);

        if ($term !== '') {
            $this->applySearch($query, $term);
        }

        if (! empty($filters['category_id'])) {
            $query->where('category_id', (int) $filters['category_id']);
        }

        if (! empty($filters['publisher_id'])) {
            $query->where('user_id', (int) $filters['publisher_id']);
        }

        match ($filters['type'] ?? null) {
            'free' => $query->free(),
            'paid' => $query->paid(),
            default => null,
        };

        if (! empty($filters['product_type']) && array_key_exists($filters['product_type'], config('marketplace.product_types'))) {
            $query->where('product_type', $filters['product_type']);
        }

        if (! empty($filters['featured'])) {
            $query->featured();
        }

        if (is_numeric($filters['min_price'] ?? null)) {
            $query->where('effective_price', '>=', (float) $filters['min_price']);
        }

        if (is_numeric($filters['max_price'] ?? null)) {
            $query->where('effective_price', '<=', (float) $filters['max_price']);
        }

        $this->applySort($query, $filters['sort'] ?? ($term !== '' ? 'relevance' : 'latest'), $term);

        return $query;
    }

    public function normaliseTerm(mixed $term): string
    {
        return Str::limit(trim(preg_replace('/\s+/', ' ', strip_tags((string) $term))), 100, '');
    }

    protected function applySearch(Builder $query, string $term): void
    {
        $words = collect(explode(' ', $term))
            ->filter(fn ($word) => mb_strlen($word) >= (int) config('marketplace.search.min_term_length', 2))
            ->unique()
            ->take(5);

        if ($words->isEmpty()) {
            $words = collect([$term]);
        }

        // Every word must match at least one searchable field.
        foreach ($words as $word) {
            $like = '%'.$this->escapeLike($word).'%';

            $query->where(function (Builder $q) use ($like) {
                $q->where('name', 'like', $like)
                    ->orWhere('short_description', 'like', $like)
                    ->orWhere('description', 'like', $like)
                    ->orWhere('seo_keywords', 'like', $like)
                    ->orWhereHas('keywords', fn (Builder $k) => $k->where('name', 'like', $like))
                    ->orWhereHas('category', fn (Builder $c) => $c->where('name', 'like', $like))
                    ->orWhereHas('user', function (Builder $u) use ($like) {
                        $u->where('name', 'like', $like)
                            ->orWhere('username', 'like', $like)
                            ->orWhereHas('publisherProfile', fn (Builder $p) => $p->where('display_name', 'like', $like));
                    });
            });
        }
    }

    protected function applySort(Builder $query, string $sort, string $term): void
    {
        match ($sort) {
            'popular' => $query->orderByDesc('sales_count')->orderByDesc('download_count')->orderByDesc('view_count'),
            'downloads' => $query->orderByDesc('download_count'),
            'featured' => $query->orderByDesc('is_featured')->orderByDesc('published_at'),
            'price_asc' => $query->orderBy('effective_price'),
            'price_desc' => $query->orderByDesc('effective_price'),
            'name' => $query->orderBy('name'),
            'relevance' => $this->orderByRelevance($query, $term),
            default => $query->orderByDesc('published_at'),
        };

        $query->orderByDesc('id');
    }

    protected function orderByRelevance(Builder $query, string $term): void
    {
        if ($term === '') {
            $query->orderByDesc('published_at');

            return;
        }

        $escaped = $this->escapeLike($term);

        // Exact name hits first, then names starting with the term, then names containing it.
        $query->orderByRaw(
            'CASE WHEN name = ? THEN 0 WHEN name LIKE ? THEN 1 WHEN name LIKE ? THEN 2 ELSE 3 END',
            [$term, $escaped.'%', '%'.$escaped.'%']
        );

        $driver = $query->getConnection()->getDriverName();
        if (config('marketplace.search.fulltext') && in_array($driver, ['mysql', 'mariadb'], true)) {
            $query->orderByRaw('MATCH(name, short_description, description) AGAINST (? IN NATURAL LANGUAGE MODE) DESC', [$term]);
        }

        $query->orderByDesc('sales_count')->orderByDesc('published_at');
    }

    protected function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }
}
