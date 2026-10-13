@extends('layouts.admin')

@section('title', 'Products')

@php $canManage = auth()->user()->can('manage_products'); $canApprove = auth()->user()->can('approve_products'); @endphp

@section('content')
    <div class="panel-title">
        <div><h1>Products</h1><p>Review submissions and manage the catalog.</p></div>
        @if ($canManage)
            <div class="d-flex gap-2">
                <a href="{{ route('admin.products.create', ['type' => 'free']) }}" class="btn btn-outline-primary"><i class="bi bi-gift"></i> Add free product</a>
                <a href="{{ route('admin.products.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Add product</a>
            </div>
        @endif
    </div>

    <form method="GET" class="card mb-3">
        <div class="card-body row g-2 align-items-end">
            <div class="col-md-3"><label class="form-label small" for="q">Search</label><input type="search" name="q" id="q" value="{{ $filters['q'] ?? '' }}" class="form-control" placeholder="Name, slug or ID"></div>
            <div class="col-6 col-md-2"><label class="form-label small" for="approval">Approval</label>
                <select name="approval" id="approval" class="form-select"><option value="">Any</option>@foreach (\App\Enums\ApprovalStatus::options() as $v => $l)<option value="{{ $v }}" @selected(($filters['approval'] ?? '') === $v)>{{ $l }}</option>@endforeach</select></div>
            <div class="col-6 col-md-2"><label class="form-label small" for="status">Status</label>
                <select name="status" id="status" class="form-select"><option value="">Any</option>@foreach (\App\Enums\ProductStatus::options() as $v => $l)<option value="{{ $v }}" @selected(($filters['status'] ?? '') === $v)>{{ $l }}</option>@endforeach</select></div>
            <div class="col-6 col-md-2"><label class="form-label small" for="type">Type</label>
                <select name="type" id="type" class="form-select"><option value="">Free &amp; paid</option><option value="free" @selected(($filters['type'] ?? '') === 'free')>Free</option><option value="paid" @selected(($filters['type'] ?? '') === 'paid')>Paid</option></select></div>
            <div class="col-6 col-md-2"><label class="form-label small" for="category">Category</label>
                <select name="category" id="category" class="form-select"><option value="">All</option>@foreach ($categories as $c)<option value="{{ $c->id }}" @selected((string) ($filters['category'] ?? '') === (string) $c->id)>{{ $c->name }}</option>@endforeach</select></div>
            <div class="col-md-1 d-grid"><button class="btn btn-primary" type="submit" aria-label="Filter"><i class="bi bi-funnel"></i></button></div>
            <div class="col-12 d-flex gap-3 small">
                <div class="form-check"><input class="form-check-input" type="checkbox" name="featured" value="1" id="featured" @checked(! empty($filters['featured']))><label class="form-check-label" for="featured">Featured only</label></div>
                @if (array_filter($filters))<a href="{{ route('admin.products.index') }}">Reset filters</a>@endif
            </div>
        </div>
    </form>

    <form method="POST" action="{{ route('admin.products.bulk') }}" id="bulkForm" data-confirm="Apply this bulk action to the selected products?" data-confirm-class="btn-primary">
        @csrf
        <div class="card">
            <div class="card-header d-flex flex-wrap gap-2 align-items-center d-none" data-bulk-bar>
                <span class="small"><strong data-bulk-count>0</strong> selected</span>
                <select name="action" class="form-select form-select-sm w-auto" required aria-label="Bulk action">
                    <option value="">Bulk action...</option>
                    @if ($canApprove)<option value="approve">Approve &amp; publish</option>@endif
                    @if ($canManage)
                        <option value="publish">Publish</option>
                        <option value="unpublish">Unpublish</option>
                        <option value="feature">Mark featured</option>
                        <option value="unfeature">Remove featured</option>
                        <option value="delete">Delete</option>
                    @endif
                </select>
                <button type="submit" class="btn btn-sm btn-primary">Apply</button>
            </div>
            @if ($products->isEmpty())
                <x-empty-state icon="bi-box-seam" title="No products found" />
            @else
                <div class="table-responsive-md">
                    <table class="table table-hover table-stack">
                        <thead>
                            <tr>
                                <th><input type="checkbox" class="form-check-input" data-check-all aria-label="Select all"></th>
                                <th>ID</th><th>Image</th><th>Product</th><th>Publisher</th><th>Category</th><th>Price</th><th>Type</th><th>Approval</th><th>Status</th><th>Downloads</th><th>Date</th><th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($products as $product)
                                <tr>
                                    <td><input type="checkbox" class="form-check-input" name="ids[]" value="{{ $product->id }}" data-check-item aria-label="Select {{ $product->name }}"></td>
                                    <td class="text-muted-2">#{{ $product->id }}</td>
                                    <td><img src="{{ $product->thumbnailUrl() }}" class="thumb" alt="" loading="lazy"></td>
                                    <td>
                                        <a href="{{ route('admin.products.show', $product) }}" class="fw-semibold text-ink d-inline-block text-truncate" style="max-width: 220px">{{ $product->name }}</a>
                                        @if ($product->is_featured)<i class="bi bi-star-fill text-warning" title="Featured"></i>@endif
                                    </td>
                                    <td>{{ $product->user?->name }}</td>
                                    <td>{{ $product->category?->name }}</td>
                                    <td><x-price :product="$product" /></td>
                                    <td>{!! $product->is_free ? '<span class="badge badge-soft-success">Free</span>' : '<span class="badge badge-soft-primary">Paid</span>' !!}</td>
                                    <td><x-status-badge :status="$product->approval_status" /></td>
                                    <td><x-status-badge :status="$product->status" /></td>
                                    <td>{{ number_format($product->download_count) }}</td>
                                    <td class="text-nowrap">{{ $product->created_at->format('M j, Y') }}</td>
                                    <td class="text-end td-actions">
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-light" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Actions"><i class="bi bi-three-dots"></i></button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li><a class="dropdown-item" href="{{ route('admin.products.show', $product) }}"><i class="bi bi-eye me-2"></i>View / review</a></li>
                                                @if ($canManage)<li><a class="dropdown-item" href="{{ route('admin.products.edit', $product) }}"><i class="bi bi-pencil me-2"></i>Edit</a></li>@endif
                                                @if ($canApprove && ! $product->isPublished())
                                                    <li><button type="submit" form="act-approve-{{ $product->id }}" class="dropdown-item"><i class="bi bi-check2-circle me-2 text-success"></i>Approve &amp; publish</button></li>
                                                @endif
                                                @if ($canApprove && $product->approval_status !== \App\Enums\ApprovalStatus::Rejected)
                                                    <li><button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#reasonModal" data-action="{{ route('admin.products.reject', $product) }}" data-title="Reject &quot;{{ $product->name }}&quot;" data-help="The publisher will see this reason and can resubmit."><i class="bi bi-x-octagon me-2 text-danger"></i>Reject</button></li>
                                                @endif
                                                @if ($canManage)
                                                    @if ($product->approval_status === \App\Enums\ApprovalStatus::Approved && ! $product->isPublished())
                                                        <li><button type="submit" form="act-publish-{{ $product->id }}" class="dropdown-item"><i class="bi bi-globe me-2"></i>Publish</button></li>
                                                    @endif
                                                    @if ($product->isPublished())
                                                        <li><button type="submit" form="act-unpublish-{{ $product->id }}" class="dropdown-item"><i class="bi bi-eye-slash me-2"></i>Unpublish</button></li>
                                                    @endif
                                                    <li><button type="submit" form="act-feature-{{ $product->id }}" class="dropdown-item"><i class="bi bi-star me-2"></i>{{ $product->is_featured ? 'Remove featured' : 'Mark featured' }}</button></li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li><button type="submit" form="act-delete-{{ $product->id }}" class="dropdown-item text-danger"><i class="bi bi-trash me-2"></i>Delete</button></li>
                                                @endif
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if ($products->hasPages())<div class="card-footer">{{ $products->links() }}</div>@endif
            @endif
        </div>
    </form>

    {{-- Per-row action forms (outside the bulk form; forms cannot be nested). --}}
    @foreach ($products as $product)
        @if ($canApprove)<form id="act-approve-{{ $product->id }}" method="POST" action="{{ route('admin.products.approve', $product) }}" class="d-none">@csrf<input type="hidden" name="publish" value="1"></form>@endif
        @if ($canManage)
            <form id="act-publish-{{ $product->id }}" method="POST" action="{{ route('admin.products.publish', $product) }}" class="d-none">@csrf</form>
            <form id="act-unpublish-{{ $product->id }}" method="POST" action="{{ route('admin.products.unpublish', $product) }}" class="d-none" data-confirm="Unpublish &quot;{{ $product->name }}&quot;? It will be hidden from the marketplace.">@csrf</form>
            <form id="act-feature-{{ $product->id }}" method="POST" action="{{ route('admin.products.feature', $product) }}" class="d-none">@csrf</form>
            <form id="act-delete-{{ $product->id }}" method="POST" action="{{ route('admin.products.destroy', $product) }}" class="d-none" data-confirm="Delete &quot;{{ $product->name }}&quot;? Buyers will lose access to its downloads.">@csrf @method('DELETE')</form>
        @endif
    @endforeach
@endsection
