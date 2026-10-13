@extends('layouts.admin')

@section('title', 'Admins')

@section('content')
    <div class="panel-title">
        <div><h1>Admins</h1><p>Super Admins have full access. Admins only see the sections you grant them.</p></div>
        <a href="{{ route('admin.admins.create') }}" class="btn btn-primary"><i class="bi bi-person-plus"></i> Add admin</a>
    </div>

    <div class="card">
        <div class="table-responsive-md">
            <table class="table table-hover table-stack">
                <thead><tr><th>Admin</th><th>Role</th><th>Permissions</th><th>Status</th><th>Last login</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                    @foreach ($admins as $admin)
                        <tr>
                            <td>
                                <div class="d-flex gap-2 align-items-center">
                                    <span class="avatar">{{ $admin->initials() }}</span>
                                    <div class="min-w-0"><div class="fw-semibold text-ink">{{ $admin->name }} @if ($admin->is(auth()->user()))<span class="badge badge-soft-info">You</span>@endif</div><div class="small text-muted-2">{{ $admin->email }}</div></div>
                                </div>
                            </td>
                            <td><x-status-badge :status="$admin->roleName()" /></td>
                            <td class="small">
                                @if ($admin->isSuperAdmin())
                                    All permissions
                                @else
                                    {{ $admin->permissions->pluck('name')->implode(', ') ?: 'No permissions (dashboard only)' }}
                                @endif
                            </td>
                            <td><x-status-badge :status="$admin->status" /></td>
                            <td class="small text-nowrap">{{ $admin->last_login_at?->diffForHumans() ?? 'Never' }}</td>
                            <td class="text-end td-actions">
                                <div class="d-inline-flex gap-1">
                                    <a href="{{ route('admin.admins.edit', $admin) }}" class="btn btn-sm btn-light" title="Edit"><i class="bi bi-pencil"></i></a>
                                    @unless ($admin->is(auth()->user()))
                                        <form method="POST" action="{{ route('admin.admins.destroy', $admin) }}" data-confirm="Delete the admin account {{ $admin->email }}?">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-light text-danger" title="Delete"><i class="bi bi-trash"></i></button>
                                        </form>
                                    @endunless
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
