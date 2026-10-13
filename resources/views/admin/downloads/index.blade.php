@extends('layouts.admin')

@section('title', 'Download history')

@section('content')
    <div class="panel-title"><div><h1>Download history</h1><p>Every free and paid download with user, IP and time.</p></div></div>

    <form method="GET" class="card mb-3">
        <div class="card-body row g-2 align-items-end">
            <div class="col-md-4"><label class="form-label small" for="q">Search</label><input type="search" name="q" id="q" value="{{ request('q') }}" class="form-control" placeholder="Product, user email or IP"></div>
            <div class="col-6 col-md-2"><label class="form-label small" for="type">Type</label>
                <select name="type" id="type" class="form-select"><option value="">All</option><option value="free" @selected(request('type') === 'free')>Free</option><option value="paid" @selected(request('type') === 'paid')>Paid</option></select></div>
            <div class="col-6 col-md-2"><label class="form-label small" for="from">From</label><input type="date" name="from" id="from" value="{{ request('from') }}" class="form-control"></div>
            <div class="col-6 col-md-2"><label class="form-label small" for="to">To</label><input type="date" name="to" id="to" value="{{ request('to') }}" class="form-control"></div>
            <div class="col-6 col-md-2 d-grid"><button class="btn btn-primary" type="submit"><i class="bi bi-funnel"></i> Filter</button></div>
        </div>
    </form>

    <div class="card">
        @if ($downloads->isEmpty())
            <x-empty-state icon="bi-download" title="No downloads found" />
        @else
            <div class="table-responsive-md">
                <table class="table table-hover table-stack">
                    <thead><tr><th>Date</th><th>Product</th><th>User</th><th>Type</th><th>Order</th><th>IP address</th><th>User agent</th></tr></thead>
                    <tbody>
                        @foreach ($downloads as $download)
                            <tr>
                                <td class="text-nowrap">{{ $download->created_at->format('M j, Y H:i') }}</td>
                                <td>{{ $download->product?->name ?? '-' }}</td>
                                <td>{!! $download->user ? e($download->user->name).'<div class="small text-muted-2">'.e($download->user->email).'</div>' : '<span class="text-muted-2">Guest</span>' !!}</td>
                                <td>{!! $download->is_free ? '<span class="badge badge-soft-success">Free</span>' : '<span class="badge badge-soft-primary">Paid</span>' !!}</td>
                                <td>@if ($download->order)<a href="{{ route('admin.orders.show', $download->order) }}">{{ $download->order->order_number }}</a>@else - @endif</td>
                                <td class="mono small">{{ $download->ip_address }}</td>
                                <td class="small text-muted-2"><span class="d-inline-block text-truncate" style="max-width: 220px" title="{{ $download->user_agent }}">{{ $download->user_agent }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($downloads->hasPages())<div class="card-footer">{{ $downloads->links() }}</div>@endif
        @endif
    </div>
@endsection
