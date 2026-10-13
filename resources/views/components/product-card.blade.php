@props(['product'])
<article class="product-card">
    <a href="{{ $product->url() }}" class="media" tabindex="-1" aria-hidden="true">
        <img src="{{ $product->thumbnailUrl() }}" alt="{{ $product->name }}" loading="lazy" width="600" height="450">
    </a>
    <div class="flags">
        @if ($product->is_free)<span class="flag flag-free">Free</span>@endif
        @if ($product->hasDiscount())<span class="flag flag-sale">-{{ $product->discountPercent() }}%</span>@endif
        @if ($product->is_featured)<span class="flag flag-featured"><i class="bi bi-star-fill"></i></span>@endif
    </div>
    <div class="body">
        <div class="meta">
            <span><i class="bi bi-folder2"></i> {{ $product->category?->name }}</span>
            <span><i class="bi bi-download"></i> {{ number_format($product->download_count) }}</span>
        </div>
        <h3 class="title"><a href="{{ $product->url() }}">{{ $product->name }}</a></h3>
        <div class="meta">by {{ $product->publisherName() }}</div>
        <div class="footer">
            <x-price :product="$product" />
            @if ($product->is_free)
                <a href="{{ route('downloads.download', $product->slug) }}" class="btn btn-sm btn-soft" rel="nofollow"><i class="bi bi-download"></i> Get</a>
            @else
                <form method="POST" action="{{ route('cart.add', $product) }}" data-ajax-cart>
                    @csrf
                    <button type="submit" class="btn btn-sm btn-soft" aria-label="Add {{ $product->name }} to cart"><i class="bi bi-cart-plus"></i> Add</button>
                </form>
            @endif
        </div>
    </div>
</article>
