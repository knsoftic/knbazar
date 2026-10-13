@extends('layouts.admin')

@section('title', 'Homepage banners')

@section('content')
    <div class="panel-title">
        <div><h1>Homepage banners</h1><p>Promotional slides shown in the homepage hero. Hero text and section toggles live in <a href="{{ route('admin.settings.edit', 'homepage') }}">Settings &rarr; Homepage</a>.</p></div>
        <a href="{{ route('admin.banners.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Add banner</a>
    </div>

    <div class="card">
        @if ($banners->isEmpty())
            <x-empty-state icon="bi-images" title="No banners" text="Without banners the homepage shows the standard hero." />
        @else
            <div class="table-responsive-md">
                <table class="table table-hover table-stack">
                    <thead><tr><th>Banner</th><th>Button</th><th>Schedule</th><th>Order</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
                    <tbody>
                        @foreach ($banners as $banner)
                            <tr>
                                <td>
                                    <div class="d-flex gap-2 align-items-center">
                                        @if ($banner->imageUrl())<img src="{{ $banner->imageUrl() }}" class="thumb" alt="">@endif
                                        <div class="min-w-0"><div class="fw-semibold text-ink">{{ $banner->title }}</div><div class="small text-muted-2 text-truncate" style="max-width: 280px">{{ $banner->subtitle }}</div></div>
                                    </div>
                                </td>
                                <td class="small">{{ $banner->button_text ? $banner->button_text.' -> '.$banner->button_url : '-' }}</td>
                                <td class="small">{{ $banner->starts_at?->format('M j') ?? 'Now' }} - {{ $banner->ends_at?->format('M j, Y') ?? 'No end' }}</td>
                                <td>{{ $banner->sort_order }}</td>
                                <td>{!! $banner->is_active ? '<span class="badge badge-soft-success">Active</span>' : '<span class="badge badge-soft-secondary">Inactive</span>' !!}</td>
                                <td class="text-end td-actions">
                                    <div class="d-inline-flex gap-1">
                                        <a href="{{ route('admin.banners.edit', $banner) }}" class="btn btn-sm btn-light" title="Edit"><i class="bi bi-pencil"></i></a>
                                        <form method="POST" action="{{ route('admin.banners.destroy', $banner) }}" data-confirm="Delete this banner?">
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
