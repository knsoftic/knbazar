@extends('account.layout')

@section('account')
    <div class="panel-title"><div><h1>Downloads</h1><p>Your download access and history.</p></div></div>

    <div class="card mb-4">
        <div class="card-header">Download access</div>
        @if ($permissions->isEmpty())
            <x-empty-state icon="bi-download" title="No purchased products yet" text="After your payment is verified, your products appear here.">
                <a href="{{ route('products.index', ['type' => 'free']) }}" class="btn btn-sm btn-outline-primary">Browse free products</a>
            </x-empty-state>
        @else
            <div class="table-responsive-md">
                <table class="table table-stack">
                    <thead><tr><th>Product</th><th>Order</th><th>Downloads</th><th>Access</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($permissions as $permission)
                            @php
                                $product = $permission->product;
                                $active = $permission->isUsable() && $permission->order?->grantsDownloads() && $product && ! $product->trashed();
                            @endphp
                            <tr>
                                <td>
                                    <div class="d-flex gap-2 align-items-center">
                                        @if ($product)<img src="{{ $product->thumbnailUrl() }}" class="thumb" alt="" loading="lazy">@endif
                                        <div class="min-w-0">
                                            <div class="fw-semibold text-ink text-truncate">{{ $product?->name ?? 'Removed product' }}</div>
                                            @if ($product?->version)<div class="small text-muted-2">v{{ $product->version }}</div>@endif
                                        </div>
                                    </div>
                                </td>
                                <td>@if ($permission->order)<a href="{{ route('orders.show', $permission->order) }}">{{ $permission->order->order_number }}</a>@endif</td>
                                <td>{{ $permission->downloads_count }}{{ $permission->download_limit !== null ? ' / '.$permission->download_limit : '' }}</td>
                                <td>
                                    @if ($permission->isRevoked())
                                        <span class="badge badge-soft-danger">Revoked</span>
                                    @elseif ($permission->isExpired())
                                        <span class="badge badge-soft-secondary">Expired</span>
                                    @elseif ($permission->isLimitReached())
                                        <span class="badge badge-soft-warning">Limit reached</span>
                                    @elseif ($active)
                                        <span class="badge badge-soft-success">Active</span>
                                        @if ($permission->expires_at)<div class="small text-muted-2">until {{ $permission->expires_at->format('M j, Y') }}</div>@endif
                                    @else
                                        <span class="badge badge-soft-secondary">Unavailable</span>
                                    @endif
                                </td>
                                <td class="text-end td-actions">
                                    @if ($active)
                                        <a href="{{ route('downloads.download', $product->slug) }}" class="btn btn-sm btn-success" rel="nofollow"><i class="bi bi-download"></i> Download</a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <div class="card">
        <div class="card-header">Download history</div>
        @if ($history->isEmpty())
            <x-empty-state icon="bi-clock-history" title="No downloads yet" />
        @else
            <div class="table-responsive-md">
                <table class="table table-stack">
                    <thead><tr><th>Product</th><th>Type</th><th>Date</th></tr></thead>
                    <tbody>
                        @foreach ($history as $download)
                            <tr>
                                <td>
                                    @if ($download->product && ! $download->product->trashed())
                                        <a href="{{ route('products.show', $download->product->slug) }}">{{ $download->product->name }}</a>
                                    @else
                                        {{ $download->product?->name ?? 'Removed product' }}
                                    @endif
                                </td>
                                <td>{!! $download->is_free ? '<span class="badge badge-soft-success">Free</span>' : '<span class="badge badge-soft-primary">Purchased</span>' !!}</td>
                                <td>{{ $download->created_at->format('M j, Y H:i') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($history->hasPages())<div class="card-footer">{{ $history->links() }}</div>@endif
        @endif
    </div>
@endsection
