<?php

use App\Http\Controllers\Account;
use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DownloadController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\Publisher;
use App\Http\Controllers\PublisherController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SeoFileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public marketplace
|--------------------------------------------------------------------------
*/

Route::get('/', HomeController::class)->name('home');
Route::get('/products', [ProductController::class, 'index'])->name('products.index');
Route::get('/product/{slug}', [ProductController::class, 'show'])->where('slug', '[A-Za-z0-9\-]+')->name('products.show');
Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
Route::get('/category/{slug}', [CategoryController::class, 'show'])->where('slug', '[A-Za-z0-9\-]+')->name('categories.show');
Route::get('/search', SearchController::class)->name('search');
Route::get('/page/{slug}', [PageController::class, 'show'])->where('slug', '[A-Za-z0-9\-]+')->name('pages.show');
Route::get('/contact', [ContactController::class, 'show'])->name('contact.show');
Route::post('/contact', [ContactController::class, 'store'])->middleware('throttle:contact')->name('contact.store');
Route::get('/sitemap.xml', [SeoFileController::class, 'sitemap'])->name('sitemap');
Route::get('/robots.txt', [SeoFileController::class, 'robots'])->name('robots');

Route::middleware('throttle:cart')->group(function () {
    Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
    Route::post('/cart/{product}', [CartController::class, 'add'])->whereNumber('product')->name('cart.add');
    Route::patch('/cart/{productId}', [CartController::class, 'update'])->whereNumber('productId')->name('cart.update');
    Route::delete('/cart/{productId}', [CartController::class, 'remove'])->whereNumber('productId')->name('cart.remove');
    Route::delete('/cart', [CartController::class, 'clear'])->name('cart.clear');
    Route::post('/buy-now/{product}', [CartController::class, 'buyNow'])->whereNumber('product')->name('cart.buy-now');
});

// Secure downloads (free and purchased). Authorisation happens in DownloadService.
Route::get('/download/{product:slug}', [DownloadController::class, 'download'])
    ->middleware(['throttle:downloads', 'verified.optional'])
    ->name('downloads.download');

/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {
    Route::get('/login', [Auth\LoginController::class, 'create'])->name('login');
    Route::post('/login', [Auth\LoginController::class, 'store'])->middleware('throttle:auth');
    Route::get('/register', [Auth\RegisterController::class, 'create'])->name('register');
    Route::post('/register', [Auth\RegisterController::class, 'store'])->middleware('throttle:register');
    Route::get('/forgot-password', [Auth\PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [Auth\PasswordResetLinkController::class, 'store'])->middleware('throttle:auth')->name('password.email');
    Route::get('/reset-password/{token}', [Auth\NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [Auth\NewPasswordController::class, 'store'])->middleware('throttle:auth')->name('password.store');
});

// Guests register a new publisher account; signed-in customers can upgrade.
Route::get('/register/publisher', [Auth\PublisherRegisterController::class, 'create'])->name('register.publisher');
Route::post('/register/publisher', [Auth\PublisherRegisterController::class, 'store'])->middleware('throttle:register');

Route::middleware('auth')->group(function () {
    Route::post('/logout', [Auth\LoginController::class, 'destroy'])->name('logout');

    Route::get('/email/verify', [Auth\EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [Auth\EmailVerificationController::class, 'verify'])
        ->middleware(['signed', 'throttle:6,1'])->name('verification.verify');
    Route::post('/email/verification-notification', [Auth\EmailVerificationController::class, 'send'])
        ->middleware('throttle:6,1')->name('verification.send');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::get('/notifications/{id}', [NotificationController::class, 'open'])->whereUuid('id')->name('notifications.open');

    /*
    |----------------------------------------------------------------------
    | Customer account
    |----------------------------------------------------------------------
    */
    Route::get('/account', [Account\AccountController::class, 'index'])->name('account.index');
    Route::get('/account/profile', [Account\ProfileController::class, 'edit'])->name('account.profile');
    Route::put('/account/profile', [Account\ProfileController::class, 'update'])->name('account.profile.update');
    Route::put('/account/password', [Account\ProfileController::class, 'password'])->name('account.password.update');
    Route::get('/account/payments', [Account\AccountController::class, 'payments'])->name('account.payments');
    Route::get('/account/purchases', [Account\AccountController::class, 'purchases'])->name('account.purchases');

    Route::get('/orders', [Account\OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [Account\OrderController::class, 'show'])->name('orders.show');
    Route::post('/orders/{order}/cancel', [Account\OrderController::class, 'cancel'])->name('orders.cancel');
    Route::get('/orders/{order}/payments/{payment}/proof', [Account\PaymentController::class, 'proof'])->name('orders.payments.proof');
    Route::get('/downloads', [DownloadController::class, 'index'])->name('downloads.index');

    Route::middleware('verified.optional')->group(function () {
        Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
        Route::post('/checkout', [CheckoutController::class, 'store'])->middleware('throttle:checkout')->name('checkout.store');
        Route::get('/orders/{order}/payment', [Account\PaymentController::class, 'create'])->name('orders.pay');
        Route::post('/orders/{order}/payment', [Account\PaymentController::class, 'store'])->middleware('throttle:payments')->name('orders.pay.store');
    });
});

/*
|--------------------------------------------------------------------------
| Publisher dashboard  (/publisher/*)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:publisher'])->prefix('publisher')->name('publisher.')->group(function () {
    Route::get('/', Publisher\DashboardController::class)->name('dashboard');
    Route::get('/profile', [Publisher\ProfileController::class, 'edit'])->name('profile');
    Route::put('/profile', [Publisher\ProfileController::class, 'update'])->middleware('throttle:uploads')->name('profile.update');
    Route::get('/orders', [Publisher\OrderController::class, 'index'])->name('orders');

    Route::middleware(['publisher.approved', 'verified.optional'])->group(function () {
        Route::get('/products/create', [Publisher\ProductController::class, 'create'])->name('products.create');
        Route::post('/products', [Publisher\ProductController::class, 'store'])->middleware('throttle:uploads')->name('products.store');
        Route::get('/products/{product}/edit', [Publisher\ProductController::class, 'edit'])->name('products.edit');
        Route::put('/products/{product}', [Publisher\ProductController::class, 'update'])->middleware('throttle:uploads')->name('products.update');
        Route::post('/products/{product}/submit', [Publisher\ProductController::class, 'submit'])->name('products.submit');
        Route::delete('/products/{product}/images/{image}', [Publisher\ProductController::class, 'destroyImage'])->name('products.images.destroy');
    });

    Route::get('/products', [Publisher\ProductController::class, 'index'])->name('products.index');
    Route::get('/products/{product}', [Publisher\ProductController::class, 'show'])->whereNumber('product')->name('products.show');
    Route::get('/products/{product}/file', [Publisher\ProductController::class, 'file'])->name('products.file');
    Route::delete('/products/{product}', [Publisher\ProductController::class, 'destroy'])->name('products.destroy');
});

// Public publisher storefront. Registered after the dashboard routes;
// usernames that collide with them are reserved at registration.
Route::get('/publisher/{username}', [PublisherController::class, 'show'])->where('username', '[A-Za-z0-9_\-]+')->name('publishers.show');

/*
|--------------------------------------------------------------------------
| Admin panel  (/admin/*)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:super_admin,admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', Admin\DashboardController::class)->name('dashboard');
    Route::get('/search', Admin\SearchController::class)->name('search');

    Route::middleware('can:manage_users')->group(function () {
        Route::get('/customers', [Admin\UserController::class, 'customers'])->name('customers.index');
        Route::resource('users', Admin\UserController::class)->except('show');
    });

    Route::middleware('can:manage_admins')->group(function () {
        Route::resource('admins', Admin\AdminUserController::class)->except('show')->parameters(['admins' => 'user']);
    });

    Route::middleware('can:manage_publishers')->group(function () {
        Route::get('/publishers', [Admin\PublisherController::class, 'index'])->name('publishers.index');
        Route::get('/publishers/{user}', [Admin\PublisherController::class, 'show'])->name('publishers.show');
        Route::post('/publishers/{user}/status', [Admin\PublisherController::class, 'updateStatus'])->name('publishers.status');
    });

    // Products: reviewers (approve_products) can list and inspect; managers can edit.
    Route::middleware('can:view_products')->group(function () {
        Route::get('/products', [Admin\ProductController::class, 'index'])->name('products.index');
        Route::post('/products/bulk', [Admin\ProductController::class, 'bulk'])->name('products.bulk');
        Route::get('/products/{product}', [Admin\ProductController::class, 'show'])->whereNumber('product')->name('products.show');
        Route::get('/products/{product}/file', [Admin\ProductController::class, 'file'])->name('products.file');
    });

    Route::middleware('can:approve_products')->group(function () {
        Route::post('/products/{product}/approve', [Admin\ProductApprovalController::class, 'approve'])->name('products.approve');
        Route::post('/products/{product}/reject', [Admin\ProductApprovalController::class, 'reject'])->name('products.reject');
    });

    Route::middleware('can:manage_products')->group(function () {
        Route::get('/products/create', [Admin\ProductController::class, 'create'])->name('products.create');
        Route::post('/products', [Admin\ProductController::class, 'store'])->name('products.store');
        Route::get('/products/{product}/edit', [Admin\ProductController::class, 'edit'])->name('products.edit');
        Route::put('/products/{product}', [Admin\ProductController::class, 'update'])->name('products.update');
        Route::delete('/products/{product}', [Admin\ProductController::class, 'destroy'])->name('products.destroy');
        Route::post('/products/{product}/publish', [Admin\ProductApprovalController::class, 'publish'])->name('products.publish');
        Route::post('/products/{product}/unpublish', [Admin\ProductApprovalController::class, 'unpublish'])->name('products.unpublish');
        Route::post('/products/{product}/feature', [Admin\ProductApprovalController::class, 'toggleFeatured'])->name('products.feature');
        Route::delete('/products/{product}/images/{image}', [Admin\ProductController::class, 'destroyImage'])->name('products.images.destroy');
    });

    Route::middleware('can:manage_categories')->group(function () {
        Route::post('/categories/{category}/toggle', [Admin\CategoryController::class, 'toggle'])->name('categories.toggle');
        Route::resource('categories', Admin\CategoryController::class)->except('show');
    });

    Route::middleware('can:manage_orders')->group(function () {
        Route::get('/orders', [Admin\OrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/{order}', [Admin\OrderController::class, 'show'])->name('orders.show');
        Route::post('/orders/{order}/status', [Admin\OrderController::class, 'updateStatus'])->name('orders.status');
        Route::post('/orders/{order}/notes', [Admin\OrderController::class, 'addNote'])->name('orders.notes');
    });

    Route::middleware('can:manage_payments')->group(function () {
        Route::get('/payments', [Admin\PaymentController::class, 'index'])->name('payments.index');
        Route::get('/payments/{payment}', [Admin\PaymentController::class, 'show'])->name('payments.show');
        Route::get('/payments/{payment}/proof', [Admin\PaymentController::class, 'proof'])->name('payments.proof');
        Route::post('/payments/{payment}/approve', [Admin\PaymentController::class, 'approve'])->name('payments.approve');
        Route::post('/payments/{payment}/reject', [Admin\PaymentController::class, 'reject'])->name('payments.reject');
    });

    Route::resource('payment-gateways', Admin\PaymentGatewayController::class)
        ->except('show')
        ->parameters(['payment-gateways' => 'gateway'])
        ->names('gateways')
        ->middleware('can:manage_payment_gateways');

    Route::get('/downloads', Admin\DownloadController::class)->middleware('can:manage_downloads')->name('downloads.index');

    Route::resource('pages', Admin\PageController::class)->except('show')->middleware('can:manage_pages');
    Route::resource('banners', Admin\BannerController::class)->except('show')->middleware('can:manage_homepage');

    // Settings: each group is authorised individually in the controller.
    Route::get('/seo', [Admin\SettingController::class, 'seo'])->name('seo.edit');
    Route::get('/settings/{group?}', [Admin\SettingController::class, 'edit'])->where('group', '[a-z_]+')->name('settings.edit');
    Route::put('/settings/{group}', [Admin\SettingController::class, 'update'])->where('group', '[a-z_]+')->name('settings.update');

    Route::middleware('can:manage_messages')->group(function () {
        Route::get('/messages', [Admin\ContactMessageController::class, 'index'])->name('messages.index');
        Route::get('/messages/{message}', [Admin\ContactMessageController::class, 'show'])->name('messages.show');
        Route::post('/messages/{message}/toggle-read', [Admin\ContactMessageController::class, 'toggleRead'])->name('messages.toggle');
        Route::delete('/messages/{message}', [Admin\ContactMessageController::class, 'destroy'])->name('messages.destroy');
    });

    Route::middleware('can:view_reports')->group(function () {
        Route::get('/reports', [Admin\ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/export', [Admin\ReportController::class, 'export'])->name('reports.export');
    });

    Route::get('/logs', Admin\LogController::class)->middleware('can:view_logs')->name('logs.index');
});
