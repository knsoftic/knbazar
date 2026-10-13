@extends('layouts.admin')

@section('title', 'Contact messages')

@section('content')
    <div class="panel-title"><div><h1>Contact messages</h1><p>Messages sent through the contact form.</p></div></div>

    <div class="d-flex flex-wrap gap-2 justify-content-between mb-3">
        <ul class="nav nav-pills gap-1">
            <li class="nav-item"><a class="nav-link {{ request('filter') !== 'unread' ? 'active' : '' }}" href="{{ route('admin.messages.index') }}">All</a></li>
            <li class="nav-item"><a class="nav-link {{ request('filter') === 'unread' ? 'active' : '' }}" href="{{ route('admin.messages.index', ['filter' => 'unread']) }}">Unread</a></li>
        </ul>
        <form method="GET" class="d-flex gap-2">
            @if (request('filter'))<input type="hidden" name="filter" value="{{ request('filter') }}">@endif
            <input type="search" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Search name, email, subject" aria-label="Search messages">
            <button class="btn btn-sm btn-light" type="submit"><i class="bi bi-search"></i></button>
        </form>
    </div>

    <div class="card">
        @if ($messages->isEmpty())
            <x-empty-state icon="bi-envelope" title="No messages" />
        @else
            <div class="table-responsive-md">
                <table class="table table-hover table-stack">
                    <thead><tr><th>From</th><th>Subject</th><th>Received</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
                    <tbody>
                        @foreach ($messages as $message)
                            <tr class="{{ $message->is_read ? '' : 'fw-semibold' }}">
                                <td><div class="text-ink">{{ $message->name }}</div><div class="small text-muted-2 fw-normal">{{ $message->email }}</div></td>
                                <td><a href="{{ route('admin.messages.show', $message) }}" class="text-ink d-inline-block text-truncate" style="max-width: 320px">{{ $message->subject }}</a></td>
                                <td class="text-nowrap fw-normal">{{ $message->created_at->diffForHumans() }}</td>
                                <td>{!! $message->is_read ? '<span class="badge badge-soft-secondary">Read</span>' : '<span class="badge badge-soft-primary">New</span>' !!}</td>
                                <td class="text-end td-actions">
                                    <div class="d-inline-flex gap-1">
                                        <a href="{{ route('admin.messages.show', $message) }}" class="btn btn-sm btn-light" title="View"><i class="bi bi-eye"></i></a>
                                        <form method="POST" action="{{ route('admin.messages.toggle', $message) }}">@csrf<button type="submit" class="btn btn-sm btn-light" title="{{ $message->is_read ? 'Mark unread' : 'Mark read' }}"><i class="bi {{ $message->is_read ? 'bi-envelope' : 'bi-envelope-open' }}"></i></button></form>
                                        <form method="POST" action="{{ route('admin.messages.destroy', $message) }}" data-confirm="Delete this message?">@csrf @method('DELETE')<button type="submit" class="btn btn-sm btn-light text-danger" title="Delete"><i class="bi bi-trash"></i></button></form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($messages->hasPages())<div class="card-footer">{{ $messages->links() }}</div>@endif
        @endif
    </div>
@endsection
