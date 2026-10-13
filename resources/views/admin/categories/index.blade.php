@extends('layouts.admin')

@section('title', 'Categories')

@section('content')
    <div class="panel-title">
        <div><h1>Categories</h1><p>Organise products. Inactive categories hide their products from the marketplace.</p></div>
        <a href="{{ route('admin.categories.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Add category</a>
    </div>

    <div class="card">
        <div class="card-header">
            <form method="GET" class="d-flex gap-2" style="max-width: 360px">
                <input type="search" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Search categories" aria-label="Search categories">
                <button class="btn btn-sm btn-light" type="submit"><i class="bi bi-search"></i></button>
            </form>
        </div>
        @if ($categories->isEmpty())
            <x-empty-state icon="bi-folder2" title="No categories" />
        @else
            <div class="table-responsive-md">
                <table class="table table-hover table-stack">
                    <thead><tr><th>Category</th><th>Slug</th><th>Parent</th><th>Products</th><th>Published</th><th>Order</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
                    <tbody>
                        @foreach ($categories as $category)
                            <tr>
                                <td>
                                    <div class="d-flex gap-2 align-items-center">
                                        @if ($category->imageUrl())<img src="{{ $category->imageUrl() }}" class="thumb" alt="" loading="lazy">@else<span class="avatar"><i class="bi {{ $category->icon ?: 'bi-folder2' }}"></i></span>@endif
                                        <span class="fw-semibold text-ink">{{ $category->name }}</span>
                                    </div>
                                </td>
                                <td class="small text-muted-2">/category/{{ $category->slug }}</td>
                                <td>{{ $category->parent?->name ?? '-' }}</td>
                                <td>{{ $category->products_count }}</td>
                                <td>{{ $category->published_count }}</td>
                                <td>{{ $category->sort_order }}</td>
                                <td>
                                    <form method="POST" action="{{ route('admin.categories.toggle', $category) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-sm p-0 border-0" title="Toggle status">
                                            {!! $category->is_active ? '<span class="badge badge-soft-success">Active</span>' : '<span class="badge badge-soft-secondary">Inactive</span>' !!}
                                        </button>
                                    </form>
                                </td>
                                <td class="text-end td-actions">
                                    <div class="d-inline-flex gap-1">
                                        @if ($category->is_active)<a href="{{ $category->url() }}" target="_blank" class="btn btn-sm btn-light" title="View"><i class="bi bi-box-arrow-up-right"></i></a>@endif
                                        <a href="{{ route('admin.categories.edit', $category) }}" class="btn btn-sm btn-light" title="Edit"><i class="bi bi-pencil"></i></a>
                                        <form method="POST" action="{{ route('admin.categories.destroy', $category) }}" data-confirm="Delete the &quot;{{ $category->name }}&quot; category?">
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
            @if ($categories->hasPages())<div class="card-footer">{{ $categories->links() }}</div>@endif
        @endif
    </div>
@endsection
