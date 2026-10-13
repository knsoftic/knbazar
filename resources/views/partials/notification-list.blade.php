<div class="d-flex justify-content-between align-items-center px-2 py-1">
    <strong class="small text-ink">Notifications</strong>
    @if ($items->whereNull('read_at')->isNotEmpty())
        <form method="POST" action="{{ route('notifications.read-all') }}">
            @csrf
            <button type="submit" class="btn btn-link btn-sm p-0 small">Mark all read</button>
        </form>
    @endif
</div>
<div class="dropdown-divider"></div>
@forelse ($items as $notification)
    <a href="{{ route('notifications.open', $notification->id) }}" class="notif-item {{ $notification->read_at ? '' : 'unread' }}">
        <i class="bi {{ $notification->data['icon'] ?? 'bi-bell' }}"></i>
        <span class="min-w-0">
            <span class="d-block small fw-semibold text-ink">{{ $notification->data['title'] ?? 'Notification' }}</span>
            <span class="d-block small text-muted-2">{{ \Illuminate\Support\Str::limit($notification->data['message'] ?? '', 90) }}</span>
            <span class="d-block small-xs text-muted-2">{{ $notification->created_at->diffForHumans() }}</span>
        </span>
    </a>
@empty
    <div class="text-center text-muted-2 small py-3">You're all caught up.</div>
@endforelse
<div class="dropdown-divider"></div>
<a href="{{ route('notifications.index') }}" class="dropdown-item text-center small fw-semibold">View all notifications</a>
