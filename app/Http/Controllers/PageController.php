<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Services\SeoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PageController extends Controller
{
    public function show(string $slug, SeoService $seo): View|RedirectResponse
    {
        if ($slug === 'contact') {
            return redirect()->route('contact.show', status: 301);
        }

        $page = Page::query()->active()->where('slug', $slug)->firstOrFail();

        return view('pages.show', [
            'page' => $page,
            'seo' => $seo->forPage($page),
        ]);
    }
}
