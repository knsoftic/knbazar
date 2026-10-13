@extends('layouts.admin')

@section('title', 'System logs')

@section('content')
    <div class="panel-title"><div><h1>System logs</h1><p>Admin actions, approvals, payments and security events.</p></div></div>

    <ul class="nav nav-pills gap-1 mb-3">
        <li class="nav-item"><a class="nav-link {{ $tab !== 'files' ? 'active' : '' }}" href="{{ route('admin.logs.index') }}">Activity log</a></li>
        <li class="nav-item"><a class="nav-link {{ $tab === 'files' ? 'active' : '' }}" href="{{ route('admin.logs.index', ['tab' => 'files']) }}">Log files</a></li>
    </ul>

    @if ($tab === 'files')
        <div class="card">
            <div class="card-header d-flex gap-2 align-items-center flex-wrap">
                @foreach (\App\Http\Controllers\Admin\LogController::FILES as $name)
                    <a href="{{ route('admin.logs.index', ['tab' => 'files', 'file' => $name]) }}" class="btn btn-sm {{ $file === $name ? 'btn-primary' : 'btn-light' }}">{{ $name }}.log</a>
                @endforeach
                <span class="small text-muted-2 ms-auto">Last {{ count($lines) }} lines (newest at the bottom)</span>
            </div>
            <div class="card-body">
                @if (empty($lines))
                    <x-empty-state icon="bi-journal" title="Log file is empty or missing" />
                @else
                    <div class="log-viewer">@foreach ($lines as $line)<div class="{{ str_contains($line, '.ERROR') || str_contains($line, '.CRITICAL') ? 'lvl-error' : (str_contains($line, '.WARNING') ? 'lvl-warning' : '') }}">{{ $line }}</div>@endforeach</div>
                @endif
            </div>
        </div>
    @else
        <form method="GET" class="card mb-3">
            <div class="card-body row g-2 align-items-end">
                <div class="col-md-5"><label class="form-label small" for="action">Action</label>
                    <select name="action" id="action" class="form-select" data-autosubmit><option value="">All actions</option>@foreach ($actions as $action)<option value="{{ $action }}" @selected(request('action') === $action)>{{ $action }}</option>@endforeach</select></div>
            </div>
        </form>
        <div class="card">
            @if ($activity->isEmpty())
                <x-empty-state icon="bi-journal" title="No activity recorded" />
            @else
                <div class="table-responsive-md">
                    <table class="table table-hover table-stack">
                        <thead><tr><th>Time</th><th>User</th><th>Action</th><th>Description</th><th>IP</th></tr></thead>
                        <tbody>
                            @foreach ($activity as $log)
                                <tr>
                                    <td class="text-nowrap small">{{ $log->created_at?->format('M j, Y H:i:s') }}</td>
                                    <td>{{ $log->user?->name ?? 'System' }}</td>
                                    <td><span class="badge badge-soft-{{ str_contains($log->action, 'reject') || str_contains($log->action, 'delete') ? 'danger' : (str_contains($log->action, 'approve') ? 'success' : 'secondary') }}">{{ $log->action }}</span></td>
                                    <td class="small">{{ $log->description }}@if ($log->properties)<div class="text-muted-2 mono small-xs text-break">{{ \Illuminate\Support\Str::limit(json_encode($log->properties), 160) }}</div>@endif</td>
                                    <td class="mono small">{{ $log->ip_address }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if ($activity->hasPages())<div class="card-footer">{{ $activity->links() }}</div>@endif
            @endif
        </div>
    @endif
@endsection
