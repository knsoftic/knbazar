@extends('layouts.admin')

@section('title', 'Payment gateways')

@section('content')
    <div class="panel-title">
        <div><h1>Payment gateways</h1><p>Manual payment methods shown at checkout (JazzCash, EasyPaisa, bank transfer, USDT...).</p></div>
        <a href="{{ route('admin.gateways.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Add gateway</a>
    </div>

    <div class="card">
        @if ($gateways->isEmpty())
            <x-empty-state icon="bi-wallet2" title="No payment gateways" text="Customers cannot check out until at least one gateway is active.">
                <a href="{{ route('admin.gateways.create') }}" class="btn btn-primary btn-sm">Add gateway</a>
            </x-empty-state>
        @else
            <div class="table-responsive-md">
                <table class="table table-hover table-stack">
                    <thead><tr><th>Gateway</th><th>Account fields</th><th>Currency</th><th>Screenshot</th><th>Payments</th><th>Order</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
                    <tbody>
                        @foreach ($gateways as $gateway)
                            <tr>
                                <td>
                                    <div class="d-flex gap-2 align-items-center">
                                        @if ($gateway->logoUrl())<img src="{{ $gateway->logoUrl() }}" class="gateway-logo" alt="">@else<span class="avatar"><i class="bi bi-wallet2"></i></span>@endif
                                        <div><div class="fw-semibold text-ink">{{ $gateway->name }}</div><div class="small text-muted-2">{{ ucfirst($gateway->driver) }}</div></div>
                                    </div>
                                </td>
                                <td class="small">{{ collect($gateway->detailRows())->pluck('label')->implode(', ') ?: '-' }}</td>
                                <td>{{ $gateway->currency }}</td>
                                <td>{{ $gateway->requires_proof ? 'Required' : 'Optional' }}</td>
                                <td>{{ $gateway->payments_count }}</td>
                                <td>{{ $gateway->sort_order }}</td>
                                <td>{!! $gateway->is_active ? '<span class="badge badge-soft-success">Active</span>' : '<span class="badge badge-soft-secondary">Disabled</span>' !!}</td>
                                <td class="text-end td-actions">
                                    <div class="d-inline-flex gap-1">
                                        <a href="{{ route('admin.gateways.edit', $gateway) }}" class="btn btn-sm btn-light" title="Edit"><i class="bi bi-pencil"></i></a>
                                        <form method="POST" action="{{ route('admin.gateways.destroy', $gateway) }}" data-confirm="Delete the {{ $gateway->name }} gateway? Existing orders keep their payment records.">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-light text-danger" title="Delete"><i class="bi bi-trash"></i></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection
