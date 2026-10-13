<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\BuildsCatalogFilters;
use App\Models\Category;
use App\Services\ProductSearchService;
use App\Services\SeoService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoryController extends Controller
{
    use BuildsCatalogFilters;

    public function index(SeoService $seo): View
    {
        $categories = Category::query()
            ->active()
            ->ordered()
            ->withCount(['products' => fn ($q) => $q->visible()])
            ->get();

        return view('categories.index', [
            'categories' => $categories,
            'seo' => $seo->defaults('Categories', 'Browse digital products by category on '.setting('site_name').'.'),
        ]);
    }

    public function show(Request $request, string $slug, ProductSearchService $search, SeoService $seo): View
    {
        $category = Category::query()->active()->where('slug', $slug)->firstOrFail();
        $filters = $this->catalogFilters($request) + ['category_id' => $category->getKey()];
        $products = $search->paginate($filters);

        $meta = $seo->forCategory($category, $products->currentPage());
        if (array_diff(array_keys($filters), ['sort', 'category_id'])) {
            $meta->noindex();
        }

        return view('products.index', [
            'products' => $products,
            'filters' => $filters,
            'category' => $category,
            'heading' => $category->name,
            'subheading' => $category->description,
            'formAction' => $category->url(),
            'seo' => $meta,
        ]);
    }
}
