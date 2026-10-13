@extends('layouts.admin')

@section('title', config("settings.groups.$group.label").' settings')

@php
    use App\Support\UploadRules;
    $currencies = collect(config('marketplace.currencies'))->mapWithKeys(fn ($symbol, $code) => [$code => "$code ($symbol)"])->all();
    $timezones = collect(timezone_identifiers_list())->mapWithKeys(fn ($tz) => [$tz => $tz])->all();
@endphp

@section('content')
    <div class="panel-title">
        <div><h1>{{ $group === 'seo' ? 'SEO settings' : 'Settings' }}</h1><p>{{ $group === 'seo' ? 'Default meta tags, robots and sitemap configuration.' : 'Website configuration. Nothing here is hard-coded.' }}</p></div>
    </div>

    <div class="row g-4">
        @if (count($groups) > 1)
            <div class="col-lg-3">
                <div class="card">
                    <div class="list-group list-group-flush account-nav p-2">
                        @foreach ($groups as $key => $meta)
                            <a href="{{ $key === 'seo' ? route('admin.seo.edit') : route('admin.settings.edit', $key) }}" class="list-group-item list-group-item-action {{ $key === $group ? 'active' : '' }}"><i class="bi {{ $meta['icon'] }}"></i> {{ $meta['label'] }}</a>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif
        <div class="{{ count($groups) > 1 ? 'col-lg-9' : 'col-12' }}">
            <form method="POST" action="{{ route('admin.settings.update', $group) }}" enctype="multipart/form-data" novalidate>
                @csrf @method('PUT')
                <div class="card">
                    <div class="card-header"><i class="bi {{ config("settings.groups.$group.icon") }} me-1"></i> {{ config("settings.groups.$group.label") }}</div>
                    <div class="card-body">
                        @if ($group === 'whatsapp')
                            @php $wa = app(\App\Services\WhatsAppOrderService::class); @endphp
                            <div class="alert alert-info small">
                                <i class="bi bi-whatsapp"></i> Current number: <strong>{{ $wa->phoneNumber() ?: 'not set' }}</strong>.
                                Buttons appear on paid products when ordering is enabled globally and on the product.
                                @if ($wa->phoneNumber())<a href="{{ $wa->chatUrl('Test message from '.setting('site_name')) }}" target="_blank" rel="noopener">Test link</a>@endif
                            </div>
                        @endif
                        @if ($group === 'seo')
                            <div class="alert alert-info small">
                                Sitemap: <a href="{{ route('sitemap') }}" target="_blank">{{ route('sitemap') }}</a> &middot; Robots: <a href="{{ route('robots') }}" target="_blank">{{ route('robots') }}</a>.
                                Product, category and page SEO is edited on each item.
                            </div>
                        @endif

                        @foreach ($definitions as $key => $definition)
                            @php $value = $settings->get($key); $help = $definition['help'] ?? null; @endphp
                            @switch($definition['type'])
                                @case('bool')
                                    <x-toggle :name="$key" :label="$definition['label']" :checked="(bool) $value" :help="$help" />
                                    @break
                                @case('text')
                                    <x-textarea :name="$key" :label="$definition['label']" :value="$value" rows="3" :help="$help" />
                                    @break
                                @case('int')
                                    <x-input :name="$key" type="number" :label="$definition['label']" :value="$value" :help="$help" />
                                    @break
                                @case('email')
                                    <x-input :name="$key" type="email" :label="$definition['label']" :value="$value" :help="$help" />
                                    @break
                                @case('url')
                                    <x-input :name="$key" type="url" :label="$definition['label']" :value="$value" placeholder="https://" :help="$help" />
                                    @break
                                @case('select')
                                    <x-select :name="$key" :label="$definition['label']" :options="$definition['options'] === 'currencies' ? $currencies : $definition['options']" :value="$value" :help="$help" />
                                    @break
                                @case('timezone')
                                    <x-select :name="$key" :label="$definition['label']" :options="$timezones" :value="$value" :help="$help" />
                                    @break
                                @case('image')
                                    <div class="mb-3">
                                        <label for="f_{{ $key }}" class="form-label">{{ $definition['label'] }}</label>
                                        <div class="d-flex gap-3 align-items-center flex-wrap">
                                            <img id="prev_{{ $key }}" src="{{ storage_url($value) ?? asset('assets/img/placeholder.svg') }}" class="upload-preview" style="object-fit: contain" alt="">
                                            <div class="flex-grow-1">
                                                <input type="file" name="{{ $key }}" id="f_{{ $key }}" class="form-control @error($key) is-invalid @enderror" accept="{{ ($definition['accept'] ?? null) === 'png,ico' ? '.png,.ico' : UploadRules::imageAccept() }}" data-preview="#prev_{{ $key }}">
                                                @error($key)<div class="invalid-feedback">{{ $message }}</div>@enderror
                                                @if ($help)<div class="form-text">{{ $help }}</div>@endif
                                                @if ($value)<div class="form-check mt-1"><input class="form-check-input" type="checkbox" name="remove_{{ $key }}" value="1" id="rm_{{ $key }}"><label class="form-check-label small" for="rm_{{ $key }}">Remove</label></div>@endif
                                            </div>
                                        </div>
                                    </div>
                                    @break
                                @default
                                    <x-input :name="$key" :label="$definition['label']" :value="$value" :help="$help" />
                            @endswitch
                        @endforeach
                    </div>
                    <div class="card-footer d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary" data-loading="Saving..."><i class="bi bi-save"></i> Save settings</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
