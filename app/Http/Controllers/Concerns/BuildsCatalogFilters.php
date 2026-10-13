<?php

namespace App\Http\Controllers\Concerns;

use App\Services\ProductSearchService;
use Illuminate\Http\Request;

trait BuildsCatalogFilters
{
    /**
     * Whitelisted catalog filters from the query string.
     *
     * @return array<string, mixed>
     */
    protected function catalogFilters(Request $request): array
    {
        $sort = (string) $request->query('sort', '');

        return array_filter([
            'q' => app(ProductSearchService::class)->normaliseTerm($request->query('q')),
            'type' => in_array($request->query('type'), ['free', 'paid'], true) ? $request->query('type') : null,
            'product_type' => is_string($request->query('product_type')) ? $request->query('product_type') : null,
            'min_price' => is_numeric($request->query('min_price')) ? max(0, (float) $request->query('min_price')) : null,
            'max_price' => is_numeric($request->query('max_price')) ? max(0, (float) $request->query('max_price')) : null,
            'featured' => $request->boolean('featured') ?: null,
            'sort' => array_key_exists($sort, ProductSearchService::SORTS) ? $sort : null,
        ], fn ($value) => $value !== null && $value !== '');
    }
}
