@extends('account.layout')

@section('account')
    <div class="panel-title"><div><h1>Purchased products</h1><p>Everything you own on {{ setting('site_name') }}.</p></div></div>

    @if ($permissions->isEmpty())
        <div class="card">
            <x-empty-state icon="bi-bag-check" title="You haven't bought anything yet" text="Purchased products appear here once your payment is verified.">
                <a href="{{ route('products.index') }}" class="btn btn-primary btn-sm">Browse products</a>
            </x-empty-state>
        </div>
    @else
        <div class="row g-3">
            @foreach ($permissions as $permission)
                @php $product = $permission->product; $active = $product && ! $product->trashed() && $permission->isUsable() && $permission->order?->grantsDownloads(); @endphp
                @continue(! $product)
                <div class="col-sm-6 col-xl-4">
                    <div class="product-card">
                        <span class="media"><img src="{{ $product->thumbnailUrl() }}" alt="{{ $product->name }}" loading="lazy"></span>
                        <div class="body">
                            <div class="meta"><span><i class="bi bi-folder2"></i> {{ $product->category?->name }}</span>@if ($product->version)<span>v{{ $product->version }}</span>@endif</div>
                            <h2 class="title h6">{{ $product->name }}</h2>
                            <div class="meta">Order {{ $permission->order?->order_number }}</div>
                            <div class="footer">
                                @if (! $product->trashed())<a href="{{ $product->url() }}" class="btn btn-sm btn-light position-relative" style="z-index: 2">View</a>@endif
                                @if ($active)
                                    <a href="{{ route('downloads.download', $product->slug) }}" class="btn btn-sm btn-success position-relative" style="z-index: 2" rel="nofollow"><i class="bi bi-download"></i> Download</a>
                                @else
                                    <span class="badge badge-soft-secondary">Access unavailable</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
@endsection
