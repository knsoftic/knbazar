<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CategoryRequest;
use App\Models\Category;
use App\Services\ActivityLogger;
use App\Services\CatalogCache;
use App\Services\ImageService;
use App\Services\SlugService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function __construct(
        protected ImageService $images,
        protected SlugService $slugs,
        protected ActivityLogger $logger,
    ) {}

    public function index(Request $request): View
    {
        $categories = Category::query()
            ->with('parent:id,name')
            ->withCount(['products', 'products as published_count' => fn ($q) => $q->published()])
            ->when($request->filled('q'), fn ($q) => $q->where('name', 'like', '%'.$request->query('q').'%'))
            ->ordered()
            ->paginate(config('marketplace.pagination.admin'))
            ->withQueryString();

        return view('admin.categories.index', ['categories' => $categories]);
    }

    public function create(): View
    {
        return view('admin.categories.form', ['category' => new Category(['is_active' => true]), 'parents' => $this->parents()]);
    }

    public function store(CategoryRequest $request): RedirectResponse
    {
        $category = new Category($request->safe()->except(['image', 'remove_image', 'slug']));
        $category->slug = $this->slugs->unique(Category::class, $request->validated('slug') ?: $request->validated('name'));
        $category->sort_order = (int) $request->validated('sort_order', 0);

        if ($request->hasFile('image')) {
            $category->image = $this->images->store($request->file('image'), 'categories', 800)['path'];
        }

        $category->save();
        CatalogCache::flush();
        $this->logger->log('category.created', $category, "Category \"{$category->name}\" created");

        return redirect()->route('admin.categories.index')->with('success', 'Category created.');
    }

    public function edit(Category $category): View
    {
        return view('admin.categories.form', ['category' => $category, 'parents' => $this->parents($category)]);
    }

    public function update(CategoryRequest $request, Category $category): RedirectResponse
    {
        $category->fill($request->safe()->except(['image', 'remove_image', 'slug']));
        $category->sort_order = (int) $request->validated('sort_order', 0);

        $slug = $request->validated('slug') ?: $request->validated('name');
        if ($slug !== $category->slug) {
            $category->slug = $this->slugs->unique(Category::class, $slug, $category->getKey());
        }

        if ($request->hasFile('image')) {
            $this->images->delete($category->image);
            $category->image = $this->images->store($request->file('image'), 'categories', 800)['path'];
        } elseif ($request->boolean('remove_image') && $category->image) {
            $this->images->delete($category->image);
            $category->image = null;
        }

        $category->save();
        CatalogCache::flush();
        $this->logger->log('category.updated', $category, "Category \"{$category->name}\" updated");

        return redirect()->route('admin.categories.index')->with('success', 'Category updated.');
    }

    public function toggle(Category $category): RedirectResponse
    {
        $category->forceFill(['is_active' => ! $category->is_active])->save();
        CatalogCache::flush();
        $this->logger->log('category.toggled', $category, "Category \"{$category->name}\" ".($category->is_active ? 'activated' : 'deactivated'));

        return back()->with('success', 'Category '.($category->is_active ? 'activated.' : 'deactivated. Its products are hidden from the marketplace.'));
    }

    public function destroy(Category $category): RedirectResponse
    {
        if ($category->products()->withTrashed()->exists()) {
            return back()->with('error', 'This category still has products. Move them to another category or deactivate the category instead.');
        }

        $category->children()->update(['parent_id' => null]);
        $category->delete();
        CatalogCache::flush();
        $this->logger->log('category.deleted', $category, "Category \"{$category->name}\" deleted");

        return back()->with('success', 'Category deleted.');
    }

    protected function parents(?Category $except = null)
    {
        return Category::query()
            ->whereNull('parent_id')
            ->when($except?->exists, fn ($q) => $q->whereKeyNot($except->getKey()))
            ->ordered()
            ->get(['id', 'name']);
    }
}
