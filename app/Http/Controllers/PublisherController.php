<?php

namespace App\Http\Controllers;

use App\Enums\PublisherStatus;
use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Http\Controllers\Concerns\BuildsCatalogFilters;
use App\Models\User;
use App\Services\ProductSearchService;
use App\Services\SeoService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublisherController extends Controller
{
    use BuildsCatalogFilters;

    public function show(Request $request, string $username, ProductSearchService $search, SeoService $seo): View
    {
        $publisher = User::query()
            ->where('username', $username)
            ->where('status', UserStatus::Active->value)
            ->whereHas('role', fn ($q) => $q->where('slug', RoleName::Publisher->value))
            ->whereHas('publisherProfile', fn ($q) => $q->where('status', PublisherStatus::Approved->value))
            ->with('publisherProfile')
            ->firstOrFail();

        $filters = $this->catalogFilters($request) + ['publisher_id' => $publisher->getKey()];
        $products = $search->paginate($filters);

        return view('publishers.show', [
            'publisher' => $publisher,
            'products' => $products,
            'filters' => $filters,
            'formAction' => route('publishers.show', $publisher->username),
            'seo' => $seo->forPublisher($publisher, $products->currentPage()),
        ]);
    }
}
