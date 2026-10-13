@extends('layouts.admin')

@section('title', 'Pages')

@section('content')
    <div class="panel-title">
        <div><h1>Pages</h1><p>About, Terms, Privacy, Refund policy and other static pages.</p></div>
        <a href="{{ route('admin.pages.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Add page</a>
    </div>

    <div class="card">
        @if ($pages->isEmpty())
            <x-empty-state icon="bi-file-earmark-text" title="No pages yet" />
        @else
            <div class="table-responsive-md">
                <table class="table table-hover table-stack">
                    <thead><tr><th>Title</th><th>URL</th><th>Footer</th><th>Status</th><th>Updated</th><th class="text-end">Actions</th></tr></thead>
                    <tbody>
                        @foreach ($pages as $page)
                            @php $url = $page->slug === 'contact' ? route('contact.show') : $page->url(); @endphp
                            <tr>
                                <td class="fw-semibold text-ink">{{ $page->title }}</td>
                                <td class="small"><a href="{{ $url }}" target="_blank">{{ parse_url($url, PHP_URL_PATH) }}</a></td>
                                <td>{{ $page->show_in_footer ? 'Yes' : 'No' }}</td>
                                <td>{!! $page->is_active ? '<span class="badge badge-soft-success">Published</span>' : '<span class="badge badge-soft-secondary">Hidden</span>' !!}</td>
                                <td>{{ $page->updated_at->format('M j, Y') }}</td>
                                <td class="text-end td-actions">
                                    <div class="d-inline-flex gap-1">
                                        <a href="{{ route('admin.pages.edit', $page) }}" class="btn btn-sm btn-light" title="Edit"><i class="bi bi-pencil"></i></a>
                                        <form method="POST" action="{{ route('admin.pages.destroy', $page) }}" data-confirm="Delete the page &quot;{{ $page->title }}&quot;?">
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
