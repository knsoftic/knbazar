@extends('layouts.app')

@section('content')
    {{-- Hero --}}
    <section class="hero">
        <div class="container position-relative">
            <div class="row align-items-center g-4">
                <div class="col-lg-7">
                    <span class="badge rounded-pill text-bg-light mb-3"><i class="bi bi-shield-check text-success"></i> Every product reviewed by our team</span>
                    <h1 class="mb-3">{{ setting('home_hero_title') }}</h1>
                    <p class="lead mb-4">{{ setting('home_hero_subtitle') }}</p>
                    <form action="{{ route('search') }}" method="GET" class="hero-search" role="search">
                        <label for="heroSearch" class="visually-hidden">Search products</label>
                        <input type="search" id="heroSearch" name="q" class="form-control" placeholder="What are you looking for?" maxlength="100">
                        <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i><span class="d-none d-sm-inline ms-1">Search</span></button>
                    </form>
                    @if ($categories->isNotEmpty())
                        <div class="hero-tags mt-3">
                            @foreach ($categories->take(6) as $tag)
                                <a href="{{ $tag->url() }}">{{ $tag->name }}</a>
                            @endforeach
                        </div>
                    @endif
                    <div class="hero-stats">
                        <div><strong>{{ number_format($stats['products']) }}+</strong><span>Digital products</span></div>
                        <div><strong>{{ number_format($stats['downloads']) }}+</strong><span>Downloads</span></div>
                        <div><strong><i class="bi bi-lightning-charge-fill text-warning"></i></strong><span>Instant access</span></div>
                    </div>
                </div>
                @if ($banners->isNotEmpty())
                    <div class="col-lg-5">
                        <div id="bannerCarousel" class="carousel slide" data-bs-ride="carousel">
                            <div class="carousel-inner">
                                @foreach ($banners as $banner)
                                    <div class="carousel-item {{ $loop->first ? 'active' : '' }}">
                                        <div class="banner-slide">
                                            @if ($banner->imageUrl())<img src="{{ $banner->imageUrl() }}" alt="" loading="lazy">@endif
                                            <div class="banner-content d-flex flex-column justify-content-center">
                                                <h2 class="h4">{{ $banner->title }}</h2>
                                                @if ($banner->subtitle)<p class="mb-3">{{ $banner->subtitle }}</p>@endif
                                                @if ($banner->button_text && $banner->button_url)
                                                    <div><a href="{{ $banner->button_url }}" class="btn btn-light">{{ $banner->button_text }} <i class="bi bi-arrow-right"></i></a></div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            @if ($banners->count() > 1)
                                <button class="carousel-control-prev" type="button" data-bs-target="#bannerCarousel" data-bs-slide="prev"><span class="carousel-control-prev-icon" aria-hidden="true"></span><span class="visually-hidden">Previous</span></button>
                                <button class="carousel-control-next" type="button" data-bs-target="#bannerCarousel" data-bs-slide="next"><span class="carousel-control-next-icon" aria-hidden="true"></span><span class="visually-hidden">Next</span></button>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </section>

    {{-- Categories --}}
    @if ($categories->isNotEmpty())
        <section class="section">
            <div class="container">
                <div class="section-head">
                    <div><h2>Browse categories</h2><p>Find exactly what your next project needs.</p></div>
                    <a href="{{ route('categories.index') }}" class="btn btn-outline-primary btn-sm">All categories</a>
                </div>
                <div class="row g-3">
                    @foreach ($categories as $category)
                        <div class="col-6 col-md-4 col-lg-3">
                            <a href="{{ $category->url() }}" class="category-tile">
                                <span class="icon"><i class="bi {{ $category->icon ?: 'bi-folder2' }}"></i></span>
                                <span class="min-w-0">
                                    <span class="name text-truncate">{{ $category->name }}</span>
                                    <small>{{ trans_choice(':count product|:count products', $category->products_count) }}</small>
                                </span>
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @php
        $sections = [
            ['featured', 'Featured products', 'Hand-picked by our team.', route('products.index', ['featured' => 1])],
            ['latest', 'Latest products', 'Fresh from our publishers.', route('products.index', ['sort' => 'latest'])],
            ['popular', 'Popular products', 'What customers love right now.', route('products.index', ['sort' => 'popular'])],
            ['paid', 'Premium products', 'Professional quality, ready to use.', route('products.index', ['type' => 'paid'])],
            ['free', 'Free products', 'Download instantly at no cost.', route('products.index', ['type' => 'free'])],
        ];
    @endphp

    @foreach ($sections as [$key, $title, $subtitle, $link])
        @if ($$key->isNotEmpty())
            <section class="section {{ $loop->even ? 'bg-white border-top border-bottom' : '' }}">
                <div class="container">
                    <div class="section-head">
                        <div><h2>{{ $title }}</h2><p>{{ $subtitle }}</p></div>
                        <a href="{{ $link }}" class="btn btn-outline-primary btn-sm">View all <i class="bi bi-arrow-right"></i></a>
                    </div>
                    <div class="row g-3 g-lg-4">
                        @foreach ($$key as $product)
                            <div class="col-6 col-md-4 col-xl-3"><x-product-card :product="$product" /></div>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif
    @endforeach

    @if ($featured->isEmpty() && $latest->isEmpty() && $free->isEmpty() && $paid->isEmpty())
        <section class="section">
            <div class="container">
                <x-empty-state icon="bi-box-seam" title="No products yet" text="Products will appear here once publishers' submissions are approved." />
            </div>
        </section>
    @endif

    {{-- Publisher CTA --}}
    @if (setting('home_show_publisher_cta') && setting('publisher_registration_enabled'))
        <section class="section">
            <div class="container">
                <div class="cta-publisher">
                    <div class="row align-items-center g-3 position-relative" style="z-index: 1">
                        <div class="col-lg-8">
                            <h2 class="h3 mb-2">{{ setting('home_publisher_cta_title') }}</h2>
                            <p class="mb-0">{{ setting('home_publisher_cta_text') }}</p>
                        </div>
                        <div class="col-lg-4 text-lg-end">
                            <a href="{{ route('register.publisher') }}" class="btn btn-light btn-lg">Become a publisher <i class="bi bi-arrow-right"></i></a>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    @endif
@endsection
