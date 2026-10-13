@extends('errors.layout')

@section('code', '404')
@section('title', 'Page not found')
@section('message', 'The page or product you are looking for does not exist, was moved, or is no longer available.')
@section('actions')
    <a href="{{ url('/products') }}" class="btn btn-outline-primary"><i class="bi bi-grid"></i> Browse products</a>
@endsection
