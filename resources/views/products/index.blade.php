@extends('layouts.app')

@php
    $current = fn (array $merge = [], array $forget = []) => array_filter(
        array_diff_key(array_merge(request()->except(['page']), $merge), array_flip($forget)),
        fn ($v) => $v !== null && $v !== ''
    );
    $category ??= null;
    $isSearch ??= false;
@endphp

@section('page-header')
    <section class="page-header">
        <div class="container">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                    @if ($category)
                        <li class="breadcrumb-item"><a href="{{ route('categories.index') }}">Categories</a></li>
                        <li class="breadcrumb-item active" aria-current="page">{{ $category->name }}</li>
                    @else
                        <li class="breadcrumb-item active" aria-current="page">{{ $isSearch ? 'Search' : 'Products' }}</li>
                    @endif
                </ol>
            </nav>
            <h1>{{ $heading }}</h1>
            @if (! empty($subheading))<p class="text-muted-2 mb-0">{{ $subheading }}</p>@endif
        </div>
    </section>
@endsection

@section('content')
    <div class="container py-4">
        <div class="row g-4">
            {{-- Filters --}}
            <aside class="col-lg-3">
                <button class="btn btn-outline-secondary w-100 d-lg-none mb-2" type="button" data-bs-toggle="collapse" data-bs-target="#filters" aria-expanded="false" aria-controls="filters">
                    <i class="bi bi-funnel"></i> Filters
                </button>
                <div class="collapse d-lg-block" id="filters">
                    <form action="{{ $formAction }}" method="GET" class="card filter-card">
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="filterQ" class="form-label">Keyword</label>
                                <input type="search" name="q" id="filterQ" value="{{ $filters['q'] ?? '' }}" class="form-control" placeholder="Search..." maxlength="100">
                            </div>
                            @unless ($category)
                                <div class="mb-3">
                                    <label for="filterCategory" class="form-label">Category</label>
                                    <select name="category" id="filterCategory" class="form-select">
                                        <option value="">All categories</option>
                                        @foreach ($navCategories as $navCategory)
                                            <option value="{{ $navCategory->slug }}" @selected(($filters['category'] ?? '') === $navCategory->slug)>{{ $navCategory->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endunless
                            <div class="mb-3">
                                <span class="form-label d-block">Price</span>
                                @foreach (['' => 'All', 'free' => 'Free', 'paid' => 'Paid'] as $value => $label)
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="type" id="type{{ $value ?: 'all' }}" value="{{ $value }}" @checked(($filters['type'] ?? '') === $value)>
                                        <label class="form-check-label" for="type{{ $value ?: 'all' }}">{{ $label }}</label>
                                    </div>
                                @endforeach
                            </div>
                            <div class="mb-3">
                                <span class="form-label d-block">Price range ({{ currency_code() }})</span>
                                <div class="d-flex gap-2">
                                    <input type="number" name="min_price" min="0" step="1" value="{{ $filters['min_price'] ?? '' }}" class="form-control" placeholder="Min" aria-label="Minimum price">
                                    <input type="number" name="max_price" min="0" step="1" value="{{ $filters['max_price'] ?? '' }}" class="form-control" placeholder="Max" aria-label="Maximum price">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="filterType" class="form-label">Product type</label>
                                <select name="product_type" id="filterType" class="form-select">
                                    <option value="">Any type</option>
                                    @foreach (config('marketplace.product_types') as $value => $label)
                                        <option value="{{ $value }}" @selected(($filters['product_type'] ?? '') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" name="featured" value="1" id="filterFeatured" @checked(! empty($filters['featured']))>
                                <label class="form-check-label" for="filterFeatured">Featured only</label>
                            </div>
                            @if (! empty($filters['sort']))<input type="hidden" name="sort" value="{{ $filters['sort'] }}">@endif
                            <div class="d-grid gap-2">
                                <button class="btn btn-primary" type="submit"><i class="bi bi-funnel"></i> Apply filters</button>
                                <a href="{{ $formAction }}" class="btn btn-light">Reset</a>
                            </div>
                        </div>
                    </form>
                </div>
            </aside>

            {{-- Results --}}
            <div class="col-lg-9">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <div class="text-muted-2 small">
                        {{ number_format($products->total()) }} {{ \Illuminate\Support\Str::plural('product', $products->total()) }} found
                    </div>
                    <form action="{{ $formAction }}" method="GET" class="d-flex align-items-center gap-2">
                        @foreach ($current([], ['sort']) as $key => $value)
                            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                        @endforeach
                        <label for="sort" class="small text-muted-2 text-nowrap">Sort by</label>
                        <select name="sort" id="sort" class="form-select form-select-sm" data-autosubmit>
                            @if (! empty($filters['q']))<option value="">Best match</option>@endif
                            @foreach (\App\Services\ProductSearchService::SORTS as $value => $label)
                                <option value="{{ $value }}" @selected(($filters['sort'] ?? (empty($filters['q']) ? 'latest' : '')) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <noscript><button class="btn btn-sm btn-light" type="submit">Go</button></noscript>
                    </form>
                </div>

                <div class="d-flex flex-wrap gap-2 mb-3">
                    <a href="{{ $formAction.'?'.http_build_query($current([], ['type'])) }}" class="filter-chip {{ empty($filters['type']) ? 'active' : '' }}">All</a>
                    <a href="{{ $formAction.'?'.http_build_query($current(['type' => 'free'])) }}" class="filter-chip {{ ($filters['type'] ?? '') === 'free' ? 'active' : '' }}"><i class="bi bi-gift"></i> Free</a>
                    <a href="{{ $formAction.'?'.http_build_query($current(['type' => 'paid'])) }}" class="filter-chip {{ ($filters['type'] ?? '') === 'paid' ? 'active' : '' }}"><i class="bi bi-gem"></i> Paid</a>
                    <a href="{{ $formAction.'?'.http_build_query($current(['sort' => 'popular'])) }}" class="filter-chip {{ ($filters['sort'] ?? '') === 'popular' ? 'active' : '' }}"><i class="bi bi-fire"></i> Popular</a>
                    <a href="{{ $formAction.'?'.http_build_query($current(['featured' => 1])) }}" class="filter-chip {{ ! empty($filters['featured']) ? 'active' : '' }}"><i class="bi bi-star"></i> Featured</a>
                </div>

                @if ($products->isEmpty())
                    <div class="card">
                        <x-empty-state icon="bi-search" title="No products match your filters" text="Try a different keyword or remove some filters.">
                            <a href="{{ $formAction }}" class="btn btn-primary btn-sm">Clear filters</a>
                        </x-empty-state>
                    </div>
                @else
                    <div class="row g-3">
                        @foreach ($products as $product)
                            <div class="col-6 col-md-4"><x-product-card :product="$product" /></div>
                        @endforeach
                    </div>
                    <div class="mt-4 d-flex justify-content-center">{{ $products->onEachSide(1)->links() }}</div>
                @endif
            </div>
        </div>
    </div>
@endsection
