<?php

namespace App\Providers;

use App\Enums\ApprovalStatus;
use App\Enums\PaymentRecordStatus;
use App\Models\Category;
use App\Models\ContactMessage;
use App\Models\Page;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Services\CartService;
use App\Services\CatalogCache;
use App\Services\SettingService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Throwable;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(SettingService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();
        Model::preventLazyLoading(! $this->app->isProduction());

        $this->applyRuntimeSettings();
        $this->registerGates();
        $this->registerRateLimiters();
        $this->registerViewComposers();
    }

    /**
     * Apply admin-managed settings that affect framework configuration.
     */
    protected function applyRuntimeSettings(): void
    {
        try {
            $settings = $this->app->make(SettingService::class);

            config([
                'app.name' => $settings->get('site_name', config('app.name')),
                'mail.from.name' => $settings->get('site_name', config('mail.from.name')),
            ]);

            $timezone = (string) $settings->get('timezone');
            if ($timezone && in_array($timezone, timezone_identifiers_list(), true) && ! $this->app->runningUnitTests()) {
                config(['app.timezone' => $timezone]);
                date_default_timezone_set($timezone);
            }
        } catch (Throwable) {
            // Settings table not available yet (fresh install).
        }
    }

    protected function registerGates(): void
    {
        // Super Admins can do everything.
        Gate::before(fn (User $user) => $user->isSuperAdmin() ? true : null);

        foreach (array_keys(config('permissions', [])) as $permission) {
            Gate::define($permission, fn (User $user) => $user->hasPermission($permission));
        }

        // Only Super Admins (handled by Gate::before) may manage admin accounts.
        Gate::define('manage_admins', fn (User $user) => false);

        Gate::define('access_admin', fn (User $user) => $user->isAdmin());

        // Reviewers may inspect products without being allowed to edit them.
        Gate::define('view_products', fn (User $user) => $user->hasPermission('manage_products') || $user->hasPermission('approve_products'));
    }

    protected function registerRateLimiters(): void
    {
        $key = fn (Request $request) => $request->user()?->getKey() ?: $request->ip();

        RateLimiter::for('auth', fn (Request $request) => Limit::perMinute(10)->by($request->ip()));
        RateLimiter::for('register', fn (Request $request) => Limit::perHour(10)->by($request->ip()));
        RateLimiter::for('contact', fn (Request $request) => [
            Limit::perMinute(3)->by($request->ip()),
            Limit::perDay(20)->by($request->ip()),
        ]);
        RateLimiter::for('downloads', fn (Request $request) => Limit::perMinute(20)->by($key($request)));
        RateLimiter::for('checkout', fn (Request $request) => Limit::perMinute(10)->by($key($request)));
        RateLimiter::for('payments', fn (Request $request) => Limit::perMinute(5)->by($key($request)));
        RateLimiter::for('cart', fn (Request $request) => Limit::perMinute(60)->by($key($request)));
        RateLimiter::for('uploads', fn (Request $request) => Limit::perMinute(20)->by($key($request)));
    }

    protected function registerViewComposers(): void
    {
        View::composer(['partials.header', 'partials.footer', 'products.index'], function ($view) {
            $ttl = (int) config('marketplace.cache.nav_ttl');

            $view->with('navCategories', CatalogCache::models(CatalogCache::NAV_CATEGORIES, $ttl, Category::class, fn () => Category::query()
                ->active()
                ->ordered()
                ->withCount(['products' => fn ($q) => $q->visible()])
                ->get(['id', 'name', 'slug', 'icon'])));

            $view->with('footerPages', CatalogCache::models(CatalogCache::FOOTER_PAGES, $ttl, Page::class, fn () => Page::query()
                ->active()
                ->where('show_in_footer', true)
                ->orderBy('sort_order')
                ->get(['id', 'title', 'slug'])));

            $view->with('cartCount', app(CartService::class)->count());
        });

        View::composer('layouts.admin', function ($view) {
            $user = auth()->user();

            $view->with('adminBadges', [
                'products' => $user?->can('approve_products') ? Product::query()->where('approval_status', ApprovalStatus::Pending->value)->count() : 0,
                'payments' => $user?->can('manage_payments') ? Payment::query()->where('status', PaymentRecordStatus::UnderReview->value)->count() : 0,
                'messages' => $user?->can('manage_messages') ? ContactMessage::query()->where('is_read', false)->count() : 0,
                'publishers' => $user?->can('manage_publishers') ? User::query()->whereHas('publisherProfile', fn ($q) => $q->where('status', 'pending'))->count() : 0,
            ]);
        });

        View::composer(['layouts.panel', 'partials.header'], function ($view) {
            $user = auth()->user();

            $view->with('unreadNotifications', $user ? $user->unreadNotifications()->count() : 0);
            $view->with('latestNotifications', $user ? $user->notifications()->latest()->limit(5)->get() : collect());
        });
    }
}
