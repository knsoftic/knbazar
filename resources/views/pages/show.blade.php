@extends('layouts.app')

@section('page-header')
    <section class="page-header">
        <div class="container">
            <nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li><li class="breadcrumb-item active" aria-current="page">{{ $page->title }}</li></ol></nav>
            <h1 class="mb-0">{{ $page->title }}</h1>
        </div>
    </section>
@endsection

@section('content')
    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-lg-9">
                <article class="card">
                    <div class="card-body p-4 p-lg-5 prose">
                        {{-- Pages are authored by trusted admins (manage_pages); Markdown with HTML allowed. --}}
                        {!! markdown($page->content, true) !!}
                    </div>
                </article>
                <p class="small text-muted-2 mt-3">Last updated {{ $page->updated_at->format('F j, Y') }}</p>
            </div>
        </div>
    </div>
@endsection
