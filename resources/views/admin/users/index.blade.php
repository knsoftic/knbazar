@extends('layouts.admin')

@section('title', $title)

@section('content')
    <div class="panel-title">
        <div><h1>{{ $title }}</h1><p>Customer and publisher accounts. Staff accounts are managed under Admins.</p></div>
        <a href="{{ route('admin.users.create') }}" class="btn btn-primary"><i class="bi bi-person-plus"></i> Add user</a>
    </div>

    <ul class="nav nav-pills gap-1 mb-3 flex-wrap">
        <li class="nav-item"><a class="nav-link {{ ! $role && request()->routeIs('admin.users.index') ? 'active' : '' }}" href="{{ route('admin.users.index') }}">All</a></li>
        <li class="nav-item"><a class="nav-link {{ $role === 'customer' ? 'active' : '' }}" href="{{ route('admin.customers.index') }}">Customers</a></li>
        <li class="nav-item"><a class="nav-link {{ $role === 'publisher' ? 'active' : '' }}" href="{{ route('admin.users.index', ['role' => 'publisher']) }}">Publishers</a></li>
        @can('manage_admins')<li class="nav-item"><a class="nav-link" href="{{ route('admin.admins.index') }}">Admins</a></li>@endcan
    </ul>

    <form method="GET" class="card mb-3">
        <div class="card-body row g-2 align-items-end">
            @if ($role && request()->routeIs('admin.users.index'))<input type="hidden" name="role" value="{{ $role }}">@endif
            <div class="col-md-6"><label class="form-label small" for="q">Search</label><input type="search" name="q" id="q" value="{{ request('q') }}" class="form-control" placeholder="Name, email or username"></div>
            <div class="col-md-4"><label class="form-label small" for="status">Status</label>
                <select name="status" id="status" class="form-select"><option value="">Any</option><option value="active" @selected(request('status') === 'active')>Active</option><option value="suspended" @selected(request('status') === 'suspended')>Suspended</option></select></div>
            <div class="col-md-2 d-grid"><button class="btn btn-primary" type="submit"><i class="bi bi-funnel"></i> Filter</button></div>
        </div>
    </form>

    <div class="card">
        @if ($users->isEmpty())
            <x-empty-state icon="bi-people" title="No users found" />
        @else
            <div class="table-responsive-md">
                <table class="table table-hover table-stack">
                    <thead><tr><th>User</th><th>Username</th><th>Role</th><th>Orders</th><th>Status</th><th>Joined</th><th>Last login</th><th class="text-end">Actions</th></tr></thead>
                    <tbody>
                        @foreach ($users as $listed)
                            <tr>
                                <td>
                                    <div class="d-flex gap-2 align-items-center">
                                        <span class="avatar">{{ $listed->initials() }}</span>
                                        <div class="min-w-0"><div class="fw-semibold text-ink">{{ $listed->name }}</div><div class="small text-muted-2 text-truncate">{{ $listed->email }}</div></div>
                                    </div>
                                </td>
                                <td class="small">{{ '@'.$listed->username }}</td>
                                <td><x-status-badge :status="$listed->roleName()" /></td>
                                <td>{{ $listed->orders_count }}</td>
                                <td><x-status-badge :status="$listed->status" /></td>
                                <td class="text-nowrap">{{ $listed->created_at->format('M j, Y') }}</td>
                                <td class="text-nowrap small">{{ $listed->last_login_at?->diffForHumans() ?? 'Never' }}</td>
                                <td class="text-end td-actions">
                                    <div class="d-inline-flex gap-1">
                                        @if ($listed->isAdmin())
                                            @can('manage_admins')<a href="{{ route('admin.admins.edit', $listed) }}" class="btn btn-sm btn-light" title="Edit admin"><i class="bi bi-shield-lock"></i></a>@endcan
                                        @else
                                            @if ($listed->isPublisher() && auth()->user()->can('manage_publishers'))
                                                <a href="{{ route('admin.publishers.show', $listed) }}" class="btn btn-sm btn-light" title="Publisher profile"><i class="bi bi-shop"></i></a>
                                            @endif
                                            <a href="{{ route('admin.users.edit', $listed) }}" class="btn btn-sm btn-light" title="Edit"><i class="bi bi-pencil"></i></a>
                                            <form method="POST" action="{{ route('admin.users.destroy', $listed) }}" data-confirm="Delete {{ $listed->email }}? Their order history is kept.">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-light text-danger" title="Delete"><i class="bi bi-trash"></i></button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($users->hasPages())<div class="card-footer">{{ $users->links() }}</div>@endif
        @endif
    </div>
@endsection
