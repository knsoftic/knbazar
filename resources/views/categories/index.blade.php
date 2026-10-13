@extends('layouts.app')

@section('page-header')
    <section class="page-header">
        <div class="container">
            <nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li><li class="breadcrumb-item active" aria-current="page">Categories</li></ol></nav>
            <h1>Categories</h1>
            <p class="text-muted-2 mb-0">Browse every product category on {{ setting('site_name') }}.</p>
        </div>
    </section>
@endsection

@section('content')
    <div class="container py-4">
        @if ($categories->isEmpty())
            <div class="card"><x-empty-state icon="bi-folder2" title="No categories yet" /></div>
        @else
            <div class="row g-3">
                @foreach ($categories as $category)
                    <div class="col-sm-6 col-lg-4">
                        <a href="{{ $category->url() }}" class="card h-100 text-decoration-none overflow-hidden">
                            @if ($category->imageUrl())
                                <img src="{{ $category->imageUrl() }}" alt="{{ $category->name }}" class="card-img-top object-cover" style="aspect-ratio: 16/9" loading="lazy">
                            @endif
                            <div class="card-body d-flex gap-3 align-items-start">
                                <span class="category-tile p-0 border-0 shadow-none"><span class="icon"><i class="bi {{ $category->icon ?: 'bi-folder2' }}"></i></span></span>
                                <div class="min-w-0">
                                    <h2 class="h6 mb-1 text-ink">{{ $category->name }}</h2>
                                    <p class="small text-muted-2 mb-1">{{ \Illuminate\Support\Str::limit($category->description, 110) }}</p>
                                    <span class="badge badge-soft-primary">{{ trans_choice(':count product|:count products', $category->products_count) }}</span>
                                </div>
                            </div>
                        </a>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
@endsection
