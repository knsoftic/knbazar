@extends('layouts.admin')

@section('title', 'Publishers')

@section('content')
    <div class="panel-title">
        <div><h1>Publishers</h1><p>Approve new publishers and manage their accounts. Approval required: <strong>{{ setting('publisher_require_approval') ? 'Yes' : 'No' }}</strong>.</p></div>
    </div>

    <ul class="nav nav-pills gap-1 mb-3 flex-wrap">
        <li class="nav-item"><a class="nav-link {{ ! $status ? 'active' : '' }}" href="{{ route('admin.publishers.index') }}">All</a></li>
        @foreach (\App\Enums\PublisherStatus::options() as $value => $label)
            <li class="nav-item"><a class="nav-link {{ $status === $value ? 'active' : '' }}" href="{{ route('admin.publishers.index', ['status' => $value]) }}">{{ $label }}</a></li>
        @endforeach
    </ul>

    <div class="card">
        <div class="card-header">
            <form method="GET" class="d-flex gap-2" style="max-width: 380px">
                @if ($status)<input type="hidden" name="status" value="{{ $status }}">@endif
                <input type="search" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Search publishers" aria-label="Search publishers">
                <button class="btn btn-sm btn-light" type="submit"><i class="bi bi-search"></i></button>
            </form>
        </div>
        @if ($publishers->isEmpty())
            <x-empty-state icon="bi-shop" title="No publishers found" />
        @else
            <div class="table-responsive-md">
                <table class="table table-hover table-stack">
                    <thead><tr><th>Publisher</th><th>Contact</th><th>Products</th><th>Published</th><th>Status</th><th>Joined</th><th class="text-end">Actions</th></tr></thead>
                    <tbody>
                        @foreach ($publishers as $publisher)
                            @php $profile = $publisher->publisherProfile; @endphp
                            <tr>
                                <td>
                                    <div class="d-flex gap-2 align-items-center">
                                        @if ($profile?->logoUrl())<img src="{{ $profile->logoUrl() }}" class="avatar" alt="">@else<span class="avatar">{{ $publisher->initials() }}</span>@endif
                                        <div class="min-w-0"><a href="{{ route('admin.publishers.show', $publisher) }}" class="fw-semibold text-ink">{{ $publisher->publicName() }}</a><div class="small text-muted-2">{{ '@'.$publisher->username }}</div></div>
                                    </div>
                                </td>
                                <td class="small">{{ $publisher->email }}<div class="text-muted-2">{{ $publisher->phone }}</div></td>
                                <td>{{ $publisher->products_count }}</td>
                                <td>{{ $publisher->published_count }}</td>
                                <td>@if ($profile)<x-status-badge :status="$profile->status" />@else<span class="badge badge-soft-secondary">No profile</span>@endif @if (! $publisher->isActive())<span class="badge badge-soft-danger">Account suspended</span>@endif</td>
                                <td class="text-nowrap">{{ $publisher->created_at->format('M j, Y') }}</td>
                                <td class="text-end td-actions">
                                    <div class="d-inline-flex gap-1">
                                        <a href="{{ route('admin.publishers.show', $publisher) }}" class="btn btn-sm btn-light" title="View"><i class="bi bi-eye"></i></a>
                                        @if ($profile && $profile->status !== \App\Enums\PublisherStatus::Approved)
                                            <form method="POST" action="{{ route('admin.publishers.status', $publisher) }}">@csrf<input type="hidden" name="status" value="approved"><button type="submit" class="btn btn-sm btn-success" title="Approve"><i class="bi bi-check2"></i> Approve</button></form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($publishers->hasPages())<div class="card-footer">{{ $publishers->links() }}</div>@endif
        @endif
    </div>
@endsection
