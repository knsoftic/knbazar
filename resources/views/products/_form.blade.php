{{--
    Shared product form.
    Expects: $product, $categories, $action, $method ('POST'|'PUT'), $isAdmin (bool), optional $owners, $destroyImageRoute
--}}
@php
    use App\Support\UploadRules;
    $isAdmin ??= false;
    $keywords = old('keywords', $product->exists ? $product->keywords->pluck('name')->implode(', ') : '');
    $fileMax = (int) setting('upload_file_max_mb', 100);
    $galleryMax = (int) setting('upload_gallery_max', 8);
    $isLive = $product->exists && in_array($product->approval_status, [\App\Enums\ApprovalStatus::Approved, \App\Enums\ApprovalStatus::Pending], true);
@endphp

@if ($errors->any())
    <div class="alert alert-danger"><i class="bi bi-exclamation-octagon"></i> Please fix the highlighted fields below.</div>
@endif

@if (! $isAdmin && $isLive)
    <div class="alert alert-warning d-flex gap-2"><i class="bi bi-info-circle mt-1"></i><div>This product is <strong>{{ strtolower($product->status->label()) }}</strong>. Saving changes sends it back to <strong>draft</strong> (hidden) or <strong>pending review</strong>, so every change is reviewed before going live.</div></div>
@endif

@if (! $isAdmin && $product->isRejected() && $product->rejection_reason)
    <div class="alert alert-danger"><strong><i class="bi bi-x-octagon"></i> Rejected:</strong> {{ $product->rejection_reason }}<div class="small mt-1">Update the product and click <em>Save &amp; submit for review</em> to resubmit.</div></div>
@endif

<form method="POST" action="{{ $action }}" enctype="multipart/form-data" novalidate id="productForm">
    @csrf
    @if ($method !== 'POST') @method($method) @endif

    <div class="row g-4">
        <div class="col-xl-8">
            {{-- Basics --}}
            <div class="card mb-4">
                <div class="card-header">Basic information</div>
                <div class="card-body">
                    <x-input name="name" label="Product name" :value="$product->name" required maxlength="200" data-slug-source="#f_slug" />
                    <x-input name="slug" label="URL slug" :value="$product->slug" maxlength="190" prepend="/product/" help="Leave empty to generate from the name. Changing it keeps a redirect from the old URL." />
                    <div class="row">
                        <div class="col-md-6"><x-select name="category_id" label="Category" :options="$categories->mapWithKeys(fn ($c) => [$c->id => $c->name.(isset($c->is_active) && ! $c->is_active ? ' (inactive)' : '')])->all()" :value="$product->category_id" required placeholder="Select a category" /></div>
                        <div class="col-md-6"><x-select name="product_type" label="Product type" :options="config('marketplace.product_types')" :value="$product->product_type" required /></div>
                    </div>
                    <x-textarea name="short_description" label="Short description" :value="$product->short_description" rows="2" maxlength="500" help="Shown on product cards and search results (max 500 characters)." />
                    <x-textarea name="description" label="Full description" :value="$product->description" rows="10" maxlength="50000" help="Markdown supported: ## headings, **bold**, - lists, [links](https://...). HTML is not allowed." />
                    <div class="row">
                        <div class="col-md-6"><x-textarea name="features" label="Features (one per line)" :value="$product->features" rows="5" maxlength="5000" /></div>
                        <div class="col-md-6"><x-textarea name="requirements" label="Requirements (one per line)" :value="$product->requirements" rows="5" maxlength="5000" /></div>
                    </div>
                    <div class="row">
                        <div class="col-md-4"><x-input name="version" label="Version" :value="$product->version" maxlength="30" placeholder="1.0.0" /></div>
                        <div class="col-md-4"><x-input name="demo_url" type="url" label="Demo URL" :value="$product->demo_url" maxlength="255" placeholder="https://" /></div>
                        <div class="col-md-4"><x-input name="documentation_url" type="url" label="Documentation URL" :value="$product->documentation_url" maxlength="255" placeholder="https://" /></div>
                    </div>
                    <x-input name="keywords" label="Keywords" :value="$keywords" maxlength="1000" help="Comma-separated, e.g. wordpress theme, business theme, responsive template. Used for search and SEO (max 20)." />
                </div>
            </div>

            {{-- Media --}}
            <div class="card mb-4">
                <div class="card-header">Images</div>
                <div class="card-body">
                    <div class="row g-3 align-items-center mb-3">
                        <div class="col-auto"><img id="imagePreview" src="{{ $product->image ? $product->imageUrl() : asset('assets/img/placeholder.svg') }}" class="upload-preview" alt="Main image preview"></div>
                        <div class="col">
                            <label for="image" class="form-label">Main image @unless ($product->image)<span class="text-danger">*</span>@endunless</label>
                            <input type="file" name="image" id="image" class="form-control @error('image') is-invalid @enderror" accept="{{ UploadRules::imageAccept() }}" data-preview="#imagePreview">
                            @error('image')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text">{{ strtoupper(UploadRules::imageTypes()) }}, max {{ UploadRules::imageMaxKb() }} KB. Recommended 1200x900. Required before submitting.</div>
                        </div>
                    </div>

                    <label for="gallery" class="form-label">Gallery images</label>
                    <input type="file" name="gallery[]" id="gallery" multiple class="form-control @error('gallery') is-invalid @enderror @error('gallery.*') is-invalid @enderror" accept="{{ UploadRules::imageAccept() }}">
                    @error('gallery')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    @foreach ($errors->get('gallery.*') as $messages)<div class="invalid-feedback d-block">{{ $messages[0] }}</div>@endforeach
                    <div class="form-text mb-2">Up to {{ $galleryMax }} images in total.</div>

                    @if ($product->exists && $product->images->isNotEmpty())
                        <div class="gallery-grid mt-2">
                            @foreach ($product->images as $image)
                                <div class="item">
                                    <img src="{{ $image->thumbUrl() }}" alt="" loading="lazy">
                                    <button type="submit" form="del-image-{{ $image->id }}" class="btn btn-sm btn-danger btn-icon position-absolute top-0 end-0 m-1" aria-label="Remove image"><i class="bi bi-x"></i></button>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            {{-- File --}}
            <div class="card mb-4">
                <div class="card-header">Downloadable file</div>
                <div class="card-body">
                    @if ($product->hasFile())
                        <div class="d-flex align-items-center gap-2 p-2 bg-light rounded-3 mb-3 flex-wrap">
                            <i class="bi bi-file-earmark-zip fs-4 text-primary"></i>
                            <div class="min-w-0 flex-grow-1">
                                <div class="fw-semibold text-truncate">{{ $product->file_name }}</div>
                                <div class="small text-muted-2">{{ $product->fileSizeHuman() }} &middot; {{ strtoupper($product->file_type) }}</div>
                            </div>
                            <a href="{{ $isAdmin ? route('admin.products.file', $product) : route('publisher.products.file', $product) }}" class="btn btn-sm btn-light"><i class="bi bi-download"></i> Download</a>
                        </div>
                    @endif
                    <label for="file" class="form-label">{{ $product->hasFile() ? 'Replace file' : 'Upload file' }} @unless ($product->hasFile())<span class="text-danger">*</span>@endunless</label>
                    <input type="file" name="file" id="file" class="form-control @error('file') is-invalid @enderror" accept="{{ UploadRules::fileAccept() }}" data-max-mb="{{ $fileMax }}">
                    @error('file')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div class="form-text">Allowed: {{ setting('upload_file_types') }}. Max {{ $fileMax }} MB. Files are stored privately and only delivered through secure download links.</div>
                </div>
            </div>

            {{-- SEO --}}
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    SEO &amp; social sharing
                    <button class="btn btn-sm btn-light" type="button" data-bs-toggle="collapse" data-bs-target="#seoFields" aria-expanded="{{ $errors->hasAny(['seo_title', 'seo_description', 'canonical_url', 'og_title', 'og_description', 'og_image']) ? 'true' : 'false' }}">Show / hide</button>
                </div>
                <div class="collapse {{ $errors->hasAny(['seo_title', 'seo_description', 'seo_keywords', 'focus_keyword', 'canonical_url', 'og_title', 'og_description', 'og_image']) ? 'show' : '' }}" id="seoFields">
                    <div class="card-body">
                        <p class="small text-muted-2">Optional. When empty, the product name and short description are used automatically.</p>
                        <x-input name="seo_title" label="SEO title" :value="$product->seo_title" maxlength="255" data-counter="60" />
                        <x-textarea name="seo_description" label="SEO description" :value="$product->seo_description" rows="2" maxlength="500" data-counter="160" />
                        <div class="row">
                            <div class="col-md-8"><x-input name="seo_keywords" label="SEO keywords" :value="$product->seo_keywords" maxlength="500" /></div>
                            <div class="col-md-4"><x-input name="focus_keyword" label="Focus keyword" :value="$product->focus_keyword" maxlength="100" /></div>
                        </div>
                        <x-input name="canonical_url" type="url" label="Canonical URL" :value="$product->canonical_url" maxlength="255" help="Only set this if the product's main page lives elsewhere." />
                        <div class="row">
                            <div class="col-md-6"><x-input name="og_title" label="Open Graph title" :value="$product->og_title" maxlength="255" /></div>
                            <div class="col-md-6"><x-input name="og_description" label="Open Graph description" :value="$product->og_description" maxlength="500" /></div>
                        </div>
                        <label for="og_image" class="form-label">Open Graph image</label>
                        <input type="file" name="og_image" id="og_image" class="form-control @error('og_image') is-invalid @enderror" accept="{{ UploadRules::imageAccept() }}">
                        @error('og_image')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">1200x630 recommended. Defaults to the main image.</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="sticky-xl-top" style="top: 80px">
                {{-- Pricing --}}
                <div class="card mb-4">
                    <div class="card-header">Pricing</div>
                    <div class="card-body">
                        <x-toggle name="is_free" label="This is a free product" :checked="$product->is_free" data-free-toggle />
                        <div data-paid-only>
                            <x-input name="price" type="number" step="0.01" min="0" label="Regular price" :value="$product->is_free ? null : $product->price" :prepend="currency_code()" />
                            <x-input name="sale_price" type="number" step="0.01" min="0" label="Sale price (optional)" :value="$product->sale_price" :prepend="currency_code()" help="Must be lower than the regular price." />
                            <x-toggle name="whatsapp_enabled" label="Allow ordering via WhatsApp" :checked="$product->whatsapp_enabled ?? true" />
                        </div>
                    </div>
                </div>

                @if ($isAdmin)
                    <div class="card mb-4">
                        <div class="card-header">Ownership &amp; visibility</div>
                        <div class="card-body">
                            <x-select name="user_id" label="Owner / publisher" :options="$owners->mapWithKeys(fn ($o) => [$o->id => $o->name.' ('.$o->role?->name.')'])->all()" :value="$product->user_id ?? auth()->id()" required />
                            <x-toggle name="is_featured" label="Featured product" :checked="$product->is_featured" />
                            @if ($product->exists)
                                <div class="small">Current status: <x-status-badge :status="$product->status" /> <x-status-badge :status="$product->approval_status" /></div>
                            @endif
                        </div>
                    </div>
                @endif

                <div class="card">
                    <div class="card-body d-grid gap-2">
                        @if ($isAdmin)
                            @can('approve_products')
                                <button type="submit" name="action" value="publish" class="btn btn-primary" data-loading="Saving..."><i class="bi bi-rocket-takeoff"></i> Save &amp; publish</button>
                            @endcan
                            <button type="submit" name="action" value="save" class="btn btn-outline-primary" data-loading="Saving..."><i class="bi bi-save"></i> Save{{ $product->exists ? ' (keep status)' : '' }}</button>
                            <button type="submit" name="action" value="draft" class="btn btn-light">Save as draft (hidden)</button>
                        @else
                            <button type="submit" name="action" value="submit" class="btn btn-primary" data-loading="Saving..."><i class="bi bi-send"></i> Save &amp; submit for review</button>
                            <button type="submit" name="action" value="draft" class="btn btn-outline-secondary" data-loading="Saving..."><i class="bi bi-save"></i> Save as draft</button>
                            <div class="small text-muted-2"><i class="bi bi-shield-check"></i> Products go live only after admin approval.</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

{{-- Gallery delete forms live outside the main form (forms cannot be nested). --}}
@if ($product->exists)
    @foreach ($product->images as $image)
        <form id="del-image-{{ $image->id }}" method="POST" action="{{ route($destroyImageRoute, [$product, $image]) }}" data-confirm="Remove this gallery image?" class="d-none">
            @csrf @method('DELETE')
        </form>
    @endforeach
@endif
