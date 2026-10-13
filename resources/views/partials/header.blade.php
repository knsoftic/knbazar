@php $user = auth()->user(); @endphp
<header class="site-header">
    <nav class="navbar navbar-expand-lg" aria-label="Main navigation">
        <div class="container">
            @include('partials.brand')

            <div class="d-flex align-items-center gap-3 order-lg-3 ms-auto ms-lg-3">
                <a href="{{ route('cart.index') }}" class="header-icon" aria-label="Cart ({{ $cartCount }} items)">
                    <i class="bi bi-bag"></i>
                    <span class="count {{ $cartCount ? '' : 'd-none' }}" data-cart-count>{{ $cartCount }}</span>
                </a>

                @auth
                    <div class="dropdown">
                        <a href="#" class="header-icon" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Notifications">
                            <i class="bi bi-bell"></i>
                            @if ($unreadNotifications)<span class="count">{{ $unreadNotifications > 9 ? '9+' : $unreadNotifications }}</span>@endif
                        </a>
                        <div class="dropdown-menu dropdown-menu-end notif-menu">
                            @include('partials.notification-list', ['items' => $latestNotifications])
                        </div>
                    </div>

                    <div class="dropdown">
                        <a href="#" class="d-flex align-items-center gap-2 text-decoration-none" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Account menu">
                            @if ($user->avatarUrl())
                                <img src="{{ $user->avatarUrl() }}" class="avatar" alt="">
                            @else
                                <span class="avatar">{{ $user->initials() }}</span>
                            @endif
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li class="px-3 py-2">
                                <div class="fw-semibold text-ink text-truncate" style="max-width: 220px">{{ $user->name }}</div>
                                <div class="small text-muted-2 text-truncate" style="max-width: 220px">{{ $user->email }}</div>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            @if ($user->isAdmin())
                                <li><a class="dropdown-item" href="{{ route('admin.dashboard') }}"><i class="bi bi-speedometer2 me-2"></i>Admin panel</a></li>
                            @endif
                            @if ($user->isPublisher())
                                <li><a class="dropdown-item" href="{{ route('publisher.dashboard') }}"><i class="bi bi-shop me-2"></i>Publisher dashboard</a></li>
                            @endif
                            <li><a class="dropdown-item" href="{{ route('account.index', $user->isAdmin() ? ['customer' => 1] : []) }}"><i class="bi bi-person me-2"></i>My account</a></li>
                            <li><a class="dropdown-item" href="{{ route('orders.index') }}"><i class="bi bi-receipt me-2"></i>My orders</a></li>
                            <li><a class="dropdown-item" href="{{ route('downloads.index') }}"><i class="bi bi-download me-2"></i>Downloads</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="dropdown-item text-danger"><i class="bi bi-box-arrow-right me-2"></i>Sign out</button>
                                </form>
                            </li>
                        </ul>
                    </div>
                @endauth

                <button class="navbar-toggler border-0 px-1" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
            </div>

            <div class="collapse navbar-collapse order-lg-2" id="mainNav">
                <form action="{{ route('search') }}" method="GET" class="header-search my-2 my-lg-0 mx-lg-3" role="search" style="max-width: 420px">
                    <i class="bi bi-search"></i>
                    <input type="search" name="q" class="form-control" placeholder="Search themes, plugins, templates..." value="{{ request()->routeIs('search') ? request('q') : '' }}" aria-label="Search products" maxlength="100">
                </form>

                <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-1">
                    <li class="nav-item"><a class="nav-link {{ request()->routeIs('products.index') && ! request('type') ? 'active' : '' }}" href="{{ route('products.index') }}">Products</a></li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle {{ request()->routeIs('categories.*') ? 'active' : '' }}" href="{{ route('categories.index') }}" role="button" data-bs-toggle="dropdown" aria-expanded="false">Categories</a>
                        <ul class="dropdown-menu mega-categories">
                            @foreach ($navCategories as $navCategory)
                                <li>
                                    <a class="dropdown-item d-flex justify-content-between align-items-center gap-3" href="{{ $navCategory->url() }}">
                                        <span><i class="bi {{ $navCategory->icon ?: 'bi-folder2' }} me-2 text-primary"></i>{{ $navCategory->name }}</span>
                                        <span class="badge badge-soft-secondary">{{ $navCategory->products_count }}</span>
                                    </a>
                                </li>
                            @endforeach
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item fw-semibold" href="{{ route('categories.index') }}">All categories</a></li>
                        </ul>
                    </li>
                    <li class="nav-item"><a class="nav-link {{ request('type') === 'free' ? 'active' : '' }}" href="{{ route('products.index', ['type' => 'free']) }}">Free</a></li>
                    @if (setting('publisher_registration_enabled') && ! $user?->isPublisher() && ! $user?->isAdmin())
                        <li class="nav-item"><a class="nav-link" href="{{ route('register.publisher') }}">Sell</a></li>
                    @endif
                    @guest
                        <li class="nav-item"><a class="nav-link" href="{{ route('login') }}">Sign in</a></li>
                        @if (setting('registration_enabled'))
                            <li class="nav-item ms-lg-1"><a class="btn btn-primary btn-sm px-3 my-2 my-lg-0" href="{{ route('register') }}">Get started</a></li>
                        @endif
                    @endguest
                </ul>
            </div>
        </div>
    </nav>
</header>
