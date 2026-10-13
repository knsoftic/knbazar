@extends('layouts.admin')

@section('title', $category->exists ? 'Edit category' : 'Add category')

@section('content')
    <div class="panel-title">
        <div>
            <nav aria-label="breadcrumb"><ol class="breadcrumb mb-1"><li class="breadcrumb-item"><a href="{{ route('admin.categories.index') }}">Categories</a></li><li class="breadcrumb-item active">{{ $category->exists ? $category->name : 'New' }}</li></ol></nav>
            <h1>{{ $category->exists ? 'Edit category' : 'Add category' }}</h1>
        </div>
    </div>

    <form method="POST" action="{{ $category->exists ? route('admin.categories.update', $category) : route('admin.categories.store') }}" enctype="multipart/form-data" novalidate>
        @csrf
        @if ($category->exists) @method('PUT') @endif
        <div class="row g-4">
            <div class="col-xl-8">
                <div class="card mb-4">
                    <div class="card-header">Details</div>
                    <div class="card-body">
                        <x-input name="name" label="Name" :value="$category->name" required maxlength="120" data-slug-source="#f_slug" />
                        <x-input name="slug" label="Slug" :value="$category->slug" maxlength="150" prepend="/category/" help="Leave empty to generate automatically." />
                        <x-textarea name="description" label="Description" :value="$category->description" rows="3" maxlength="2000" />
                        <div class="row">
                            <div class="col-md-4"><x-select name="parent_id" label="Parent category" :options="$parents->pluck('name', 'id')->all()" :value="$category->parent_id" placeholder="None (top level)" /></div>
                            <div class="col-md-4"><x-input name="icon" label="Icon class" :value="$category->icon" maxlength="60" placeholder="bi-palette" help="Bootstrap Icons class, see icons.getbootstrap.com" /></div>
                            <div class="col-md-4"><x-input name="sort_order" type="number" min="0" label="Sort order" :value="$category->sort_order ?? 0" /></div>
                        </div>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header">SEO</div>
                    <div class="card-body">
                        <x-input name="seo_title" label="SEO title" :value="$category->seo_title" maxlength="255" data-counter="60" />
                        <x-textarea name="seo_description" label="SEO description" :value="$category->seo_description" rows="2" maxlength="500" data-counter="160" />
                        <x-input name="seo_keywords" label="Keywords" :value="$category->seo_keywords" maxlength="500" />
                    </div>
                </div>
            </div>
            <div class="col-xl-4">
                <div class="card mb-4">
                    <div class="card-header">Image</div>
                    <div class="card-body">
                        <img id="catPreview" src="{{ $category->imageUrl() ?? asset('assets/img/placeholder.svg') }}" class="upload-preview w-100 mb-2" style="height: auto; aspect-ratio: 16/10" alt="Category image preview">
                        <input type="file" name="image" class="form-control @error('image') is-invalid @enderror" accept="{{ \App\Support\UploadRules::imageAccept() }}" data-preview="#catPreview" aria-label="Category image">
                        @error('image')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        @if ($category->image)<div class="form-check mt-2"><input class="form-check-input" type="checkbox" name="remove_image" value="1" id="removeImage"><label class="form-check-label small" for="removeImage">Remove image</label></div>@endif
                    </div>
                </div>
                <div class="card">
                    <div class="card-body">
                        <x-toggle name="is_active" label="Active (visible on the marketplace)" :checked="$category->is_active ?? true" />
                        <button type="submit" class="btn btn-primary w-100" data-loading="Saving...">Save category</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection
