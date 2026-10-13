@extends('account.layout')

@section('account')
    <div class="panel-title">
        <div><h1>Hi, {{ \Illuminate\Support\Str::before($user->name, ' ') }}</h1><p>Here's what's happening with your account.</p></div>
        <a href="{{ route('products.index') }}" class="btn btn-primary"><i class="bi bi-bag"></i> Browse products</a>
    </div>

    @if (setting('email_verification_required') && ! $user->hasVerifiedEmail())
        <div class="alert alert-warning d-flex justify-content-between align-items-center flex-wrap gap-2">
            <span><i class="bi bi-envelope-exclamation"></i> Please verify your email address to check out and download products.</span>
            <a href="{{ route('verification.notice') }}" class="btn btn-sm btn-warning">Verify now</a>
        </div>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3"><x-stat-card label="Orders" :value="$stats['orders']" icon="bi-receipt" :href="route('orders.index')" /></div>
        <div class="col-6 col-xl-3"><x-stat-card label="Awaiting payment" :value="$stats['awaiting']" icon="bi-hourglass-split" color="warning" :href="route('orders.index')" /></div>
        <div class="col-6 col-xl-3"><x-stat-card label="Purchased products" :value="$stats['purchased']" icon="bi-bag-check" color="success" :href="route('account.purchases')" /></div>
        <div class="col-6 col-xl-3"><x-stat-card label="Downloads" :value="$stats['downloads']" icon="bi-download" color="info" :href="route('downloads.index')" /></div>
    </div>

    <div class="row g-4">
        <div class="col-xl-7">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">Recent orders <a href="{{ route('orders.index') }}" class="small">View all</a></div>
                @if ($recentOrders->isEmpty())
                    <x-empty-state icon="bi-receipt" title="No orders yet" text="Your purchases will appear here." />
                @else
                    <div class="list-group list-group-flush">
                        @foreach ($recentOrders as $order)
                            <a href="{{ route('orders.show', $order) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center gap-2 flex-wrap">
                                <span>
                                    <span class="fw-semibold text-ink d-block">{{ $order->order_number }}</span>
                                    <span class="small text-muted-2">{{ $order->created_at->format('M j, Y') }} &middot; {{ trans_choice(':count item|:count items', $order->items_count) }}</span>
                                </span>
                                <span class="text-end">
                                    <span class="fw-semibold d-block">{{ money($order->total, $order->currency) }}</span>
                                    <x-status-badge :status="$order->status" />
                                </span>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
        <div class="col-xl-5">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">Notifications <a href="{{ route('notifications.index') }}" class="small">View all</a></div>
                <div class="card-body p-2">
                    @forelse ($notifications as $notification)
                        <a href="{{ route('notifications.open', $notification->id) }}" class="notif-item {{ $notification->read_at ? '' : 'unread' }}">
                            <i class="bi {{ $notification->data['icon'] ?? 'bi-bell' }}"></i>
                            <span class="min-w-0">
                                <span class="d-block small fw-semibold text-ink">{{ $notification->data['title'] ?? '' }}</span>
                                <span class="d-block small text-muted-2">{{ \Illuminate\Support\Str::limit($notification->data['message'] ?? '', 100) }}</span>
                            </span>
                        </a>
                    @empty
                        <x-empty-state icon="bi-bell" title="No notifications" />
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection
