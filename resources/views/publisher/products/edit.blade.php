@extends('layouts.publisher')

@section('title', 'Edit '.$product->name)

@section('content')
    <div class="panel-title">
        <div>
            <nav aria-label="breadcrumb"><ol class="breadcrumb mb-1"><li class="breadcrumb-item"><a href="{{ route('publisher.products.index') }}">My products</a></li><li class="breadcrumb-item active text-truncate" style="max-width: 50vw">{{ $product->name }}</li></ol></nav>
            <h1>Edit product</h1>
            <p class="d-flex gap-2 align-items-center flex-wrap">Status: <x-status-badge :status="$product->status" /> <x-status-badge :status="$product->approval_status" /></p>
        </div>
        <a href="{{ route('publisher.products.show', $product) }}" class="btn btn-light"><i class="bi bi-eye"></i> View details</a>
    </div>

    @include('products._form', [
        'action' => route('publisher.products.update', $product),
        'method' => 'PUT',
        'isAdmin' => false,
        'destroyImageRoute' => 'publisher.products.images.destroy',
    ])
@endsection
