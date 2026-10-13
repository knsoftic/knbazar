@extends('layouts.admin')

@section('title', $product->exists ? 'Edit product' : 'Add product')

@section('content')
    <div class="panel-title">
        <div>
            <nav aria-label="breadcrumb"><ol class="breadcrumb mb-1"><li class="breadcrumb-item"><a href="{{ route('admin.products.index') }}">Products</a></li><li class="breadcrumb-item active">{{ $product->exists ? '#'.$product->id : 'New' }}</li></ol></nav>
            <h1>{{ $product->exists ? 'Edit: '.$product->name : 'Add product' }}</h1>
            <p>Admin products can be published directly. Use "Save as draft" to keep it hidden.</p>
        </div>
        @if ($product->exists)<a href="{{ route('admin.products.show', $product) }}" class="btn btn-light"><i class="bi bi-eye"></i> Review page</a>@endif
    </div>

    @include('products._form', [
        'action' => $product->exists ? route('admin.products.update', $product) : route('admin.products.store'),
        'method' => $product->exists ? 'PUT' : 'POST',
        'isAdmin' => true,
        'destroyImageRoute' => 'admin.products.images.destroy',
    ])
@endsection
