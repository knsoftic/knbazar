@extends('layouts.app')

@section('page-header')
    <section class="page-header">
        <div class="container">
            <h1 class="mb-0">Your cart</h1>
        </div>
    </section>
@endsection

@section('content')
    <div class="container py-4">
        @if ($lines->isEmpty())
            <div class="card">
                <x-empty-state icon="bi-bag" title="Your cart is empty" text="Browse the marketplace and add products you like.">
                    <a href="{{ route('products.index') }}" class="btn btn-primary">Browse products</a>
                </x-empty-state>
            </div>
        @else
            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="table-responsive-md">
                            <table class="table table-stack align-middle">
                                <thead>
                                    <tr><th>Product</th><th>Price</th>@if ($allowQuantity)<th>Qty</th>@endif<th class="text-end">Total</th><th></th></tr>
                                </thead>
                                <tbody>
                                    @foreach ($lines as $line)
                                        @php $product = $line['product']; @endphp
                                        <tr>
                                            <td data-label="">
                                                <div class="d-flex gap-3 align-items-center text-start">
                                                    <img src="{{ $product->thumbnailUrl() }}" class="thumb" alt="" loading="lazy">
                                                    <div class="min-w-0">
                                                        <a href="{{ $product->url() }}" class="fw-semibold text-ink d-block text-truncate">{{ $product->name }}</a>
                                                        <span class="small text-muted-2">{{ $product->category?->name }} &middot; {{ $product->publisherName() }}</span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><x-price :product="$product" /></td>
                                            @if ($allowQuantity)
                                                <td>
                                                    <form method="POST" action="{{ route('cart.update', $product->id) }}" class="d-flex gap-1">
                                                        @csrf @method('PATCH')
                                                        <input type="number" name="quantity" value="{{ $line['quantity'] }}" min="1" max="{{ $maxQuantity }}" class="form-control form-control-sm" style="width: 70px" aria-label="Quantity">
                                                        <button class="btn btn-sm btn-light" type="submit" aria-label="Update quantity"><i class="bi bi-arrow-repeat"></i></button>
                                                    </form>
                                                </td>
                                            @endif
                                            <td class="text-end fw-semibold">{{ money($line['total']) }}</td>
                                            <td class="text-end td-actions">
                                                <form method="POST" action="{{ route('cart.remove', $product->id) }}">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-light text-danger" aria-label="Remove {{ $product->name }}"><i class="bi bi-trash"></i></button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between mt-3 flex-wrap gap-2">
                        <a href="{{ route('products.index') }}" class="btn btn-light"><i class="bi bi-arrow-left"></i> Continue shopping</a>
                        <form method="POST" action="{{ route('cart.clear') }}" data-confirm="Remove all items from your cart?">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-outline-danger">Clear cart</button>
                        </form>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">Order summary</div>
                        <div class="card-body">
                            <div class="summary-line"><span>Subtotal ({{ $totals['count'] }} {{ \Illuminate\Support\Str::plural('item', $totals['count']) }})</span><span>{{ money($totals['subtotal']) }}</span></div>
                            @if ($totals['discount'] > 0)
                                <div class="summary-line text-success"><span>Discount</span><span>-{{ money($totals['discount']) }}</span></div>
                            @endif
                            <div class="summary-line summary-total"><span>Total</span><span>{{ money($totals['total']) }}</span></div>
                            <a href="{{ route('checkout.index') }}" class="btn btn-primary btn-lg w-100 mt-3">Proceed to checkout <i class="bi bi-arrow-right"></i></a>
                            @guest<p class="small text-muted-2 text-center mt-2 mb-0">You'll sign in or create an account at checkout.</p>@endguest
                            <div class="small text-muted-2 text-center mt-3"><i class="bi bi-shield-lock"></i> Secure checkout &middot; Instant access after payment verification</div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection
