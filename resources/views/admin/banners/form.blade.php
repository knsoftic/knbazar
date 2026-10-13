@extends('layouts.admin')

@section('title', $banner->exists ? 'Edit banner' : 'Add banner')

@section('content')
    <div class="panel-title">
        <div>
            <nav aria-label="breadcrumb"><ol class="breadcrumb mb-1"><li class="breadcrumb-item"><a href="{{ route('admin.banners.index') }}">Banners</a></li><li class="breadcrumb-item active">{{ $banner->exists ? $banner->title : 'New' }}</li></ol></nav>
            <h1>{{ $banner->exists ? 'Edit banner' : 'Add banner' }}</h1>
        </div>
    </div>

    <form method="POST" action="{{ $banner->exists ? route('admin.banners.update', $banner) : route('admin.banners.store') }}" enctype="multipart/form-data" novalidate>
        @csrf
        @if ($banner->exists) @method('PUT') @endif
        <div class="row g-4">
            <div class="col-xl-8">
                <div class="card">
                    <div class="card-body">
                        <x-input name="title" label="Title" :value="$banner->title" required maxlength="150" />
                        <x-textarea name="subtitle" label="Subtitle" :value="$banner->subtitle" rows="2" maxlength="500" />
                        <div class="row">
                            <div class="col-md-5"><x-input name="button_text" label="Button text" :value="$banner->button_text" maxlength="60" /></div>
                            <div class="col-md-7"><x-input name="button_url" label="Button link" :value="$banner->button_url" maxlength="255" placeholder="/products?type=free or https://..." /></div>
                            <div class="col-md-6"><x-input name="starts_at" type="datetime-local" label="Starts (optional)" :value="$banner->starts_at?->format('Y-m-d\TH:i')" /></div>
                            <div class="col-md-6"><x-input name="ends_at" type="datetime-local" label="Ends (optional)" :value="$banner->ends_at?->format('Y-m-d\TH:i')" /></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-4">
                <div class="card mb-4">
                    <div class="card-header">Background image</div>
                    <div class="card-body">
                        <img id="bannerPreview" src="{{ $banner->imageUrl() ?? asset('assets/img/placeholder.svg') }}" class="upload-preview w-100 mb-2" style="height: auto; aspect-ratio: 16/9" alt="Banner preview">
                        <input type="file" name="image" class="form-control @error('image') is-invalid @enderror" accept="{{ \App\Support\UploadRules::imageAccept() }}" data-preview="#bannerPreview" aria-label="Banner image">
                        @error('image')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">Optional. 1600x700 recommended; a gradient is used otherwise.</div>
                    </div>
                </div>
                <div class="card">
                    <div class="card-body">
                        <x-toggle name="is_active" label="Active" :checked="$banner->is_active" />
                        <x-input name="sort_order" type="number" min="0" label="Sort order" :value="$banner->sort_order ?? 0" />
                        <button type="submit" class="btn btn-primary w-100" data-loading="Saving...">Save banner</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection
