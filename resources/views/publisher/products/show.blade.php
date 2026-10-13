@extends('layouts.publisher')

@section('title', $product->name)

@section('content')
    <div class="panel-title">
        <div>
            <nav aria-label="breadcrumb"><ol class="breadcrumb mb-1"><li class="breadcrumb-item"><a href="{{ route('publisher.products.index') }}">My products</a></li><li class="breadcrumb-item active text-truncate" style="max-width: 50vw">{{ $product->name }}</li></ol></nav>
            <h1>{{ $product->name }}</h1>
            <p class="d-flex gap-2 align-items-center flex-wrap mb-0"><x-status-badge :status="$product->status" /> <x-status-badge :status="$product->approval_status" /> @if ($product->is_featured)<span class="badge badge-soft-warning"><i class="bi bi-star-fill"></i> Featured</span>@endif</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('products.show', $product->slug) }}" class="btn btn-light" target="_blank"><i class="bi bi-box-arrow-up-right"></i> {{ $product->isPublished() ? 'View live' : 'Preview' }}</a>
            @can('update', $product)<a href="{{ route('publisher.products.edit', $product) }}" class="btn btn-outline-primary"><i class="bi bi-pencil"></i> Edit</a>@endcan
            @can('submit', $product)
                <form method="POST" action="{{ route('publisher.products.submit', $product) }}">
                    @csrf
                    <button type="submit" class="btn btn-primary" data-loading="Submitting..."><i class="bi bi-send"></i> {{ $product->isRejected() ? 'Resubmit for review' : 'Submit for review' }}</button>
                </form>
            @endcan
        </div>
    </div>

    @if ($product->isRejected() && $product->rejection_reason)
        <div class="alert alert-danger"><strong><i class="bi bi-x-octagon"></i> Rejection reason:</strong> {{ $product->rejection_reason }}<div class="small mt-1">Edit the product to address this, then resubmit it.</div></div>
    @elseif ($product->isPendingReview())
        <div class="alert alert-info"><i class="bi bi-hourglass-split"></i> This product is waiting for review. You'll be notified once it has been reviewed.</div>
    @elseif ($product->isDraft())
        <div class="alert alert-secondary"><i class="bi bi-pencil-square"></i> This is a draft and is not visible to customers. Submit it for review when it's ready.</div>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3"><x-stat-card label="Views" :value="number_format($product->view_count)" icon="bi-eye" /></div>
        <div class="col-6 col-lg-3"><x-stat-card label="Downloads" :value="number_format($product->download_count)" icon="bi-download" color="info" /></div>
        <div class="col-6 col-lg-3"><x-stat-card label="Sales" :value="number_format($product->sales_count)" icon="bi-bag-check" color="success" /></div>
        <div class="col-6 col-lg-3"><x-stat-card label="Revenue" :value="money($sales)" icon="bi-cash-coin" color="warning" /></div>
    </div>

    <div class="row g-4">
        <div class="col-xl-8">
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-5"><img src="{{ $product->imageUrl() }}" alt="" class="rounded-3 w-100 ratio-4x3 object-cover border"></div>
                        <div class="col-md-7">
                            <x-price :product="$product" size="lg" />
                            <p class="mt-2">{{ $product->short_description }}</p>
                            <ul class="spec-list">
                                <li><span>Category</span><span>{{ $product->category?->name }}</span></li>
                                <li><span>Type</span><span>{{ $product->typeLabel() }}</span></li>
                                <li><span>Version</span><span>{{ $product->version ?: '-' }}</span></li>
                                <li><span>File</span><span>@if ($product->hasFile()){{ $product->file_name }} ({{ $product->fileSizeHuman() }}) <a href="{{ route('publisher.products.file', $product) }}"><i class="bi bi-download"></i></a>@else<span class="text-danger">Missing</span>@endif</span></li>
                                <li><span>URL</span><span class="text-break">/product/{{ $product->slug }}</span></li>
                            </ul>
                        </div>
                    </div>
                    @if ($product->images->isNotEmpty())
                        <div class="gallery-grid mt-3">@foreach ($product->images as $image)<div class="item"><img src="{{ $image->thumbUrl() }}" alt="" loading="lazy"></div>@endforeach</div>
                    @endif
                </div>
            </div>
            <div class="card">
                <div class="card-header">Description</div>
                <div class="card-body prose">{!! markdown($product->description) ?: '<p class="text-muted-2">No description.</p>' !!}</div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card mb-4">
                <div class="card-header">SEO</div>
                <div class="card-body small">
                    <div class="mb-2"><span class="text-muted-2 d-block">Title</span>{{ $product->seo_title ?: $product->name }}</div>
                    <div class="mb-2"><span class="text-muted-2 d-block">Description</span>{{ $product->seo_description ?: $product->short_description ?: '-' }}</div>
                    <div><span class="text-muted-2 d-block">Keywords</span>
                        @forelse ($product->keywords as $keyword)<span class="keyword-chip">{{ $keyword->name }}</span>@empty - @endforelse
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-header">Review history</div>
                <div class="card-body">@include('products._history')</div>
            </div>
        </div>
    </div>
@endsection
