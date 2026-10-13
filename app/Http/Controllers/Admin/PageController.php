<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PageRequest;
use App\Models\Page;
use App\Services\ActivityLogger;
use App\Services\CatalogCache;
use App\Services\SlugService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PageController extends Controller
{
    public function __construct(protected SlugService $slugs, protected ActivityLogger $logger) {}

    public function index(): View
    {
        return view('admin.pages.index', ['pages' => Page::query()->orderBy('sort_order')->orderBy('title')->get()]);
    }

    public function create(): View
    {
        return view('admin.pages.form', ['page' => new Page(['is_active' => true, 'show_in_footer' => true])]);
    }

    public function store(PageRequest $request): RedirectResponse
    {
        $page = new Page($request->safe()->except('slug'));
        $page->slug = $this->slugs->unique(Page::class, $request->validated('slug') ?: $request->validated('title'));
        $page->sort_order = (int) $request->validated('sort_order', 0);
        $page->save();

        CatalogCache::flush();
        $this->logger->log('page.created', $page, "Page \"{$page->title}\" created");

        return redirect()->route('admin.pages.index')->with('success', 'Page created.');
    }

    public function edit(Page $page): View
    {
        return view('admin.pages.form', ['page' => $page]);
    }

    public function update(PageRequest $request, Page $page): RedirectResponse
    {
        $page->fill($request->safe()->except('slug'));
        $page->sort_order = (int) $request->validated('sort_order', 0);

        $slug = $request->validated('slug') ?: $request->validated('title');
        if ($slug !== $page->slug) {
            $page->slug = $this->slugs->unique(Page::class, $slug, $page->getKey());
        }

        $page->save();
        CatalogCache::flush();
        $this->logger->log('page.updated', $page, "Page \"{$page->title}\" updated");

        return redirect()->route('admin.pages.index')->with('success', 'Page updated.');
    }

    public function destroy(Page $page): RedirectResponse
    {
        $page->delete();
        CatalogCache::flush();
        $this->logger->log('page.deleted', $page, "Page \"{$page->title}\" deleted");

        return back()->with('success', 'Page deleted.');
    }
}
