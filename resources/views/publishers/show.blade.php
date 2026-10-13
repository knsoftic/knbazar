@extends('layouts.app')

@php $profile = $publisher->publisherProfile; @endphp

@section('page-header')
    <section class="page-header">
        <div class="container">
            <nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li><li class="breadcrumb-item active" aria-current="page">Publishers</li></ol></nav>
            <div class="d-flex gap-3 align-items-center flex-wrap">
                @if ($profile?->logoUrl())
                    <img src="{{ $profile->logoUrl() }}" alt="{{ $publisher->publicName() }}" class="avatar avatar-lg">
                @else
                    <span class="avatar avatar-lg">{{ $publisher->initials() }}</span>
                @endif
                <div class="min-w-0">
                    <h1 class="mb-1">{{ $publisher->publicName() }}</h1>
                    <div class="text-muted-2 small d-flex flex-wrap gap-3">
                        <span><i class="bi bi-person"></i> {{ '@'.$publisher->username }}</span>
                        @if ($profile?->country)<span><i class="bi bi-geo-alt"></i> {{ $profile->country }}</span>@endif
                        <span><i class="bi bi-box-seam"></i> {{ trans_choice(':count product|:count products', $products->total()) }}</span>
                        <span><i class="bi bi-calendar3"></i> Member since {{ $publisher->created_at->format('M Y') }}</span>
                        @if ($profile?->website)<a href="{{ $profile->website }}" target="_blank" rel="noopener nofollow"><i class="bi bi-globe"></i> Website</a>@endif
                    </div>
                </div>
            </div>
            @if ($profile?->bio)<p class="mt-3 mb-0" style="max-width: 760px">{{ $profile->bio }}</p>@endif
        </div>
    </section>
@endsection

@section('content')
    <div class="container py-4">
        @if ($products->isEmpty())
            <div class="card"><x-empty-state icon="bi-box-seam" title="No products published yet" /></div>
        @else
            <div class="row g-3">
                @foreach ($products as $product)
                    <div class="col-6 col-md-4 col-lg-3"><x-product-card :product="$product" /></div>
                @endforeach
            </div>
            <div class="mt-4 d-flex justify-content-center">{{ $products->onEachSide(1)->links() }}</div>
        @endif
    </div>
@endsection
