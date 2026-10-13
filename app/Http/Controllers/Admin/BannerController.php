<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BannerRequest;
use App\Models\Banner;
use App\Services\ActivityLogger;
use App\Services\CatalogCache;
use App\Services\ImageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class BannerController extends Controller
{
    public function __construct(protected ImageService $images, protected ActivityLogger $logger) {}

    public function index(): View
    {
        return view('admin.banners.index', ['banners' => Banner::query()->orderBy('sort_order')->latest('id')->get()]);
    }

    public function create(): View
    {
        return view('admin.banners.form', ['banner' => new Banner(['is_active' => true])]);
    }

    public function store(BannerRequest $request): RedirectResponse
    {
        $banner = new Banner($request->safe()->except('image'));
        $banner->sort_order = (int) $request->validated('sort_order', 0);

        if ($request->hasFile('image')) {
            $banner->image = $this->images->store($request->file('image'), 'banners', 1920)['path'];
        }

        $banner->save();
        CatalogCache::flush();
        $this->logger->log('banner.created', $banner, "Banner \"{$banner->title}\" created");

        return redirect()->route('admin.banners.index')->with('success', 'Banner created.');
    }

    public function edit(Banner $banner): View
    {
        return view('admin.banners.form', ['banner' => $banner]);
    }

    public function update(BannerRequest $request, Banner $banner): RedirectResponse
    {
        $banner->fill($request->safe()->except('image'));
        $banner->sort_order = (int) $request->validated('sort_order', 0);

        if ($request->hasFile('image')) {
            $this->images->delete($banner->image);
            $banner->image = $this->images->store($request->file('image'), 'banners', 1920)['path'];
        }

        $banner->save();
        CatalogCache::flush();
        $this->logger->log('banner.updated', $banner, "Banner \"{$banner->title}\" updated");

        return redirect()->route('admin.banners.index')->with('success', 'Banner updated.');
    }

    public function destroy(Banner $banner): RedirectResponse
    {
        $this->images->delete($banner->image);
        $banner->delete();
        CatalogCache::flush();
        $this->logger->log('banner.deleted', $banner, "Banner \"{$banner->title}\" deleted");

        return back()->with('success', 'Banner deleted.');
    }
}
