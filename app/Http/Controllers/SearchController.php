<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\BuildsCatalogFilters;
use App\Models\Category;
use App\Services\ProductSearchService;
use App\Services\SeoService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SearchController extends Controller
{
    use BuildsCatalogFilters;

    public function __invoke(Request $request, ProductSearchService $search, SeoService $seo): View
    {
        $filters = $this->catalogFilters($request);

        if ($slug = $request->query('category')) {
            $filters['category_id'] = Category::query()->active()->where('slug', (string) $slug)->value('id') ?? 0;
        }

        $term = $filters['q'] ?? '';

        return view('products.index', [
            'products' => $search->paginate($filters),
            'filters' => $filters + ['category' => $request->query('category')],
            'heading' => $term !== '' ? 'Search results for "'.$term.'"' : 'Search products',
            'formAction' => route('search'),
            'isSearch' => true,
            'seo' => $seo->defaults($term !== '' ? 'Search: '.$term : 'Search')->noindex(),
        ]);
    }
}
