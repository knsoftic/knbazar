@extends('layouts.admin')

@section('title', $publisher->publicName())

@php use App\Enums\PublisherStatus; $profile = $publisher->publisherProfile; @endphp

@section('content')
    <div class="panel-title">
        <div>
            <nav aria-label="breadcrumb"><ol class="breadcrumb mb-1"><li class="breadcrumb-item"><a href="{{ route('admin.publishers.index') }}">Publishers</a></li><li class="breadcrumb-item active">{{ '@'.$publisher->username }}</li></ol></nav>
            <h1>{{ $publisher->publicName() }}</h1>
            <p class="d-flex gap-2 align-items-center mb-0">@if ($profile)<x-status-badge :status="$profile->status" />@endif <x-status-badge :status="$publisher->status" /></p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            @if ($profile)
                @if ($profile->status !== PublisherStatus::Approved)
                    <form method="POST" action="{{ route('admin.publishers.status', $publisher) }}">@csrf<input type="hidden" name="status" value="approved"><button type="submit" class="btn btn-success"><i class="bi bi-check2-circle"></i> Approve</button></form>
                @endif
                @if ($profile->status === PublisherStatus::Pending)
                    <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#publisherRejectModal"><i class="bi bi-x-octagon"></i> Reject</button>
                @endif
                @if ($profile->status === PublisherStatus::Approved)
                    <form method="POST" action="{{ route('admin.publishers.status', $publisher) }}" data-confirm="Suspend this publisher? They will not be able to submit or edit products.">@csrf<input type="hidden" name="status" value="suspended"><button type="submit" class="btn btn-outline-danger"><i class="bi bi-pause-circle"></i> Suspend</button></form>
                @endif
            @endif
            @can('manage_users')<a href="{{ route('admin.users.edit', $publisher) }}" class="btn btn-light"><i class="bi bi-pencil"></i> Edit account</a>@endcan
            @if ($profile?->isApproved())<a href="{{ route('publishers.show', $publisher->username) }}" target="_blank" class="btn btn-light"><i class="bi bi-box-arrow-up-right"></i> Storefront</a>@endif
        </div>
    </div>

    @if ($profile?->rejection_reason)<div class="alert alert-danger"><strong>Rejection reason:</strong> {{ $profile->rejection_reason }}</div>@endif

    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3"><x-stat-card label="Products" :value="$stats['products']" icon="bi-box-seam" /></div>
        <div class="col-6 col-lg-3"><x-stat-card label="Published" :value="$stats['published']" icon="bi-check2-circle" color="success" /></div>
        <div class="col-6 col-lg-3"><x-stat-card label="Items sold" :value="$stats['sales'].' / '.money($stats['revenue'])" icon="bi-cash-coin" color="warning" /></div>
        <div class="col-6 col-lg-3"><x-stat-card label="Downloads" :value="number_format($stats['downloads'])" icon="bi-download" color="info" /></div>
    </div>

    <div class="row g-4">
        <div class="col-xl-4">
            <div class="card">
                <div class="card-header">Profile</div>
                <div class="card-body">
                    <ul class="spec-list">
                        <li><span>Name</span><span>{{ $publisher->name }}</span></li>
                        <li><span>Email</span><span>{{ $publisher->email }}</span></li>
                        <li><span>Phone</span><span>{{ $publisher->phone ?: '-' }}</span></li>
                        <li><span>WhatsApp</span><span>{{ $profile?->whatsapp ?: '-' }}</span></li>
                        <li><span>Company</span><span>{{ $profile?->company ?: '-' }}</span></li>
                        <li><span>Country</span><span>{{ $profile?->country ?: '-' }}</span></li>
                        <li><span>Website</span><span>@if ($profile?->website)<a href="{{ $profile->website }}" target="_blank" rel="noopener nofollow">{{ \Illuminate\Support\Str::limit($profile->website, 30) }}</a>@else - @endif</span></li>
                        <li><span>Joined</span><span>{{ $publisher->created_at->format('M j, Y') }}</span></li>
                        @if ($profile?->approved_at)<li><span>Approved</span><span>{{ $profile->approved_at->format('M j, Y') }}{{ $profile->approver ? ' by '.$profile->approver->name : '' }}</span></li>@endif
                    </ul>
                    @if ($profile?->bio)<p class="small mt-3 mb-0">{{ $profile->bio }}</p>@endif
                </div>
            </div>
        </div>
        <div class="col-xl-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between">Products @can('view_products')<a href="{{ route('admin.products.index', ['publisher' => $publisher->id]) }}" class="small">View all</a>@endcan</div>
                @if ($products->isEmpty())
                    <x-empty-state icon="bi-box-seam" title="No products yet" />
                @else
                    <div class="table-responsive-md">
                        <table class="table table-hover table-stack">
                            <thead><tr><th>Product</th><th>Category</th><th>Price</th><th>Approval</th><th>Status</th></tr></thead>
                            <tbody>
                                @foreach ($products as $product)
                                    <tr>
                                        <td>@can('view_products')<a href="{{ route('admin.products.show', $product) }}" class="fw-semibold">{{ $product->name }}</a>@else{{ $product->name }}@endcan</td>
                                        <td>{{ $product->category?->name }}</td>
                                        <td><x-price :product="$product" /></td>
                                        <td><x-status-badge :status="$product->approval_status" /></td>
                                        <td><x-status-badge :status="$product->status" /></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Publisher rejection (needs status + reason) --}}
    <div class="modal fade" id="publisherRejectModal" tabindex="-1" aria-labelledby="publisherRejectTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" action="{{ route('admin.publishers.status', $publisher) }}" class="modal-content">
                @csrf
                <input type="hidden" name="status" value="rejected">
                <div class="modal-header"><h2 class="modal-title h5" id="publisherRejectTitle">Reject publisher application</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
                <div class="modal-body">
                    <label for="pubReason" class="form-label">Reason <span class="text-danger">*</span></label>
                    <textarea name="reason" id="pubReason" rows="4" class="form-control" required maxlength="1000"></textarea>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-danger">Reject</button></div>
            </form>
        </div>
    </div>
@endsection
