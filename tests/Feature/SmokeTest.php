<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Services\ReportService;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Renders every page with the demo data for each role, catching template,
 * query and lazy-loading errors across the whole UI.
 */
class SmokeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Storage::fake('local');
        $this->seed(DemoSeeder::class);
    }

    public function test_public_pages_render(): void
    {
        $product = Product::query()->published()->firstOrFail();
        $free = Product::query()->published()->where('is_free', true)->firstOrFail();

        $this->assertPagesOk([
            '/', '/products', '/products?type=free&sort=popular', '/products?featured=1&min_price=100&max_price=5000',
            '/categories', '/category/wordpress-themes', '/search?q=wordpress', '/search?q=',
            $product->url(), $free->url(), '/publisher/pixelcraft',
            '/page/about', '/page/terms-and-conditions', '/page/privacy-policy', '/page/refund-policy',
            '/page/publisher-guidelines', '/page/how-it-works', '/contact', '/cart',
            '/login', '/register', '/register/publisher', '/forgot-password', '/reset-password/some-token',
            '/sitemap.xml', '/robots.txt',
        ]);

        $this->get('/page/contact')->assertRedirect(route('contact.show'));
        $this->get('/product/does-not-exist')->assertNotFound()->assertSee('Page not found');
    }

    public function test_customer_pages_render(): void
    {
        $customer = User::query()->where('email', 'customer@knbazar.test')->firstOrFail();
        $orders = Order::query()->where('user_id', $customer->id)->get();
        $unpaid = $orders->first(fn (Order $o) => $o->canSubmitPayment());

        $this->actingAs($customer);
        $this->assertPagesOk([
            '/account', '/account/profile', '/account/payments', '/account/purchases', '/orders', '/downloads', '/notifications',
            ...$orders->map(fn (Order $o) => route('orders.show', $o))->all(),
            route('orders.pay', $unpaid),
        ]);

        // Checkout with an item in the cart
        $product = Product::query()->published()->where('is_free', false)->where('slug', 'flat-icon-pack-500-icons')->firstOrFail();
        $this->post(route('cart.add', $product));
        $this->assertPagesOk(['/cart', '/checkout']);
    }

    public function test_publisher_pages_render(): void
    {
        $publisher = User::query()->where('email', 'publisher@knbazar.test')->firstOrFail();
        $products = Product::query()->where('user_id', $publisher->id)->get();

        $this->actingAs($publisher);
        $this->assertPagesOk([
            '/publisher', '/publisher/products', '/publisher/products?status=rejected', '/publisher/products/create',
            '/publisher/orders', '/publisher/profile', '/notifications',
            ...$products->map(fn (Product $p) => route('publisher.products.show', $p))->all(),
            ...$products->map(fn (Product $p) => route('publisher.products.edit', $p))->all(),
        ]);

        // Pending publisher sees a restricted dashboard
        $this->actingAs(User::query()->where('email', 'pending.publisher@knbazar.test')->firstOrFail());
        $this->get('/publisher')->assertOk()->assertSee('awaiting approval');
    }

    public function test_admin_pages_render(): void
    {
        $admin = User::query()->where('email', 'superadmin@knbazar.test')->firstOrFail();
        $product = Product::query()->firstOrFail();
        $order = Order::query()->firstOrFail();
        $payment = Payment::query()->firstOrFail();
        $publisher = User::query()->where('email', 'publisher@knbazar.test')->firstOrFail();
        $customer = User::query()->where('email', 'customer@knbazar.test')->firstOrFail();

        $this->actingAs($admin);

        $pages = [
            '/admin', '/admin?range=this_month', '/admin/search?q=theme', '/admin/search?q=ORD',
            '/admin/users', '/admin/users?role=publisher', '/admin/users/create', route('admin.users.edit', $customer), '/admin/customers',
            '/admin/admins', '/admin/admins/create', route('admin.admins.edit', $admin),
            '/admin/publishers', '/admin/publishers?status=pending', route('admin.publishers.show', $publisher),
            '/admin/products', '/admin/products?approval=pending&type=paid', '/admin/products/create', '/admin/products/create?type=free',
            route('admin.products.show', $product), route('admin.products.edit', $product),
            '/admin/categories', '/admin/categories/create', route('admin.categories.edit', 1),
            '/admin/orders', '/admin/orders?status=completed&from=2026-01-01', route('admin.orders.show', $order),
            '/admin/payments', '/admin/payments?status=under_review', route('admin.payments.show', $payment),
            '/admin/payment-gateways', '/admin/payment-gateways/create', route('admin.gateways.edit', 1),
            '/admin/downloads', '/admin/pages', '/admin/pages/create', route('admin.pages.edit', 1),
            '/admin/banners', '/admin/banners/create', route('admin.banners.edit', 1),
            '/admin/seo', '/admin/settings', '/admin/messages', '/admin/logs', '/admin/logs?tab=files', '/notifications',
        ];

        foreach (array_keys(config('settings.groups')) as $group) {
            $pages[] = route('admin.settings.edit', $group);
        }

        foreach (array_keys(ReportService::TYPES) as $type) {
            $pages[] = route('admin.reports.index', ['type' => $type]);
            $pages[] = route('admin.reports.index', ['type' => $type, 'range' => 'custom', 'from' => '2026-01-01', 'to' => now()->format('Y-m-d')]);
        }

        $this->assertPagesOk($pages);

        $this->get(route('admin.reports.export', ['type' => 'sales']))->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->get(route('admin.products.file', $product))->assertOk();
    }

    public function test_settings_can_be_saved(): void
    {
        $this->actingAs(User::query()->where('email', 'superadmin@knbazar.test')->firstOrFail());

        $this->put(route('admin.settings.update', 'whatsapp'), [
            'whatsapp_enabled' => '1',
            'whatsapp_country_code' => '92',
            'whatsapp_number' => '3211234567',
            'whatsapp_button_text' => 'Buy on WhatsApp',
            'whatsapp_message_template' => 'I want {product_name}',
        ])->assertSessionHasNoErrors();

        $this->assertSame('3211234567', setting('whatsapp_number'));

        $this->put(route('admin.settings.update', 'general'), [
            'site_name' => 'My Market', 'currency' => 'USD', 'currency_position' => 'before', 'timezone' => 'Asia/Karachi',
        ])->assertSessionHasNoErrors();

        $this->assertSame('USD', setting('currency'));
        $this->get('/')->assertSee('My Market');
    }

    /**
     * @param  array<int, string>  $urls
     */
    protected function assertPagesOk(array $urls): void
    {
        foreach ($urls as $url) {
            $response = $this->get($url);
            $this->assertSame(200, $response->status(), "GET {$url} returned {$response->status()}".($response->exception ? ': '.$response->exception->getMessage() : ''));
        }
    }
}
