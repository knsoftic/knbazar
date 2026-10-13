@php
    $socials = [
        'social_facebook' => 'bi-facebook', 'social_twitter' => 'bi-twitter-x', 'social_instagram' => 'bi-instagram',
        'social_youtube' => 'bi-youtube', 'social_linkedin' => 'bi-linkedin', 'social_tiktok' => 'bi-tiktok', 'social_github' => 'bi-github',
    ];
    $whatsappChat = app(\App\Services\WhatsAppOrderService::class)->chatUrl();
@endphp
<footer class="site-footer">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-4">
                @include('partials.brand')
                <p class="mt-3 mb-3">{{ setting('footer_text') }}</p>
                <div class="social">
                    @foreach ($socials as $key => $icon)
                        @if (setting($key))
                            <a href="{{ setting($key) }}" target="_blank" rel="noopener" aria-label="{{ ucfirst(str_replace('social_', '', $key)) }}"><i class="bi {{ $icon }}"></i></a>
                        @endif
                    @endforeach
                    @if ($whatsappChat && setting('whatsapp_enabled'))
                        <a href="{{ $whatsappChat }}" target="_blank" rel="noopener" aria-label="WhatsApp"><i class="bi bi-whatsapp"></i></a>
                    @endif
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <h6>Marketplace</h6>
                <ul>
                    <li><a href="{{ route('products.index') }}">All products</a></li>
                    <li><a href="{{ route('products.index', ['type' => 'free']) }}">Free products</a></li>
                    <li><a href="{{ route('products.index', ['featured' => 1]) }}">Featured</a></li>
                    <li><a href="{{ route('products.index', ['sort' => 'popular']) }}">Popular</a></li>
                    <li><a href="{{ route('categories.index') }}">Categories</a></li>
                </ul>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <h6>Categories</h6>
                <ul>
                    @foreach ($navCategories->take(6) as $footerCategory)
                        <li><a href="{{ $footerCategory->url() }}">{{ $footerCategory->name }}</a></li>
                    @endforeach
                </ul>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <h6>Company</h6>
                <ul>
                    @foreach ($footerPages as $footerPage)
                        <li><a href="{{ $footerPage->slug === 'contact' ? route('contact.show') : $footerPage->url() }}">{{ $footerPage->title }}</a></li>
                    @endforeach
                    @if ($footerPages->where('slug', 'contact')->isEmpty())
                        <li><a href="{{ route('contact.show') }}">Contact</a></li>
                    @endif
                </ul>
            </div>
            <div class="col-6 col-md-12 col-lg-2">
                <h6>Get in touch</h6>
                <ul>
                    @if (setting('contact_email'))<li><i class="bi bi-envelope me-1"></i> <a href="mailto:{{ setting('contact_email') }}">{{ setting('contact_email') }}</a></li>@endif
                    @if (setting('contact_phone'))<li><i class="bi bi-telephone me-1"></i> {{ setting('contact_phone') }}</li>@endif
                    @if (setting('contact_address'))<li><i class="bi bi-geo-alt me-1"></i> {{ setting('contact_address') }}</li>@endif
                </ul>
            </div>
        </div>
        <div class="footer-bottom d-flex flex-column flex-md-row justify-content-between gap-2">
            <span>&copy; {{ date('Y') }} {{ setting('site_name') }}. {{ setting('copyright_text') }}</span>
            <span class="d-flex gap-3">
                @if (setting('publisher_registration_enabled'))<a href="{{ route('register.publisher') }}">Become a publisher</a>@endif
                @if (setting('seo_sitemap_enabled'))<a href="{{ route('sitemap') }}">Sitemap</a>@endif
            </span>
        </div>
    </div>
</footer>
