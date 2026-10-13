@extends('layouts.admin')

@section('title', 'Review: '.$product->name)

@php $canManage = auth()->user()->can('manage_products'); $canApprove = auth()->user()->can('approve_products'); @endphp

@section('content')
    <div class="panel-title">
        <div>
            <nav aria-label="breadcrumb"><ol class="breadcrumb mb-1"><li class="breadcrumb-item"><a href="{{ route('admin.products.index') }}">Products</a></li><li class="breadcrumb-item active">#{{ $product->id }}</li></ol></nav>
            <h1>{{ $product->name }}</h1>
            <p class="d-flex gap-2 flex-wrap align-items-center mb-0">
                <x-status-badge :status="$product->approval_status" /> <x-status-badge :status="$product->status" />
                @if ($product->is_featured)<span class="badge badge-soft-warning"><i class="bi bi-star-fill"></i> Featured</span>@endif
                <span class="small text-muted-2">Submitted {{ $product->submitted_at?->diffForHumans() ?? 'never' }}</span>
            </p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('products.show', $product->slug) }}" target="_blank" class="btn btn-light"><i class="bi bi-box-arrow-up-right"></i> {{ $product->isPublished() ? 'View live' : 'Preview' }}</a>
            @if ($canManage)<a href="{{ route('admin.products.edit', $product) }}" class="btn btn-outline-primary"><i class="bi bi-pencil"></i> Edit</a>@endif
        </div>
    </div>

    {{-- Review actions --}}
    <div class="card mb-4 border-primary-subtle">
        <div class="card-body d-flex flex-wrap gap-2 align-items-center">
            <strong class="me-2">Review actions:</strong>
            @if ($canApprove && ! $product->isPublished())
                <form method="POST" action="{{ route('admin.products.approve', $product) }}" class="d-flex gap-2 align-items-center flex-wrap">
                    @csrf
                    <input type="hidden" name="publish" value="0">
                    <div class="form-check mb-0"><input class="form-check-input" type="checkbox" name="publish" value="1" id="publishNow" checked><label class="form-check-label small" for="publishNow">Publish immediately</label></div>
                    <button type="submit" class="btn btn-success" @disabled($missing)><i class="bi bi-check2-circle"></i> Approve</button>
                </form>
            @endif
            @if ($canApprove && $product->approval_status !== \App\Enums\ApprovalStatus::Rejected)
                <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#reasonModal" data-action="{{ route('admin.products.reject', $product) }}" data-title="Reject this product" data-help="The publisher will see this reason, can edit the product and resubmit."><i class="bi bi-x-octagon"></i> Reject</button>
            @endif
            @if ($canManage)
                @if ($product->approval_status === \App\Enums\ApprovalStatus::Approved && ! $product->isPublished())
                    <form method="POST" action="{{ route('admin.products.publish', $product) }}">@csrf<button type="submit" class="btn btn-primary"><i class="bi bi-globe"></i> Publish</button></form>
                @endif
                @if ($product->isPublished())
                    <form method="POST" action="{{ route('admin.products.unpublish', $product) }}" data-confirm="Unpublish this product? It will be hidden from the marketplace.">@csrf<button type="submit" class="btn btn-outline-secondary"><i class="bi bi-eye-slash"></i> Unpublish</button></form>
                @endif
                <form method="POST" action="{{ route('admin.products.feature', $product) }}">@csrf<button type="submit" class="btn btn-light"><i class="bi bi-star"></i> {{ $product->is_featured ? 'Remove featured' : 'Mark featured' }}</button></form>
                <form method="POST" action="{{ route('admin.products.destroy', $product) }}" data-confirm="Delete this product? Buyers will lose access to its downloads." class="ms-auto">@csrf @method('DELETE')<button type="submit" class="btn btn-outline-danger"><i class="bi bi-trash"></i> Delete</button></form>
            @endif
        </div>
        @if ($missing)
            <div class="card-footer small text-danger"><i class="bi bi-exclamation-triangle"></i> Cannot approve yet - missing: {{ implode(', ', $missing) }}.</div>
        @endif
        @if ($product->rejection_reason)
            <div class="card-footer small text-danger"><strong>Rejection reason:</strong> {{ $product->rejection_reason }}</div>
        @endif
    </div>

    <div class="row g-4">
        <div class="col-xl-8">
            <div class="card mb-4">
                <div class="card-header">Product details</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-5">
                            <img src="{{ $product->imageUrl() }}" alt="" class="rounded-3 w-100 ratio-4x3 object-cover border">
                            @if ($product->images->isNotEmpty())
                                <div class="gallery-grid mt-2">@foreach ($product->images as $image)<a class="item" href="{{ $image->url() }}" target="_blank"><img src="{{ $image->thumbUrl() }}" alt="" loading="lazy"></a>@endforeach</div>
                            @endif
                        </div>
                        <div class="col-md-7">
                            <x-price :product="$product" size="lg" />
                            <p class="mt-2">{{ $product->short_description }}</p>
                            <ul class="spec-list">
                                <li><span>Category</span><span>{{ $product->category?->name }} @if ($product->category && ! $product->category->is_active)<span class="badge badge-soft-danger">inactive</span>@endif</span></li>
                                <li><span>Type</span><span>{{ $product->typeLabel() }}</span></li>
                                <li><span>Regular price</span><span>{{ $product->is_free ? 'Free' : money($product->price) }}</span></li>
                                @if ($product->hasDiscount())<li><span>Sale price</span><span>{{ money($product->sale_price) }} (-{{ $product->discountPercent() }}%)</span></li>@endif
                                <li><span>Version</span><span>{{ $product->version ?: '-' }}</span></li>
                                <li><span>Demo</span><span>@if ($product->demo_url)<a href="{{ $product->demo_url }}" target="_blank" rel="noopener nofollow">{{ \Illuminate\Support\Str::limit($product->demo_url, 40) }}</a>@else - @endif</span></li>
                                <li><span>Docs</span><span>@if ($product->documentation_url)<a href="{{ $product->documentation_url }}" target="_blank" rel="noopener nofollow">{{ \Illuminate\Support\Str::limit($product->documentation_url, 40) }}</a>@else - @endif</span></li>
                                <li><span>WhatsApp ordering</span><span>{{ $product->whatsapp_enabled ? 'Enabled' : 'Disabled' }}</span></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">Download file</div>
                <div class="card-body">
                    @if ($product->hasFile())
                        <div class="d-flex align-items-center gap-3 flex-wrap">
                            <i class="bi bi-file-earmark-zip fs-2 text-primary"></i>
                            <div class="flex-grow-1 min-w-0">
                                <div class="fw-semibold text-truncate">{{ $product->file_name }}</div>
                                <div class="small text-muted-2">{{ $product->fileSizeHuman() }} &middot; {{ strtoupper($product->file_type) }} &middot; {{ $product->file_mime }}</div>
                                @unless ($fileExists)<div class="small text-danger"><i class="bi bi-exclamation-triangle"></i> File is missing from storage.</div>@endunless
                            </div>
                            @if ($fileExists)<a href="{{ route('admin.products.file', $product) }}" class="btn btn-outline-primary"><i class="bi bi-download"></i> Download for review</a>@endif
                        </div>
                    @else
                        <span class="text-danger">No file uploaded.</span>
                    @endif
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">Description</div>
                <div class="card-body prose">{!! markdown($product->description) ?: '<p class="text-muted-2">No description.</p>' !!}</div>
                @if ($product->featuresList() || $product->requirementsList())
                    <div class="card-body border-top row">
                        <div class="col-md-6"><h3 class="h6">Features</h3><ul class="check-list small">@foreach ($product->featuresList() as $f)<li>{{ $f }}</li>@endforeach</ul></div>
                        <div class="col-md-6"><h3 class="h6">Requirements</h3><ul class="check-list small">@foreach ($product->requirementsList() as $r)<li>{{ $r }}</li>@endforeach</ul></div>
                    </div>
                @endif
            </div>

            <div class="card">
                <div class="card-header">SEO data</div>
                <div class="card-body">
                    <ul class="spec-list">
                        <li><span>URL</span><span>/product/{{ $product->slug }}</span></li>
                        <li><span>SEO title</span><span>{{ $product->seo_title ?: '(uses name)' }}</span></li>
                        <li><span>SEO description</span><span>{{ $product->seo_description ?: '(uses short description)' }}</span></li>
                        <li><span>SEO keywords</span><span>{{ $product->seo_keywords ?: '-' }}</span></li>
                        <li><span>Focus keyword</span><span>{{ $product->focus_keyword ?: '-' }}</span></li>
                        <li><span>Canonical</span><span>{{ $product->canonical_url ?: '(self)' }}</span></li>
                        <li><span>OG title</span><span>{{ $product->og_title ?: '-' }}</span></li>
                        <li><span>OG description</span><span>{{ $product->og_description ?: '-' }}</span></li>
                    </ul>
                    <div class="mt-2"><span class="small text-muted-2 d-block mb-1">Keywords</span>@forelse ($product->keywords as $keyword)<span class="keyword-chip">{{ $keyword->name }}</span>@empty <span class="small">-</span>@endforelse</div>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card mb-4">
                <div class="card-header">Publisher</div>
                <div class="card-body">
                    @php $owner = $product->user; @endphp
                    <div class="d-flex gap-2 align-items-center mb-2">
                        <span class="avatar">{{ $owner?->initials() }}</span>
                        <div class="min-w-0">
                            <div class="fw-semibold text-ink">{{ $owner?->publicName() }}</div>
                            <div class="small text-muted-2 text-truncate">{{ $owner?->email }}</div>
                        </div>
                    </div>
                    @if ($owner?->publisherProfile)
                        <div class="small">Publisher status: <x-status-badge :status="$owner->publisherProfile->status" /></div>
                    @endif
                    @if ($owner?->isPublisher() && auth()->user()->can('manage_publishers'))
                        <a href="{{ route('admin.publishers.show', $owner) }}" class="btn btn-sm btn-light mt-2">View publisher</a>
                    @endif
                </div>
            </div>
            <div class="card mb-4">
                <div class="card-header">Statistics</div>
                <div class="card-body">
                    <ul class="spec-list">
                        <li><span>Views</span><span>{{ number_format($product->view_count) }}</span></li>
                        <li><span>Downloads</span><span>{{ number_format($product->download_count) }}</span></li>
                        <li><span>Sales</span><span>{{ number_format($product->sales_count) }}</span></li>
                        <li><span>Created</span><span>{{ $product->created_at->format('M j, Y H:i') }}</span></li>
                        <li><span>Updated</span><span>{{ $product->updated_at->format('M j, Y H:i') }}</span></li>
                        @if ($product->approved_at)<li><span>Approved</span><span>{{ $product->approved_at->format('M j, Y') }} by {{ $product->approver?->name ?? '-' }}</span></li>@endif
                        @if ($product->published_at)<li><span>Published</span><span>{{ $product->published_at->format('M j, Y') }}</span></li>@endif
                    </ul>
                </div>
            </div>
            <div class="card">
                <div class="card-header">Status history</div>
                <div class="card-body">@include('products._history')</div>
            </div>
        </div>
    </div>
@endsection
