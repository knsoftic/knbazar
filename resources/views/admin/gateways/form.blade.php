@extends('layouts.admin')

@section('title', $gateway->exists ? 'Edit gateway' : 'Add gateway')

@php
    $rows = old('details', $gateway->details ?: [['label' => '', 'value' => '']]);
    $currencies = collect(config('marketplace.currencies'))->mapWithKeys(fn ($symbol, $code) => [$code => "$code ($symbol)"])->all();
@endphp

@section('content')
    <div class="panel-title">
        <div>
            <nav aria-label="breadcrumb"><ol class="breadcrumb mb-1"><li class="breadcrumb-item"><a href="{{ route('admin.gateways.index') }}">Payment gateways</a></li><li class="breadcrumb-item active">{{ $gateway->exists ? $gateway->name : 'New' }}</li></ol></nav>
            <h1>{{ $gateway->exists ? 'Edit '.$gateway->name : 'Add payment gateway' }}</h1>
        </div>
    </div>

    <form method="POST" action="{{ $gateway->exists ? route('admin.gateways.update', $gateway) : route('admin.gateways.store') }}" enctype="multipart/form-data" novalidate>
        @csrf
        @if ($gateway->exists) @method('PUT') @endif
        <div class="row g-4">
            <div class="col-xl-8">
                <div class="card mb-4">
                    <div class="card-header">Gateway</div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6"><x-input name="name" label="Gateway name" :value="$gateway->name" required maxlength="100" placeholder="e.g. JazzCash" /></div>
                            <div class="col-md-3"><x-select name="currency" label="Currency" :options="$currencies" :value="$gateway->currency" required /></div>
                            <div class="col-md-3"><x-select name="driver" label="Driver" :options="$drivers" :value="$gateway->driver" required /></div>
                        </div>
                        <x-input name="description" label="Short description" :value="$gateway->description" maxlength="500" placeholder="Shown under the gateway name at checkout" />
                        <x-textarea name="instructions" label="Payment instructions" :value="$gateway->instructions" rows="5" maxlength="5000" help="Shown to the customer after checkout. Plain text; line breaks are kept." />
                    </div>
                </div>

                <div class="card" data-repeater>
                    <div class="card-header d-flex justify-content-between align-items-center">
                        Account fields
                        <button type="button" class="btn btn-sm btn-soft" data-repeater-add><i class="bi bi-plus"></i> Add field</button>
                    </div>
                    <div class="card-body">
                        <p class="small text-muted-2">Add any fields customers need: Account Title, Account Number, IBAN, Bank Name, Wallet Address, Network... Empty rows are ignored.</p>
                        @error('details')<div class="alert alert-danger py-2">{{ $message }}</div>@enderror
                        <div data-repeater-list>
                            @foreach ($rows as $i => $row)
                                <div class="row g-2 mb-2 align-items-start" data-repeater-item>
                                    <div class="col-5"><input type="text" name="details[{{ $i }}][label]" value="{{ $row['label'] ?? '' }}" class="form-control @error('details.'.$i.'.label') is-invalid @enderror" placeholder="Label" maxlength="60" aria-label="Field label">@error('details.'.$i.'.label')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                                    <div class="col-6"><input type="text" name="details[{{ $i }}][value]" value="{{ $row['value'] ?? '' }}" class="form-control" placeholder="Value" maxlength="255" aria-label="Field value"></div>
                                    <div class="col-1"><button type="button" class="btn btn-light text-danger w-100" data-repeater-remove aria-label="Remove field"><i class="bi bi-x-lg"></i></button></div>
                                </div>
                            @endforeach
                        </div>
                        <template>
                            <div class="row g-2 mb-2" data-repeater-item>
                                <div class="col-5"><input type="text" name="details[__INDEX__][label]" class="form-control" placeholder="Label" maxlength="60" aria-label="Field label"></div>
                                <div class="col-6"><input type="text" name="details[__INDEX__][value]" class="form-control" placeholder="Value" maxlength="255" aria-label="Field value"></div>
                                <div class="col-1"><button type="button" class="btn btn-light text-danger w-100" data-repeater-remove aria-label="Remove field"><i class="bi bi-x-lg"></i></button></div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
            <div class="col-xl-4">
                <div class="card mb-4">
                    <div class="card-header">Logo</div>
                    <div class="card-body text-center">
                        <img id="logoPreview" src="{{ $gateway->logoUrl() ?? asset('assets/img/placeholder.svg') }}" class="upload-preview square mb-2" style="object-fit: contain" alt="Logo preview">
                        <input type="file" name="logo" class="form-control @error('logo') is-invalid @enderror" accept="{{ \App\Support\UploadRules::imageAccept() }}" data-preview="#logoPreview" aria-label="Logo">
                        @error('logo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        @if ($gateway->logo)<div class="form-check mt-2 text-start"><input class="form-check-input" type="checkbox" name="remove_logo" value="1" id="removeLogo"><label class="form-check-label small" for="removeLogo">Remove logo</label></div>@endif
                    </div>
                </div>
                <div class="card">
                    <div class="card-body">
                        <x-toggle name="is_active" label="Active at checkout" :checked="$gateway->is_active" />
                        <x-toggle name="requires_proof" label="Require payment screenshot" :checked="$gateway->requires_proof" />
                        <x-input name="sort_order" type="number" min="0" label="Sort order" :value="$gateway->sort_order ?? 0" />
                        <button type="submit" class="btn btn-primary w-100" data-loading="Saving...">Save gateway</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection
