@extends('layouts.app')

@section('page-header')
    <section class="page-header">
        <div class="container">
            <nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li><li class="breadcrumb-item active" aria-current="page">Contact</li></ol></nav>
            <h1 class="mb-0">{{ $page?->title ?? 'Contact us' }}</h1>
        </div>
    </section>
@endsection

@section('content')
    <div class="container py-4">
        <div class="row g-4">
            <div class="col-lg-7">
                <div class="card">
                    <div class="card-body p-4">
                        @if ($page?->content)<div class="prose mb-4">{!! markdown($page->content, true) !!}</div>@endif
                        <form method="POST" action="{{ route('contact.store') }}" novalidate>
                            @csrf
                            <div class="row">
                                <div class="col-md-6"><x-input name="name" label="Your name" :value="auth()->user()?->name" required maxlength="100" autocomplete="name" /></div>
                                <div class="col-md-6"><x-input name="email" type="email" label="Email" :value="auth()->user()?->email" required maxlength="150" autocomplete="email" /></div>
                                <div class="col-md-6"><x-input name="phone" label="Phone / WhatsApp" :value="auth()->user()?->phone" maxlength="40" autocomplete="tel" /></div>
                                <div class="col-md-6"><x-input name="subject" label="Subject" required maxlength="150" /></div>
                            </div>
                            <x-textarea name="message" label="Message" rows="6" required maxlength="5000" />
                            {{-- Honeypot field for bots --}}
                            <div class="d-none" aria-hidden="true"><label for="website">Website</label><input type="text" name="website" id="website" tabindex="-1" autocomplete="off"></div>
                            <button type="submit" class="btn btn-primary" data-loading="Sending..."><i class="bi bi-send"></i> Send message</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="card">
                    <div class="card-body p-4">
                        <h2 class="h5 mb-3">Other ways to reach us</h2>
                        <ul class="list-unstyled mb-0 d-grid gap-3">
                            @if (setting('contact_email'))<li class="d-flex gap-3"><span class="stat-card p-0 border-0 shadow-none"><span class="icon icon-primary"><i class="bi bi-envelope"></i></span></span><div><div class="small text-muted-2">Email</div><a href="mailto:{{ setting('contact_email') }}">{{ setting('contact_email') }}</a></div></li>@endif
                            @if (setting('contact_phone'))<li class="d-flex gap-3"><span class="stat-card p-0 border-0 shadow-none"><span class="icon icon-info"><i class="bi bi-telephone"></i></span></span><div><div class="small text-muted-2">Phone</div>{{ setting('contact_phone') }}</div></li>@endif
                            @if (setting('contact_address'))<li class="d-flex gap-3"><span class="stat-card p-0 border-0 shadow-none"><span class="icon icon-dark"><i class="bi bi-geo-alt"></i></span></span><div><div class="small text-muted-2">Address</div>{{ setting('contact_address') }}</div></li>@endif
                        </ul>
                        @if ($whatsappUrl)
                            <a href="{{ $whatsappUrl }}" class="btn btn-whatsapp w-100 mt-4" target="_blank" rel="noopener"><i class="bi bi-whatsapp"></i> Chat on WhatsApp</a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
