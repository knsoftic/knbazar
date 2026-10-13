@extends('layouts.admin')

@section('title', $page->exists ? 'Edit page' : 'Add page')

@section('content')
    <div class="panel-title">
        <div>
            <nav aria-label="breadcrumb"><ol class="breadcrumb mb-1"><li class="breadcrumb-item"><a href="{{ route('admin.pages.index') }}">Pages</a></li><li class="breadcrumb-item active">{{ $page->exists ? $page->title : 'New' }}</li></ol></nav>
            <h1>{{ $page->exists ? 'Edit page' : 'Add page' }}</h1>
        </div>
        @if ($page->exists && $page->is_active)<a href="{{ $page->slug === 'contact' ? route('contact.show') : $page->url() }}" target="_blank" class="btn btn-light"><i class="bi bi-box-arrow-up-right"></i> View</a>@endif
    </div>

    <form method="POST" action="{{ $page->exists ? route('admin.pages.update', $page) : route('admin.pages.store') }}" novalidate>
        @csrf
        @if ($page->exists) @method('PUT') @endif
        <div class="row g-4">
            <div class="col-xl-8">
                <div class="card mb-4">
                    <div class="card-body">
                        <x-input name="title" label="Title" :value="$page->title" required maxlength="200" data-slug-source="#f_slug" />
                        <x-input name="slug" label="Slug" :value="$page->slug" maxlength="150" prepend="/page/" help="The page with slug 'contact' is shown above the contact form." />
                        <x-textarea name="content" label="Content" :value="$page->content" rows="18" maxlength="200000" class="mono" help="Markdown supported (## headings, **bold**, - lists, [links](https://...)). Basic HTML is allowed for staff-authored pages." />
                    </div>
                </div>
            </div>
            <div class="col-xl-4">
                <div class="card mb-4">
                    <div class="card-header">SEO</div>
                    <div class="card-body">
                        <x-input name="seo_title" label="SEO title" :value="$page->seo_title" maxlength="255" data-counter="60" />
                        <x-textarea name="seo_description" label="SEO description" :value="$page->seo_description" rows="3" maxlength="500" data-counter="160" />
                        <x-input name="seo_keywords" label="Keywords" :value="$page->seo_keywords" maxlength="500" />
                    </div>
                </div>
                <div class="card">
                    <div class="card-body">
                        <x-toggle name="is_active" label="Published" :checked="$page->is_active" />
                        <x-toggle name="show_in_footer" label="Show link in footer" :checked="$page->show_in_footer" />
                        <x-input name="sort_order" type="number" min="0" label="Sort order" :value="$page->sort_order ?? 0" />
                        <button type="submit" class="btn btn-primary w-100" data-loading="Saving...">Save page</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection
