@extends('layouts.publisher')

@section('title', 'Add product')

@section('content')
    <div class="panel-title">
        <div>
            <nav aria-label="breadcrumb"><ol class="breadcrumb mb-1"><li class="breadcrumb-item"><a href="{{ route('publisher.products.index') }}">My products</a></li><li class="breadcrumb-item active">Add product</li></ol></nav>
            <h1>Add a new product</h1>
            <p>Save as a draft anytime. Submit it for review when it's ready.</p>
        </div>
    </div>

    @include('products._form', [
        'action' => route('publisher.products.store'),
        'method' => 'POST',
        'isAdmin' => false,
        'destroyImageRoute' => 'publisher.products.images.destroy',
    ])
@endsection
