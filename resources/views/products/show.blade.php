@extends('layouts.app')

@php
    $images = collect([['full' => $product->imageUrl(), 'thumb' => $product->thumbnailUrl(), 'alt' => $product->name]])
        ->merge($product->images->map(fn ($img) => ['full' => $img->url(), 'thumb' => $img->thumbUrl(), 'alt' => $img->alt ?: $product->name]));
    $features = $product->featuresList();
    $requirements = $product->requirementsList();
    $freeEnabled = (bool) setting('free_downloads_enabled');
@endphp

@section('content')
    @if ($isPreview)
        <div class="preview-banner py-2">
            <div class="container small"><i class="bi bi-eye"></i> Preview - this product is <strong>{{ $product->status->label() }}</strong> and is not visible to the public.</div>
        </div>
    @endif

    <div class="container py-4">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                @if ($product->category)
                    <li class="breadcrumb-item"><a href="{{ $product->category->url() }}">{{ $product->category->name }}</a></li>
                @endif
                <li class="breadcrumb-item active text-truncate" aria-current="page" style="max-width: 60vw">{{ $product->name }}</li>
            </ol>
        </nav>

        {{-- Grid: gallery / buy box / details (mobile order) - buy box sticks to the right on desktop --}}
        <div class="product-layout">
            <div class="pl-gallery min-w-0">
                <div class="gallery-main">
                    <img id="galleryMain" src="{{ $images->first()['full'] }}" alt="{{ $product->name }}" width="1200" height="900">
                </div>
                @if ($images->count() > 1)
                    <div class="gallery-thumbs" role="list">
                        @foreach ($images as $image)
                            <button type="button" class="{{ $loop->first ? 'active' : '' }}" data-full="{{ $image['full'] }}" data-alt="{{ $image['alt'] }}" aria-label="Show image {{ $loop->iteration }}" role="listitem">
                                <img src="{{ $image['thumb'] }}" alt="" loading="lazy">
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="pl-details min-w-0">
                <div class="card">
                    <div class="card-body">
                        <ul class="nav nav-underline mb-3" role="tablist">
                            <li class="nav-item" role="presentation"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-description" type="button" role="tab">Description</button></li>
                            @if ($features)<li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-features" type="button" role="tab">Features</button></li>@endif
                            @if ($requirements)<li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-requirements" type="button" role="tab">Requirements</button></li>@endif
                        </ul>
                        <div class="tab-content">
                            <div class="tab-pane fade show active prose" id="tab-description" role="tabpanel">
                                {!! markdown($product->description) ?: '<p>'.e($product->short_description).'</p>' !!}
                            </div>
                            @if ($features)
                                <div class="tab-pane fade" id="tab-features" role="tabpanel">
                                    <ul class="check-list">@foreach ($features as $feature)<li>{{ $feature }}</li>@endforeach</ul>
                                </div>
                            @endif
                            @if ($requirements)
                                <div class="tab-pane fade" id="tab-requirements" role="tabpanel">
                                    <ul class="check-list">@foreach ($requirements as $requirement)<li>{{ $requirement }}</li>@endforeach</ul>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                @if ($product->keywords->isNotEmpty())
                    <div class="mt-4">
                        <h2 class="h6 mb-2">Tags</h2>
                        @foreach ($product->keywords as $keyword)
                            <a href="{{ $keyword->searchUrl() }}" class="keyword-chip" rel="tag">{{ $keyword->name }}</a>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Buy box --}}
            <div class="pl-buy">
                <div class="buy-box">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex gap-2 mb-2 flex-wrap">
                                @if ($product->is_free)<span class="badge badge-soft-success">Free</span>@else<span class="badge badge-soft-primary">Premium</span>@endif
                                <span class="badge badge-soft-secondary">{{ $product->typeLabel() }}</span>
                                @if ($product->is_featured)<span class="badge badge-soft-warning"><i class="bi bi-star-fill"></i> Featured</span>@endif
                            </div>
                            <h1 class="h3 mb-2">{{ $product->name }}</h1>
                            @if ($product->short_description)<p class="text-muted-2">{{ $product->short_description }}</p>@endif

                            <div class="mb-3">
                                <x-price :product="$product" size="lg" />
                                @if ($product->hasDiscount())
                                    <div class="small text-success fw-semibold">You save {{ money($product->discountAmount()) }} ({{ $product->discountPercent() }}% off)</div>
                                @endif
                            </div>

                            <div class="d-grid gap-2">
                                @if ($product->is_free)
                                    @if ($freeEnabled)
                                        <a href="{{ route('downloads.download', $product->slug) }}" class="btn btn-primary btn-lg" rel="nofollow"><i class="bi bi-download"></i> Download free</a>
                                        @if (setting('free_download_require_login') && ! auth()->check())
                                            <div class="small text-muted-2 text-center">You will be asked to sign in first.</div>
                                        @endif
                                    @else
                                        <button class="btn btn-secondary btn-lg" type="button" disabled>Free downloads are temporarily disabled</button>
                                    @endif
                                @elseif ($permission)
                                    <a href="{{ route('downloads.download', $product->slug) }}" class="btn btn-success btn-lg" rel="nofollow"><i class="bi bi-download"></i> Download (purchased)</a>
                                    <div class="small text-muted-2 text-center">
                                        @if ($permission->remainingDownloads() !== null){{ $permission->remainingDownloads() }} downloads left. @endif
                                        @if ($permission->expires_at)Access until {{ $permission->expires_at->format('M j, Y') }}.@endif
                                    </div>
                                @elseif (! $isPreview)
                                    <form method="POST" action="{{ route('cart.buy-now', $product) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-primary btn-lg w-100"><i class="bi bi-lightning-charge"></i> Buy now</button>
                                    </form>
                                    @if ($inCart)
                                        <a href="{{ route('cart.index') }}" class="btn btn-outline-primary"><i class="bi bi-bag-check"></i> In your cart - view cart</a>
                                    @else
                                        <form method="POST" action="{{ route('cart.add', $product) }}" data-ajax-cart>
                                            @csrf
                                            <button type="submit" class="btn btn-outline-primary w-100"><i class="bi bi-cart-plus"></i> Add to cart</button>
                                        </form>
                                    @endif
                                    @if ($whatsappUrl)
                                        <a href="{{ $whatsappUrl }}" class="btn btn-whatsapp" target="_blank" rel="noopener nofollow"><i class="bi bi-whatsapp"></i> {{ setting('whatsapp_button_text') }}</a>
                                    @endif
                                @endif
                            </div>

                            @if ($product->demo_url || $product->documentation_url)
                                <div class="d-flex gap-2 mt-3">
                                    @if ($product->demo_url)<a href="{{ $product->demo_url }}" class="btn btn-light flex-fill" target="_blank" rel="noopener nofollow"><i class="bi bi-display"></i> Live demo</a>@endif
                                    @if ($product->documentation_url)<a href="{{ $product->documentation_url }}" class="btn btn-light flex-fill" target="_blank" rel="noopener nofollow"><i class="bi bi-journal-text"></i> Docs</a>@endif
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="card mt-3">
                        <div class="card-body">
                            <ul class="spec-list">
                                <li><span>Category</span><span>@if ($product->category)<a href="{{ $product->category->url() }}">{{ $product->category->name }}</a>@endif</span></li>
                                <li><span>Publisher</span><span>
                                    @if ($product->user?->isPublisher())
                                        <a href="{{ route('publishers.show', $product->user->username) }}">{{ $product->publisherName() }}</a>
                                    @else
                                        {{ setting('site_name') }}
                                    @endif
                                </span></li>
                                <li><span>Downloads</span><span>{{ number_format($product->download_count) }}</span></li>
                                @if ($product->version)<li><span>Version</span><span>{{ $product->version }}</span></li>@endif
                                @if ($product->file_type)<li><span>File type</span><span class="text-uppercase">{{ $product->file_type }}</span></li>@endif
                                @if ($product->fileSizeHuman())<li><span>File size</span><span>{{ $product->fileSizeHuman() }}</span></li>@endif
                                <li><span>Product ID</span><span>#{{ $product->id }}</span></li>
                                <li><span>Released</span><span>{{ ($product->published_at ?? $product->created_at)->format('M j, Y') }}</span></li>
                                <li><span>Last updated</span><span>{{ $product->updated_at->format('M j, Y') }}</span></li>
                            </ul>
                        </div>
                    </div>

                    <div class="card mt-3">
                        <div class="card-body small text-muted-2">
                            <i class="bi bi-star me-1"></i> Ratings &amp; reviews are coming soon.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if ($related->isNotEmpty())
            <section class="mt-5">
                <div class="section-head"><h2 class="h4">Related products</h2></div>
                <div class="row g-3">
                    @foreach ($related as $item)
                        <div class="col-6 col-md-4 col-lg-3"><x-product-card :product="$item" /></div>
                    @endforeach
                </div>
            </section>
        @endif
    </div>
@endsection
