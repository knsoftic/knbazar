{{-- Shared notifications list used by the account, publisher and admin areas. --}}
<div class="panel-title">
    <div><h1>Notifications</h1><p>Updates about your products, orders and payments.</p></div>
    @if ($notifications->getCollection()->whereNull('read_at')->isNotEmpty())
        <form method="POST" action="{{ route('notifications.read-all') }}">
            @csrf
            <button type="submit" class="btn btn-light"><i class="bi bi-check2-all"></i> Mark all as read</button>
        </form>
    @endif
</div>
<div class="card">
    @if ($notifications->isEmpty())
        <x-empty-state icon="bi-bell" title="No notifications yet" />
    @else
        <div class="list-group list-group-flush">
            @foreach ($notifications as $notification)
                <a href="{{ route('notifications.open', $notification->id) }}" class="list-group-item list-group-item-action d-flex gap-3 py-3 {{ $notification->read_at ? '' : 'bg-primary-subtle bg-opacity-25' }}">
                    <span class="stat-card p-0 border-0 shadow-none bg-transparent"><span class="icon icon-{{ match ($notification->data['level'] ?? 'info') { 'success' => 'success', 'danger' => 'danger', 'warning' => 'warning', default => 'primary' } }}"><i class="bi {{ $notification->data['icon'] ?? 'bi-bell' }}"></i></span></span>
                    <span class="min-w-0 flex-grow-1">
                        <span class="d-flex justify-content-between gap-2">
                            <span class="fw-semibold text-ink">{{ $notification->data['title'] ?? 'Notification' }}</span>
                            <span class="small text-muted-2 text-nowrap">{{ $notification->created_at->diffForHumans() }}</span>
                        </span>
                        <span class="d-block small">{{ $notification->data['message'] ?? '' }}</span>
                    </span>
                </a>
            @endforeach
        </div>
        @if ($notifications->hasPages())<div class="card-footer">{{ $notifications->links() }}</div>@endif
    @endif
</div>
