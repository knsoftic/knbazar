@extends('layouts.publisher')

@section('title', 'My products')

@php
    $tabs = ['' => 'All', 'draft' => 'Drafts', 'pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'];
@endphp

@section('content')
    <div class="panel-title">
        <div><h1>My products</h1><p>Drafts, submissions and published products.</p></div>
        @if (auth()->user()->canSubmitProducts())
            <a href="{{ route('publisher.products.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Add product</a>
        @endif
    </div>

    <ul class="nav nav-pills gap-1 mb-3 flex-wrap">
        @foreach ($tabs as $value => $label)
            <li class="nav-item">
                <a class="nav-link {{ (string) $status === $value ? 'active' : '' }}" href="{{ route('publisher.products.index', array_filter(['status' => $value, 'q' => request('q')])) }}">
                    {{ $label }} <span class="badge text-bg-light ms-1">{{ $value === '' ? $counts->sum() : ($counts[$value] ?? 0) }}</span>
                </a>
            </li>
        @endforeach
    </ul>

    <div class="card">
        @if ($products->isEmpty())
            <x-empty-state icon="bi-box-seam" title="No products found" text="{{ request('q') ? 'Try a different search.' : 'Add your first product to get started.' }}" />
        @else
            @include('publisher.products._table')
            @if ($products->hasPages())<div class="card-footer">{{ $products->links() }}</div>@endif
        @endif
    </div>
@endsection
