@props(['product', 'size' => null])
@if ($product->is_free)
    <span {{ $attributes->class(['price free', 'price-lg' => $size === 'lg']) }}><span class="final">Free</span></span>
@else
    <span {{ $attributes->class(['price', 'price-lg' => $size === 'lg']) }}>
        <span class="final">{{ money($product->finalPrice()) }}</span>
        @if ($product->hasDiscount())
            <span class="original" aria-label="Original price">{{ money($product->price) }}</span>
            <span class="off">-{{ $product->discountPercent() }}%</span>
        @endif
    </span>
@endif
